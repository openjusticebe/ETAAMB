"""
page.py — Python port of Page.pm (Autocorrect dependency removed).

Two public normalize functions are exported for use in commands.py:
  - normalize_title(txt)   mirrors Page.pm's normalize()  — for type matching
  - normalize_text(txt)    mirrors parseRaws.pl normalize() — for DB storage
"""

import re
import unicodedata
from bs4 import BeautifulSoup
import logging

logger = logging.getLogger(__name__)


# ── Text normalization ─────────────────────────────────────────────────────────

def _strip_accents(txt):
    nfkd = unicodedata.normalize('NFD', txt)
    return ''.join(c for c in nfkd if not unicodedata.combining(c))


def normalize_title(txt):
    """Strip accents, lowercase, remove punctuation (except . ; : - and space).
    Used internally by Page.get_type() — mirrors Page.pm::normalize()."""
    if not txt:
        return ''
    txt = _strip_accents(txt)
    txt = txt.lower()
    txt = re.sub(r'\s', ' ', txt)
    txt = re.sub(r'[^A-Za-z0-9.;:\- ]', '', txt)
    return txt


def normalize_text(txt):
    """Strip accents, lowercase, remove elisions + punctuation.
    Used when storing normalized text to DB — mirrors parseRaws.pl::normalize()."""
    if not txt:
        return ''
    txt = _strip_accents(txt)
    txt = txt.lower()
    txt = re.sub(r'\s', ' ', txt)
    txt = re.sub(r"\w'", '', txt)
    txt = re.sub(r'[^A-Za-z0-9.;:\- ]', '', txt)
    return txt


def _norm_html(html):
    """Produce the normalized HTML string used for regex matching inside Page.
    Mirrors the processing done in Perl's Page::new:
      unac_string + lc + strip <sup> + fix ministrieel typo."""
    if not html:
        return ''
    html = re.sub(r'</?sup>', '', html, flags=re.IGNORECASE)
    html = html.replace('ministrieel', 'ministerieel')
    return _strip_accents(html).lower()


# ── Type-matching helpers ──────────────────────────────────────────────────────

_DATE_PREFIX = (
    r'(?:'
    r'\s*(?:\d+/){2}\d+\s*\.?'       # 01/01/2024.
    r'|\s*\d+(?:er)?\s*\w+\s*\d+\s*\.?'  # 1er janvier 2024.
    r'|[^.]*\.\s*'                    # anything then dot
    r'|\s*'                           # just whitespace
    r')'
)


def _matches_at_start(title, mask):
    """Match mask at the start of title, possibly after a date/preamble prefix."""
    pattern = rf'^(?:{mask})|^{_DATE_PREFIX}-?\s*[^a-z]{{0,}}{mask}'
    return bool(re.search(pattern, title, re.IGNORECASE))


def _matches_anywhere(title, mask):
    return bool(re.search(rf'\W{mask}\W', title, re.IGNORECASE))


def _matches_source(source, source_mask):
    return bool(re.match(rf'^\s*{source_mask}\s*$', source, re.IGNORECASE))


def _matches_doc_mask(title, doc_mask):
    if not doc_mask:
        return False
    return bool(re.search(rf'\W{doc_mask}\W|^{doc_mask}\W', title, re.IGNORECASE))


# ── Classification tables (faithfully ported from Page.pm) ───────────────────

SOURCE_TYPES = {
    r'(ministere de la|service public federal) justice|(ministerie van|overheidsdienst) justitie': [
        {'mask': r'rechterlijke orde bekendmaking|ordre judiciaire publication',
         'fr': "publication de l'ordre judiciaire",
         'nl': 'bekendmaking van de juridische orde'},
        {'mask': r'jugement|vonnis',
         'fr': 'jugement', 'nl': 'vonnis'},
        {'mask': r'organisation dun concours de recrutement|vergelijkend wervingsexamen|oproep(.*)kandidaten|appel(.*)candidats',
         'fr': 'recrutement', 'nl': 'aanwerving'},
        {'mask': r'rechterlijke orde|ordre judiciaire',
         'fr': "document concernant l'ordre judiciaire",
         'nl': 'document betreffende de rechterlijke ordre'},
        {'mask': r'services judiciaires|gerechtelijke diensten',
         'fr': 'document concernant les services judiciaires',
         'nl': 'document betreffende de gerechtelijke diensten'},
    ],
    r'(ministere de la|service public federal) affaires economiques|(ministerie van|overheidsdienst) economische zaken': [
        {'mask': r'liste|lijst', 'fr': 'liste', 'nl': 'lijst'},
        {'mask': r'prix.*gaz nature|prijzen.*aardgas', 'fr': 'fixation des prix', 'nl': 'vaststelling van de prijzen'},
        {'mask': r'enregistremen.*normes|registratie.*normen', 'fr': 'enregistrement de normes belges', 'nl': 'registratie van belgische normen'},
        {'mask': r'demande.*concession|concessieaanvraag', 'fr': 'demande de concession', 'nl': 'concessieaanvraag'},
        {'mask': r'mededeling|information', 'fr': 'communication', 'nl': 'mededeling'},
        {'mask': r'conseil de la concurrence.*avis|raad voor de mededinging.*kennisgeving', 'fr': 'avis du conseil de la concurrence', 'nl': 'kennisgeving van de raad voor de mededinging'},
        {'mask': r'conseil de la concurrence.*decision|raad voor de mededinging.*beslissing', 'fr': 'décision du conseil de la concurrence', 'nl': 'beslissing van de raad voor de mededinging'},
        {'mask': r'formule i.b.t|formule e.i.l', 'fr': 'formule i.b.t.', 'nl': 'formule e.i.l.'},
        {'mask': r'interdiction de mise sur le marche|verbod tot het in de handel brengen', 'fr': 'interdiction de mise sur le marche', 'nl': 'verbod tot het in de handel brengen'},
        {'mask': r'actes dapprobation|akten tot goedkeuring', 'fr': 'approbations', 'nl': 'goedkeuringen'},
        {'mask': r'indice des prix a la (consommation|production)|indexcijfer.*(produktieprijzen|consumptieprijzen)', 'fr': 'indice des prix', 'nl': 'indexcijfer van de prijzen'},
    ],
    r"ministere.*emploi et du travail|ministerie van tewerkstelling en arbeid|service public federal emploi, travail et concertation sociale|federale overheidsdienst werkgelegenheid, arbeid en sociaal overleg": [
        {'mask': r'reglement general.*du travail|reglement.*arbeidsbescherming', 'fr': 'règlement général de la protection du travail', 'nl': 'algemeen reglement voor de arbeidsbescherming'},
        {'mask': r'neerlegging van collectieve arbeidsovereenkomsten|depot de conventions collectives de travail', 'fr': 'dépôt de conventions collectives de travail', 'nl': 'neerlegging van collectieve arbeidsovereenkomsten'},
    ],
    r"(ministere de la|service public federal) affaires sociales.*environnement|(ministerie van|overheidsdienst) sociale zaken.*leefmilieu": [
        {'mask': r'directive|richtlijn', 'fr': 'directive', 'nl': 'richtlijn'},
        {'mask': r'liste|lijst', 'fr': 'liste', 'nl': 'lijst'},
        {'mask': r'rijksinstituut.*invaliditeitsverzekering|institut.*maladie.invalidite', 'fr': "document de l'institut national d'assurance maladie-invalidité", 'nl': ' document van het rijksinstituut voor ziekte- en invaliditeitsverzekering'},
    ],
    r'^(services du premier ministre|diensten van de eerste minister)$': [
        {'mask': '', 'fr': 'document des services du premier ministre', 'nl': 'document van de diensten van de eerste minister'},
    ],
    r'(ministere de la|service public federal) finances|(ministerie van|overheidsdienst) financien': [
        {'mask': r'lotenlening|emprunt a lots', 'fr': 'emprunt à lots', 'nl': 'lotenlening'},
        {'mask': r'domaines publications? prescrites? par|bekendmaking(en)? voorgeschreven bij', 'fr': 'publication', 'nl': 'bekendmaking'},
        {'mask': r'loterie nationale|nationale loterij|lucky bingo', 'fr': 'document concernant la loterie nationale', 'nl': 'document betreffende de  nationale loterij'},
        {'mask': r'monnaie royale de belgique|koninklijke munt van belgie', 'fr': 'document concernant la monnaie royale de belgique', 'nl': 'document betreffende de koninklijke munt van belgie'},
        {'mask': r'(mise en competition|incompetitiestelling)', 'fr': 'mise en compétition', 'nl': 'incompetitiestelling'},
        {'mask': r'administration du cadastre|administratie van het kadaster', 'fr': 'document - cadastre', 'nl': 'document - kadaster'},
        {'mask': r'administration de la t.v.a.|administratie van de btw', 'fr': 'document - t.v.a.', 'nl': 'document - btw'},
        {'mask': r'administration (generale )?de la tresorerie|(algemene )?administratie (der|van de) thesaurie', 'fr': 'document - trésorerie', 'nl': 'document - thesaurie'},
        {'mask': r'administration (generale )?de la fiscalite|(algemene )?administratie (der|van de) fiscaliteit', 'fr': 'document - fiscalité', 'nl': 'document - fiscaliteit'},
        {'mask': r'decision du president|beslissing van de voorzitter', 'fr': 'décision', 'nl': 'beslissing'},
    ],
    r"(ministere de la|service public federal) communications et de l'infrastructure|ministerie van verkeer en infrastructuur": [
        {'mask': r'loterie nationale|nationale loterij|lucky bingo', 'fr': 'document concernant la loterie nationale', 'nl': 'document betreffende de  nationale loterij'},
    ],
    r'(ministere.*|service public federal )interieur|(ministerie van|overheidsdienst) binnenlandse zaken': [
        {'mask': r"intrekking van de vergunning|abrogation de l'?autorisation", 'fr': "abrogation d'autorisation", 'nl': 'intrekking van vergunning'},
        {'mask': r'autorisations? (dexploiter une|dexercer)|vergunning(en)? (tot het exploiteren|om het beroep)', 'fr': 'autorisation', 'nl': 'vergunning'},
        {'mask': r'gendarmerie\.|rijkswacht\.', 'fr': 'document concernant la gendarmerie', 'nl': 'document betreffende de rijkswacht'},
        {'mask': r"elections? communales? d(u|e)", 'fr': 'document concernant les elections communales', 'nl': ''},
        {'mask': r"conseil detat. - avis|raad van state. - bericht", 'fr': "avis du conseil d'état", 'nl': 'bericht van de raad van state'},
        {'mask': r'arretes? concernant les provinces|besluit(en)? betreffende de provincies', 'fr': 'arrêté royal', 'nl': 'koninklijk besluit'},
    ],
    r'service public federal economie, p.m.e., classes moyennes et energie|federale overheidsdienst economie, k.m.o., middenstand en energie': [
        {'mask': r'autorisation individuelle|individuele vergunning', 'fr': 'autorisation', 'nl': 'vergunning'},
        {'mask': r'federaal ontwikkelingsplan|plan de developpement federal', 'fr': 'plan de développement', 'nl': 'ontwikkelingsplan'},
        {'mask': r'conseil de la concurrence.*avis|raad voor de mededinging.*kennisgeving', 'fr': 'avis du conseil de la concurrence', 'nl': 'kennisgeving van de raad voor de mededinging'},
        {'mask': r'liste|lijst', 'fr': 'liste', 'nl': 'lijst'},
        {'mask': r'prix.*gaz nature|prijzen.*aardgas', 'fr': 'fixation des prix', 'nl': 'vaststelling van de prijzen'},
        {'mask': r'enregistremen.*normes|registratie.*normen', 'fr': 'enregistrement de normes belges', 'nl': 'registratie van belgische normen'},
        {'mask': r'demande.*concession|concessieaanvraag', 'fr': 'demande de concession', 'nl': 'concessieaanvraag'},
        {'mask': r'mededeling|information', 'fr': 'communication', 'nl': 'mededeling'},
        {'mask': r'conseil de la concurrence.*decision|raad voor de mededinging.*beslissing', 'fr': 'décision du conseil de la concurrence', 'nl': 'beslissing van de raad voor de mededinging'},
        {'mask': r'formule i.b.t|formule e.i.l', 'fr': 'formule i.b.t.', 'nl': 'formule e.i.l.'},
        {'mask': r'interdiction de mise sur le marche|verbod tot het in de handel brengen', 'fr': 'interdiction de mise sur le marché', 'nl': 'verbod tot het in de handel brengen'},
        {'mask': r'actes dapprobation|akten tot goedkeuring', 'fr': 'approbations', 'nl': 'goedkeuringen'},
        {'mask': r'indice des prix a la (consommation|production)|indexcijfer.*(produktieprijzen|consumptieprijzen)', 'fr': 'indice des prix', 'nl': 'indexcijfer van de prijzen'},
    ],
    r"cour d'arbitrage|arbitragehof|cour constitutionnelle|grondwettelijk hof": [
        {'mask': r"extrait de larret|uittreksel uit arrest", 'fr': "extrait d'un arrêt", 'nl': 'arrest uittreksel'},
        {'mask': r'arrest|arret', 'fr': 'arrêt de la cour constitutionelle', 'nl': 'arrest van het grondwettelijk hof'},
    ],
    r'ministere de la communaute francaise': [
        {'mask': r'jury de promotion charge de classer les candidats', 'fr': 'communique du ministere de la communaute francaise', 'nl': ''},
    ],
    r'ministere.*bruxelles-capitale|ministerie.*brussels hoofdstedelijk gewest': [
        {'mask': r'arretes concernant la ville.*bruxelles|besluiten betreffende de stad.*brussel|arretes concernant.*villes.*communes|besluiten betreffende de provincies steden en gemeenten',
         'fr': 'arrêtés concernant bruxelles', 'nl': 'besluiten betreffende brussel'},
    ],
    r'ministere de la communaute germanophone|ministerie van de duitstalige gemeenschap': [
        {'mask': r'arrete du (ministre|gouvernement)|besluit van de (minister|regering)', 'fr': 'arrêté de la communauté germanophone', 'nl': 'besluit van de duitstalige gemeenschap'},
    ],
    r'ministere de la communaute flamande|ministerie van de vlaamse gemeenschap': [
        {'mask': r'societe publique des dechets|openbare afvalstoffenmaatschappij', 'fr': 'document du ministere de la communauté flamande', 'nl': 'document van het ministeriel van de vlaamse gemeenschapscommissie'},
    ],
    r'ministere de la region wallonne': [
        {'mask': r'tresorerie situation mensuelle du tresor', 'fr': 'situation mensuelle du trésor', 'nl': ''},
    ],
    r'parlement de la region de bruxelles-capitale|brussels hoofdstedelijk parlement': [
        {'mask': r'seances plenieres ordre du jour|plenaire vergaderingen agenda', 'fr': 'ordre du jour des séances plénières', 'nl': 'agenda van de plenaire vergaderingen'},
    ],
    r'commission bancaire et financiere|commissie voor.*financiewezen': [
        {'mask': r'autorisation.*cession|toestemming.*overdracht', 'fr': 'document de la commission bancaire et financière', 'nl': 'document van de commissie voor het bank- en financiewezen'},
    ],
    r'selor.*bureau de selection|selor.*overheid': [
        {'mask': '', 'fr': 'communication du selor', 'nl': 'bericht van selor'},
    ],
    r'pouvoir judiciaire|rechterlijke macht': [
        {'mask': r'aanwijzing|designation|designe|aangewezen', 'fr': "désignation dans l'ordre judiciaire", 'nl': 'aanwijzing in de rechterlijke orde'},
        {'mask': r'ordonnances?|beschikking(en)?', 'fr': 'ordonnance', 'nl': 'beschikking'},
    ],
    r'banque nationale de belgique|nationale bank van belgie': [
        {'mask': r'notification|mededeling', 'fr': 'notification de la banque nationale', 'nl': 'mededeling van de nationale bank'},
        {'mask': r'autorisation.*cession|toestemming.*overdracht', 'fr': 'document de la banque nationale', 'nl': 'document van de nationale bank'},
    ],
    r'service public federal mobilite et transports|federale overheidsdienst mobiliteit en vervoer': [
        {'mask': r'mobilite et securite routiere|mobiliteit en verkeersveiligheid', 'fr': 'document concernant la mobilité et la sécurite routière', 'nl': 'document betreffende mobiliteit en verkeersveiligheid'},
    ],
    r'commission communautaire flamande de la region de bruxelles-capitale|vlaamse gemeenschapscommissie van het brussels hoofdstedelijk gewest': [
        {'mask': r'reglement|verordening', 'fr': 'règlement de la commission communautaire flamande', 'nl': 'verordening van de vlaamse gemeenschapscommissie'},
    ],
}

TYPES = {
    'fr': [
        ('erratum',                          ['errat(?:um|a)']),
        ('proces-verbal',                    ['proces-verbal']),
        ("arrêt du conseil d'état",          [r"conseil d(?:')?etat.? (?:- )?annulation",
                                              r"annulation par le conseil d'?etat",
                                              r"conseil d(?:')?etat.? (?:- )?arret",
                                              r"conseil d(?:')?etat (?:- )?suspension partielle",
                                              r"conseil d(?:')?etat.? (?:- )?suspension"]),
        ('arrêt de la cour constitutionelle', [r'arret .*(?:- )?questions? prejudicielles?',
                                               r'arret .*(?:- )?recours en annulation partielle',
                                               r'arret .*(?:- )?recours en annulation',
                                               r'arret .*(?:- )?demande de suspension']),
        ("ordonnance de la cour d'appel",    [r"cour d(?:')?appel .*(?:- )?ordonnance"]),
        ('ordonnance du tribunal de commerce',[r'tribunal d(?:e|u) commerce .*(?:- )?ordonnance']),
        ('ordonnance du tribunal du travail', [r'tribunal d(?:e|u) travail .*(?:- )?ordonnance']),
        ('arrêté de la commission communautaire francaise', [r'arrete.*du college.*commission.*francaise']),
        ('arrêté du gouvernement de la communauté germanophone', [r'arrete du gouvernement.*germanophone']),
        ('arrêté de la commission communautaire commune', [r'arrete.*du college reuni.*commission communautaire commune']),
        ('arrêté du gouvernement flamand',   [r'arrete du gouvernement flamand']),
        ('arrêté du gouvernement wallon',    [r'arrete du gouvernement wallon']),
        ('arrêté du gouvernement de la région de bruxelles-capitale', [r'arrete du gouvernement de la region de bruxelles-capitale']),
        ('arrêté du gouvernement de la communauté francaise', [r'arrete du gouvernement de la communaute francaise']),
        ('circulaire coordonnée',            [r'circulaire coordonnee']),
        ('arrêtés concernant les membres des commissions paritaires', [r'arretes concernant les membres des commissions paritaires']),
        ("règlement d'application",          [r"reglement d(?:')?application"]),
        ('arrêté de la commission bancaire et financière', [r'arrete de la commission bancaire et financiere']),
        ("décision du comité de ministres de l'union économique benelux", [r'decision du comite de ministres de lunion economique benelux']),
        ('enquete(s) publique(s)',            [r'enquete(?:s)? publique(?:s)?']),
        ('accord international',             [r'accord entre le royaume', r'convention entre la belgique', r'convention internationale']),
        ('ordre du jour des séances plénières', [r'seances? plenieres? ordre du jour']),
        ('agrément',                         [r"agrement d'?un expert", r'agrements?.*experts', r'agrements? en tant que', r"agrements?.*es ecoles", r'agrement.*laboratoires?']),
        ('enregistrement',                   [r'enregistrements? en tant']),
        ('approbations',                     [r'actes? dapprobation']),
        ('code judiciaire',                  [r'code judiciaire']),
        ('code pénal',                       [r'code penal']),
        ('code des sociétes',                [r'code des societes']),
        ('code civil',                       [r'code civil']),
        ('constitution reserve de recrutement', [r'(?:constitue|constitution) .* reserve de recrutement', r'examen de recrutement']),
        ("communiqué d'etat",                [r"communique d'?etat"]),
        ('consulats étrangers',              [r'consulats etrangers']),
        ("règlement d'ordre interieur",      [r"reglement d(?:')?ordre interieur"]),
        ('arrêté royal',                     [r'arrete royal', r'par arretes royaux']),
        ('arrêté ministeriel',               [r'arretes? ministeriels?']),
        ('demande de concession',            [r'demande de concession']),
        ('décision ministerielle',           [r'decision (?:de la|du) ministre']),
        ('accord de cooperation',            [r'accord de cooperation']),
        ('modification de la constitution',  [r'modification a la constitution', r'revision de la constitution']),
        ("arrêté d'application",             [r"arrete d'application"]),
        ('règlement spécial',                [r'reglement special']),
        ('décret spécial',                   [r'decret special']),
        ('décret-programme',                 [r'decret-programme']),
        ('décret',                           [r'decret']),
        ('loi-programme',                    [r'loi-programme']),
        ('arrêté-loi',                       [r'arrete-?loi']),
        ("changement d'adresse",             [r'changement dadresse']),
        ('arrêt',                            [r'arret']),
        ('ordonnance',                       [r'ordonnance']),
        ('rapport',                          [r'rapport']),
        ('circulaire',                       [r'circulaire']),
        ('loi',                              [r'loi']),
        ('nominations',                      [r'personnel.*nominations?']),
        ('avis',                             [r'avis']),
        ('jugement',                         [r'jugement']),
        ('avenant',                          [r'avenant']),
        ('règlement',                        [r'reglement']),
        ('ratification',                     [r'ratification']),
        ('adhésion',                         [r'adhesion']),
        ('autorisation',                     [r'autorisation']),
        ('liste',                            [r'liste des']),
        ('protocole',                        [r'protocole']),
        ("vacance d'emploi",                 [r"vacance(?:s) d'emploi(?:s)"]),
        ('communication',                    [r'communication']),
        ('recrutement',                      [r'recrutement']),
        ('plan de secteur',                  [r'plan de secteur']),
        ('remise de lettres de créance',     [r'remise de lettres de creance']),
        ('composition',                      [r'composition']),
        ("journal officiel des communautés européennes", [r'journal officiel des communautes europeennes']),
        ('indices du prix',                  [r'indices du prix']),
        ("publication de l'ordre judiciaire", []),
        ("document concernant l'ordre judiciaire", []),
        ('document concernant les services judiciaires', []),
        ('nomination par arrete royal',      []),
    ],
    'nl': [
        ('erratum',                          ['errat(?:um|a)']),
        ('proces-verbaal',                   ['proces-verbaal']),
        ('arrest van de raad van state',     [r'raad van state.? (?:- )?vernietiging',
                                              r'vernietiging door de raad van state',
                                              r'raad van state.? (?:- )?arrest',
                                              r'raad van state (?:- )?gedeeltelijke schorsing',
                                              r'raad van state.? (?:- )?schorsing']),
        ('arrest van het grondwettelijk hof', [r'arrest .*(?:- )?prejudiciele vraa?g(?:en)?',
                                               r'arrest .*(?:- )?beroep(?:en)? tot gedeeltelijke vernietiging',
                                               r'arrest .*(?:- )?beroep(?:en)? tot vernietiging',
                                               r'arrest .*(?:- )?vordering(?:en)? tot schorsing']),
        ('beschikking van het hof van beroep', [r'hof van beroep .*(?:- )?beschikking']),
        ('beschikking van de rechtbank van koophandel', [r'rechtbank van koophandel .*(?:- )?beschikking']),
        ('beschikking van de arbeidsrechtbank', [r'arbeidsrechtbank .*(?:- )?beschikking']),
        ('besluit van de franse gemeenschapscommissie', [r'besluit.*van het college.*franse gemeenschapscommissie']),
        ('besluit van de regering van de duitstalige gemeenschap', [r'besluit van de regering van de duitstalige gemeenschap']),
        ('besluit van de gemeenschappelijke gemeenschapscommissie', [r'besluit.*van.*gemeenschappelijke gemeenschapscommissie']),
        ('besluit van de vlaamse regering', [r'besluit van de vlaamse regering']),
        ('besluit van de waalse regering',  [r'besluit van de waalse regering']),
        ('besluit van de brusselse hoofdstedelijke regering', [r'besluit van de brusselse hoofdstedelijke regering']),
        ('besluit van de regering van de franse gemeenschap', [r'besluit (?:van de regering )?van de franse gemeenschap']),
        ('gecoordineerde omzendbrief',      [r'gecoordineerde omzendbrief']),
        ('besluiten betreffende de leden van de paritaire comites', [r'besluiten betreffende de leden .* paritaire comites']),
        ('toepassingsreglement',            [r'toepassingsreglement']),
        ('besluit van de commissie voor het bank- en financiewezen', [r'besluit van de commissie .*financiewezen']),
        ('beschikking van het comite van ministers van de benelux economische unie', [r'beschikking van het comite van ministers van de benelux economische unie']),
        ('publicatie(s) ter kritiek',       [r'publicatie(?:s)? ter kritiek']),
        ('internationale overeenkomst',     [r'overeenkomst tussen het koninkrijk', r'overeenkomst tussen belgie', r'internationa(?:al|le) verdrag']),
        ('agenda van de plenaire vergaderingen', [r'plenaire vergadering(?:en)? agenda']),
        ('erkenning',                       [r'erkenning van een deskundige', r'erkenning.*deskundigen', r'erkenning(?:en)? als', r'erkenning.*scholen', r'erkenning.*laboratori(?:a|um)']),
        ('registratie',                     [r'registratie als']),
        ('goedkeuringen',                   [r'akten? tot goedkeuring']),
        ('gerechtelijk wetboek',            [r'gerechtelijk wetboek']),
        ('strafwetboek',                    [r'strafwetboek']),
        ('wetboek van vennootschappen',     [r'wetboek van vennootschappen']),
        ('burgerlijk wetboek',              [r'burgerlijk wetboek']),
        ('samenstelling wervingsreserve',   [r'wervings?reserve', r'aanleggen van een personeelsreserve', r'wervingsexamen']),
        ('mededeling van de Staat',         [r'mededeling van de staat']),
        ('buitenlandse consulaten',         [r'buitenlandse consulaten']),
        ('huishoudelijk reglement',         [r'huishoudelijk reglement']),
        ('koninklijk besluit',              [r'koninklijk besluit', r'bij koninklijke besluiten']),
        ('ministerieel besluit',            [r'ministeriee?le? besluit(?:en)?']),
        ('concessieaanvraag',               [r'concessieaanvraag']),
        ('ministeriele beslissing',         [r'beslissing van de minister']),
        ('samenwerkingsakkoord',            [r'samenwerkingsakkoord']),
        ('wijziging aan de grondwet',       [r'wijziging aan de grondwet', r'herziening van de grondwet']),
        ('toepassingsbesluit',              [r'toepassingsbesluit']),
        ('bijzonder reglement',             [r'bijzonder reglement']),
        ('bijzonder decreet',               [r'bijzonder decreet']),
        ('programmadecreet',                [r'programmadecreet']),
        ('decreet',                         [r'decreet']),
        ('programmawet',                    [r'programmawet']),
        ('besluitwet',                      [r'besluitwet']),
        ('adreswijziging',                  [r'adreswijziging']),
        ('arrest',                          [r'arrest']),
        ('beschikking',                     [r'ordonnantie', r'beschikking']),
        ('verslag',                         [r'verslag']),
        ('omzendbrief',                     [r'omzendbrief|circulaire']),
        ('wet',                             [r'wet']),
        ('benoemingen',                     [r'personeel.*benoeming(?:en)?']),
        ('bericht',                         [r'bericht', r'advies']),
        ('vonnis',                          [r'vonnis']),
        ('bijakte',                         [r'bijakte']),
        ('overeenkomst',                    [r'overeenkomst']),
        ('bekrachtiging',                   [r'bekrachtiging']),
        ('toetreding',                      [r'toetreding']),
        ('vergunning',                      [r'verordening', r'vergunning']),
        ('lijst',                           [r'lijst van']),
        ('protocol',                        [r'protocol']),
        ('vacante bettreking',              [r'vacante bettreking(?:en)']),
        ('mededeling',                      [r'mededeling']),
        ('aanwerving',                      [r'(?:aan)?werving']),
        ('gewestplan',                      [r'gewestplan']),
        ('overhandiging van geloofsbrieven',[r'overhandiging van geloofsbrieven']),
        ('samenstelling',                   [r'samenstelling']),
        ('publicatieblad van de europese gemeenschappen', [r'publicatieblad van de europese gemeenschappen']),
        ('indexcijfers',                    [r'indexcijfers']),
        ('bekendmaking van de juridische orde', []),
        ('document betreffende de rechterlijke ordre', []),
        ('document betreffende de gerechtelijke diensten', []),
        ('benoeming door koninklijk besluit', []),
    ],
}


# ── Page class ─────────────────────────────────────────────────────────────────

class Page:
    """
    Parses a raw HTML page from ejustice.just.fgov.be and exposes document
    metadata via get_*() methods.  Drop-in Python replacement for Page.pm.
    """

    def __init__(self, html, numac, pub_date, lang):
        self.raw     = html
        self.numac   = numac
        self.pub_date = pub_date
        self.lang    = lang
        self.soup    = BeautifulSoup(html, 'html.parser')
        # Normalized HTML: unaccented + lowercase (used for regex matching)
        self.content = _norm_html(html)

    # ── Public getters ─────────────────────────────────────────────────────────

    def get_source(self):
        if 'no article available with such references' in self.content:
            return 'nosource'
        h1 = self.soup.find('h1', class_='page__title')
        if h1:
            span = h1.find('span')
            if span:
                return span.get_text(strip=True)
        return 'nosource'

    def get_title(self):
        final_title = ''
        intro = self.soup.find(class_='intro-text')

        if intro:
            text = intro.get_text(strip=True)
            # Strip leading date/preamble: "21 JUIN 2024. -" or "21/06/2024 -"
            m = re.match(r'^(?:[^.]+\.)?\s*-?\s*(.+?)(?:\s*-\s*\(.*\))?\s*$', text, re.DOTALL)
            if m and m.group(1).strip():
                final_title = m.group(1).strip()

        if not final_title:
            intro_date = self.soup.find(class_='intro-date')
            main_el    = self.soup.find(attrs={'role': 'main'})
            sub_title  = intro_date.get_text(strip=True) if intro_date else ''
            text_begin = main_el.get_text()[:100] if main_el else ''
            if sub_title:
                final_title = f"{sub_title}(...) {text_begin}(...)"

        if 'SSFetch Text failed:' in self.raw:
            logger.warning("SSFetch failed for %s (%s)", self.numac, self.lang)
        elif not final_title and 'No article available with such references' not in self.raw:
            logger.debug("Could not match title for %s (%s)", self.numac, self.lang)

        final_title = re.sub(r'\([^)]+\)', '', final_title)
        return final_title.strip()

    def get_prom_date(self):
        # 1) Try ELI URI: /eli/type/yyyy/mm/dd/
        m = re.search(r'/eli/[a-z0-9_\-./]+/(\d{4})/(\d{2})/(\d{2})/', self.content, re.IGNORECASE)
        if m:
            return f"{m.group(1)}-{m.group(2)}-{m.group(3)}"

        # 2) Try date in intro-text: "21 juin 2024."
        _months = {
            'janvier': 1, 'fevrier': 2, 'mars': 3, 'avril': 4, 'mai': 5, 'juin': 6,
            'juillet': 7, 'aout': 8, 'septembre': 9, 'octobre': 10, 'novembre': 11, 'decembre': 12,
            'januari': 1, 'februari': 2, 'maart': 3, 'april': 4, 'mei': 5, 'juni': 6,
            'juli': 7, 'augustus': 8, 'september': 9, 'oktober': 10, 'november': 11, 'december': 12,
        }
        m = re.search(r'"intro-text">\s*(\d{1,2})\s+(\w+)\s+(\d{4})\.', self.content, re.IGNORECASE)
        if m:
            month = m.group(2).lower()
            if month in _months:
                return f"{m.group(3)}-{_months[month]:02}-{int(m.group(1)):02}"
        return '--'

    def get_type(self):
        title = normalize_title(self.get_title())
        title = re.sub(r'\s{2,}', ' ', title)

        if not title.strip():
            logger.debug("NO TITLE — default type for %s", self.numac)
            return 'document'

        # Special case: vacancy
        if re.search(
            r'vacante betrekking(?:en)?|openstaande plaats|openstaande betrekking|vacature'
            r'|oproep.*kandidaten|te begeven betrekkingen|recrutering'
            r"|places? vacantes?|emplois? vacants?|vacance.+emploi|vacance.+mandat"
            r"|appel aux candidats|emplois a conferer|mandat vacant",
            title,
        ):
            return "vacance d'emploi" if self.lang == 'fr' else 'vacante bettreking'

        # Special case: royal appointment
        if re.search(
            r'benoeming(?:en)?.*bij +koninklijke? +besluit(?:en)?'
            r'|nominations?.*par +arretes? +roya(?:l|ux)',
            title,
        ):
            return 'benoeming door koninklijk besluit' if self.lang == 'nl' else 'nomination par arrete royal'

        # Source-driven classification
        source = _strip_accents(self.get_source()).lower()
        for source_mask, items in SOURCE_TYPES.items():
            if _matches_source(source, source_mask):
                for item in items:
                    if _matches_doc_mask(title, item['mask']):
                        return item[self.lang]

        # General type matching — beginning of title
        for term, masks in TYPES[self.lang]:
            for mask in masks:
                if _matches_at_start(title, mask):
                    return term

        # General type matching — anywhere in title
        for term, masks in TYPES[self.lang]:
            for mask in masks:
                if _matches_anywhere(title, mask):
                    logger.debug("LONGF %s found '%s' anywhere in title", self.numac, term)
                    return term

        logger.debug("NO TYPE — no type found for %s (%s)", self.numac, self.lang)
        return 'document'

    def get_eli(self):
        if 'no article available with such references' in self.content:
            return None
        m = re.search(r'(/eli/[a-z0-9/_\-./]+)', self.raw, re.IGNORECASE)
        return m.group(1) if m else None

    def get_eli_type(self):
        if 'no article available with such references' in self.content:
            return None
        m = re.search(r'/eli/([a-z0-9_\-.]+)/[a-z0-9/_\-.]+', self.raw, re.IGNORECASE)
        return m.group(1) if m else None

    def get_pdf(self):
        if 'no article available with such references' in self.content:
            return None
        m = re.search(r'href="(/mopdf[^"]+)"', self.raw, re.IGNORECASE)
        return m.group(1).strip() if m else None

    def get_chrono(self):
        if 'no article available with such references' in self.content:
            return None
        m = re.search(r'"(http://reflex\.raadvst-consetat\.be/[^"]+)"', self.raw, re.IGNORECASE)
        return m.group(1).strip() if m else None

    def get_chrono_data(self):
        if 'no article available with such references' in self.content:
            return None
        m = re.search(
            r'reflex\.raadvst-consetat\.be/reflex/\?page=chrono&c=detail_get&d=detail&docid=(\d+)&tab=chrono',
            self.raw, re.IGNORECASE,
        )
        return m.group(1) if m else None

    def get_chamber_data(self):
        if 'no article available with such references' in self.content:
            return (None, None)
        m = re.search(
            r'www\.dekamer\.be/kvvcr/showpage\.cfm\?section=flwb&language=\w{2}'
            r'&cfm=/site/wwwcfm/flwb/flwbn\.cfm\?&dossierID=(\d+)&legislat=(\d+)',
            self.raw, re.IGNORECASE,
        )
        return (m.group(1), m.group(2)) if m else (None, None)

    def get_senate_data(self):
        if 'no article available with such references' in self.content:
            return (None, None)
        m = re.search(
            r'www\.senaa?t\.be/www/\?MIval=dossier&LEG=(\d+)&NR=(\d+)&LANG=',
            self.raw, re.IGNORECASE,
        )
        # Perl returns ($2, $1) i.e. (NR, LEG)
        return (m.group(2), m.group(1)) if m else (None, None)

    # ── Type index helpers (used for cross-language reconciliation) ────────────

    def type_index(self, value):
        """Return 1-based index of value in TYPES[lang], or 0 for 'document'."""
        if value == 'document':
            return 0
        for i, (term, _) in enumerate(TYPES[self.lang]):
            if value == term:
                return i + 1
        return 0

    def get_type_by_index(self, index):
        """Return the type term at 1-based index, or 'document'."""
        if index == 0 or index == 100:
            return 'document'
        types = TYPES[self.lang]
        if 1 <= index <= len(types):
            return types[index - 1][0]
        return 'document'
