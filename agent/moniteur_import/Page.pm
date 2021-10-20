#!/usr/sbin/perl
package Page;
use Encode;
use Text::Unaccent;
use HTML::Strip;
use strict;
use warnings;
use Data::Dumper qw(Dumper);
use utf8;
my %SOURCE_TYPES = (
     '(ministere de la|service public federal) justice|(ministerie van|overheidsdienst) justitie' => [
        {mask => 'rechterlijke orde bekendmaking|ordre judiciaire publication',
         fr   => 'publication de l\'ordre judiciaire',
         nl   => 'bekendmaking van de juridische orde' },
        
        {mask => 'jugement|vonnis',
         fr   => 'jugement',
         nl   => 'vonnis'},

        {mask => 'organisation dun concours de recrutement|vergelijkend wervingsexamen|oproep(.*)kandidaten|appel(.*)candidats',
         fr   => 'recrutement',
         nl   => 'aanwerving'},

        {mask => 'rechterlijke orde|ordre judiciaire',
         fr   => 'document concernant l\'ordre judiciaire',
         nl   => 'document betreffende de rechterlijke ordre'},

        {mask => 'services judiciaires|gerechtelijke diensten',
         fr   => 'document concernant les services judiciaires',
         nl   => 'document betreffende de gerechtelijke diensten'}
         ]
      #,'rechterlijke macht|pouvoir judiciaire' => [
      #  {mask => 'rechtbank(en)? van eerste aanleg.*beschikking|tribuna(ux|l) de premiere instance.*ordonnance',
      #   fr   => 'ordonnance d\'un tribunal de premiere instance',
      #   nl   => 'beschikking van een rechtbank van eerste aanleg'}
      #  ]
      #
      ,'(ministere de la|service public federal) affaires economiques|(ministerie van|overheidsdienst) economische zaken' => [
         {mask  =>'liste|lijst',
          fr    =>'liste',
          nl    =>'lijst'}
         ,{mask  =>'prix.*gaz nature|prijzen.*aardgas',
          fr    =>'fixation des prix',
          nl    =>'vaststelling van de prijzen'}
         ,{mask  =>'enregistremen.*normes|registratie.*normen',
          fr    =>'enregistrement de normes belges',
          nl    =>'registratie van belgische normen'}
         ,{mask  =>'demande.*concession|concessieaanvraag',
          fr    =>'demande de concession',
          nl    =>'concessieaanvraag'}
         ,{mask  =>'mededeling|information',
          fr    =>'communication',
          nl    =>'mededeling'}
         ,{mask  =>'conseil de la concurrence.*avis|raad voor de mededinging.*kennisgeving',
          fr    =>'avis du conseil de la concurrence',
          nl    =>'kennisgeving van de raad voor de mededinging'}
         ,{mask  =>'conseil de la concurrence.*decision|raad voor de mededinging.*beslissing',
          fr    =>'décision du conseil de la concurrence',
          nl    =>'beslissing van de raad voor de mededinging'}
         ,{mask  =>'formule i.b.t|formule e.i.l',
          fr    =>'formule i.b.t.',
          nl    =>'formule e.i.l.'}
         ,{mask  =>'interdiction de mise sur le marche|verbod tot het in de handel brengen',
          fr    =>'interdiction de mise sur le marche',
          nl    =>'verbod tot het in de handel brengen'}
         ,{mask  =>'actes dapprobation|akten tot goedkeuring',
          fr    =>'approbations',
          nl    =>'goedkeuringen'}
         ,{mask  =>'indice des prix a la (consommation|production)|indexcijfer.*(produktieprijzen|consumptieprijzen)',
          fr    =>'indice des prix',
          nl    =>'indexcijfer van de prijzen'}
         ]

         ,'ministere.*emploi et du travail|ministerie van tewerkstelling en arbeid|service public federal emploi, travail et concertation sociale|federale overheidsdienst werkgelegenheid, arbeid en sociaal overleg' => [
         {mask   => 'reglement general.*du travail|reglement.*arbeidsbescherming',
          fr   => 'règlement général de la protection du travail',
          nl   => 'algemeen reglement voor de arbeidsbescherming'}
         ,{mask   => 'neerlegging van collectieve arbeidsovereenkomsten|depot de conventions collectives de travail',
          fr   => 'dépôt de conventions collectives de travail',
          nl   => 'neerlegging van collectieve arbeidsovereenkomsten'}
         ]

      ,'ministere des communications et de l\'infrastructure|ministerie van verkeer en infrastructuur' => [
          #{mask => 'bestuur.*verkeersreglementering.*infrastructuur|administration.*reglementation.*circulation.*infrastructure'
          #,fr   => ''
          #,nl   => '' }
      ]
      ,'(ministere de la|service public federal) affaires sociales.*environnement|(ministerie van|overheidsdienst) sociale zaken.*leefmilieu' => [
          {mask => 'directive|richtlijn'
          ,fr   => 'directive'
          ,nl   => 'richtlijn'
          },
         ,{mask  =>'liste|lijst',
          fr    =>'liste',
          nl    =>'lijst'}
         ,{mask  =>'rijksinstituut.*invaliditeitsverzekering|institut.*maladie.invalidite',
          fr    =>'document de l\'institut national d\'assurance maladie-invalidité',
          nl    =>' document van het rijksinstituut voor ziekte- en invaliditeitsverzekering'}
      ]

      ,'^(services du premier ministre|diensten van de eerste minister)$' => [
        {mask => ''
        ,fr   => 'document des services du premier ministre'
        ,nl   => 'document van de diensten van de eerste minister'}
      ]

      ,'(ministere de la|service public federal) finances|(ministerie van|overheidsdienst) financien' => [
        {mask => 'lotenlening|emprunt a lots'
            ,fr   => 'emprunt à lots'
            ,nl   => 'lotenlening'}
        ,{mask => 'domaines publications? prescrites? par| bekendmaking(en)? voorgeschreven bij'
            ,fr   => 'publication'
            ,nl   => 'bekendmaking'}
        ,{mask => 'loterie nationale|nationale loterij|lucky bingo'
            ,fr   => 'document concernant la loterie nationale'
            ,nl   => 'document betreffende de  nationale loterij'}
        ,{mask => 'monnaie royale de belgique|koninklijke munt van belgie'
            ,fr    => 'document concernant la monnaie royale de belgique'
            ,nl    => 'document betreffende de koninklijke munt van belgie'}
        ,{mask => '(mise en competition|incompetitiestelling)'
            ,fr    => 'mise en compétition'
            ,nl    => 'incompetitiestelling'}
        ,{mask => 'administration du cadastre|administratie van het kadaster'
            ,fr    => 'document - cadastre'
            ,nl    => 'document - kadaster'}
        ,{mask => 'administration de la t.v.a.|administratie van de btw'
            ,fr    => 'document - t.v.a.'
            ,nl    => 'document - btw'}
        ,{mask => 'administration (generale )?de la tresorerie|(algemene )?administratie (der|van de) thesaurie'
            ,fr    => 'document - trésorerie'
            ,nl    => 'document - thesaurie'}
        ,{mask => 'administration (generale )?de la fiscalite|(algemene )?administratie (der|van de) fiscaliteit'
            ,fr    => 'document - fiscalité'
            ,nl    => 'document - fiscaliteit'}
        ,{mask => 'decision du president|beslissing van de voorzitter'
            ,fr    => 'décision'
            ,nl    => 'beslissing'}
        ]

      ,'(ministere de la|service public federal) communications et de l\'infrastructure|ministerie van verkeer en infrastructuur' => [
        {mask => 'loterie nationale|nationale loterij|lucky bingo'
            ,fr   => 'document concernant la loterie nationale'
            ,nl   => 'document betreffende de  nationale loterij'}

        ]

      ,'(ministere.*|service public federal )interieur|(ministerie van|overheidsdienst) binnenlandse zaken' => [
         {mask => 'intrekking van de vergunning|abrogation de l\'?autorisation'
         ,fr   => 'abrogation d\'autorisation'
         ,nl   => 'intrekking van vergunning'}
        ,{mask => 'autorisations? (dexploiter une|dexercer)|vergunning(en)? (tot het exploiteren|om het beroep)'
         ,fr   => 'autorisation'
         ,nl   => 'vergunning'}
        ,{mask => 'gendarmerie\.|rijkswacht\.'
         ,fr   => 'document concernant la gendarmerie'
         ,nl   => 'document betreffende de rijkswacht'}
        ,{mask => 'élections? communales? d(u|e)'
         ,fr   => 'document concernant les elections communales'
         ,nl   => ''}
        ,{mask => 'conseil detat. - avis|raad van state. - bericht'
         ,fr   => 'avis du conseil d\'état'
         ,nl   => 'bericht van de raad van state'}
        ,{mask => 'arretes? concernant les provinces|besluit(en)? betreffende de provincies'
         ,fr   => 'arrêté royal'
         ,nl   => 'koninklijk besluit'}
         ]

      ,'service public federal economie, p.m.e., classes moyennes et energie|federale overheidsdienst economie, k.m.o., middenstand en energie' => [
            {mask   => 'autorisation individuelle|individuele vergunning'
            ,fr     => 'autorisation'
            ,nl     => 'vergunning'}
            ,{mask  => 'federaal ontwikkelingsplan|plan de developpement federal'
             ,fr    => 'plan de développement'
             ,nl    => 'ontwikkelingsplan'}
            ,{mask  => 'conseil de la concurrence.*avis|raad voor de mededinging.*kennisgeving'
             ,fr    => 'avis du conseil de la concurrence'
             ,nl    => 'kennisgeving van de raad voor de mededinging'}
            ,{mask  =>'liste|lijst',
              fr    =>'liste',
              nl    =>'lijst'}
             ,{mask  =>'prix.*gaz nature|prijzen.*aardgas',
              fr    =>'fixation des prix',
              nl    =>'vaststelling van de prijzen'}
             ,{mask  =>'enregistremen.*normes|registratie.*normen',
              fr    =>'enregistrement de normes belges',
              nl    =>'registratie van belgische normen'}
             ,{mask  =>'demande.*concession|concessieaanvraag',
              fr    =>'demande de concession',
              nl    =>'concessieaanvraag'}
             ,{mask  =>'mededeling|information',
              fr    =>'communication',
              nl    =>'mededeling'}
             ,{mask  =>'conseil de la concurrence.*avis|raad voor de mededinging.*kennisgeving',
              fr    =>'avis du conseil de la concurrence',
              nl    =>'kennisgeving van de raad voor de mededinging'}
             ,{mask  =>'conseil de la concurrence.*decision|raad voor de mededinging.*beslissing',
              fr    =>'décision du conseil de la concurrence',
              nl    =>'beslissing van de raad voor de mededinging'}
             ,{mask  =>'formule i.b.t|formule e.i.l',
              fr    =>'formule i.b.t.',
              nl    =>'formule e.i.l.'}
             ,{mask  =>'interdiction de mise sur le marche|verbod tot het in de handel brengen',
              fr    =>'interdiction de mise sur le marché',
              nl    =>'verbod tot het in de handel brengen'}
             ,{mask  =>'actes dapprobation|akten tot goedkeuring',
              fr    =>'approbations',
              nl    =>'goedkeuringen'}
             ,{mask  =>'indice des prix a la (consommation|production)|indexcijfer.*(produktieprijzen|consumptieprijzen)',
              fr    =>'indice des prix',
              nl    =>'indexcijfer van de prijzen'}
            ]

      ,'cour d\'arbitrage|arbitragehof|cour constitutionnelle|grondwettelijk hof' => [
        {mask => 'extrait de larret|uittreksel uit arrest'
        ,fr   => 'extrait d\'un arrêt'
        ,nl   => 'arrest uittreksel' }
        ,{mask => 'arrest|arret'
        ,fr   => 'arrêt de la cour constitutionelle'
        ,nl   => 'arrest van het grondwettelijk hof' }
        ]

      ,'ministere de la communaute francaise' => [
        {mask => 'jury de promotion chargé de classer les candidats'
        ,fr   => 'communique du ministere de la communaute francaise'
        ,nl   => ''}
        ]

      ,'ministere.*bruxelles-capitale|ministerie.*brussels hoofdstedelijk gewest' => [
        {mask => 'arretes concernant la ville.*bruxelles|besluiten betreffende de stad.*brussel|arretes concernant.*villes.*communes|besluiten betreffende de provincies steden en gemeenten'
        ,fr   => 'arrêtés concernant bruxelles'
        ,nl   => 'besluiten betreffende brussel'}
        ]

      ,'ministere de la communaute germanophone|ministerie van de duitstalige gemeenschap' => [
        {mask => 'arrete du (ministre|gouvernement)|besluit van de (minister|regering)'
        ,fr   => 'arrêté de la communauté germanophone'
        ,nl   => 'besluit van de duitstalige gemeenschap'}
        ]

      ,'ministere de la communaute flamande|ministerie van de vlaamse gemeenschap' => [
        {mask => 'societe publique des dechets|openbare afvalstoffenmaatschappij'
        ,fr   => 'document du ministere de la communauté flamande'
        ,nl   => 'document van het ministeriel van de vlaamse gemeenschapscommissie'}
        ]

      ,'ministere de la region wallonne' => [
        {mask => 'tresorerie situation mensuelle du tresor'
        ,fr   => 'situation mensuelle du trésor'
        ,nl   => ''
        }
        ]

      ,'parlement de la region de bruxelles-capitale|brussels hoofdstedelijk parlement' => [
            {mask   => 'seances plenieres ordre du jour|plenaire vergaderingen agenda'
            ,fr     => 'ordre du jour des séances plénières'
            ,nl     => 'agenda van de plenaire vergaderingen'}
            ]

      ,'commission bancaire et financiere|commissie voor.*financiewezen' => [
        {mask=>'autorisation.*cession|toestemming.*overdracht'
        ,fr  => 'document de la commission bancaire et financière'
        ,nl  => 'document van de commissie voor het bank- en financiewezen'}]

       ,'selor.*bureau de selection|selor.*overheid' => [
         {mask=>''
         ,fr  => 'communication du selor'
         ,nl  => 'bericht van selor'}]
        ,'pouvoir judiciaire|rechterlijke macht' => [
         {mask=> 'aanwijzing|designation|designe|aangewezen'
         ,fr  => 'désignation dans l\'ordre judiciaire'
         ,nl  => 'aanwijzing in de rechterlijke orde'}
         ,{mask=> 'ordonnances?|beschikking(en)?'
         ,fr  => 'ordonnance'
         ,nl  => 'beschikking'}
         ]

        ,'banque nationale de belgique|nationale bank van belgie' => [
            {mask   => 'notification|mededeling'
            ,fr     => 'notification de la banque nationale'
            ,nl     => 'mededeling van de nationale bank'}
            ,{mask=>'autorisation.*cession|toestemming.*overdracht'
            ,fr  => 'document de la banque nationale'
            ,nl  => 'document van de nationale bank'}
            ]

      ,'service public federal mobilite et transports|federale overheidsdienst mobiliteit en vervoer' => [
            {mask   =>  'mobilite et securite routiere|mobiliteit en verkeersveiligheid'
            ,fr     =>  'document concernant la mobilité et la sécurite routière'
            ,nl     =>  'document betreffende mobiliteit en verkeersveiligheid'}
            ]

      ,'commission communautaire flamande de la region de bruxelles-capitale|vlaamse gemeenschapscommissie van het brussels hoofdstedelijk gewest' => [
            {mask   => 'reglement|verordening'
            ,fr     => 'règlement de la commission communautaire flamande'
            ,nl     => 'verordening van de vlaamse gemeenschapscommissie'}
            ]
    );


my %TYPES = (
    fr => [
         ['erratum',
                        ['errat(um|a)']]
         ,['proces-verbal',
                        ['proces-verbal']]
        ,['arrêt du conseil d\'état',
                        ['conseil d(\')?etat.? (- )?annulation'
                        ,'annulation par le conseil d\'?etat',
                        ,'conseil d(\')?etat.? (- )?arret'
                        ,'conseil d(\')?etat (- )?suspension partielle'
                        ,'conseil d(\')?etat.? (- )?suspension']]
        ,['arrêt de la cour constitutionelle',
                        ['arret .*(- )?questions? prejudicielles?'
                        ,'arret .*(- )?recours en annulation partielle'
                        ,'arret .*(- )?recours en annulation'
                        ,'arret .*(- )?demande de suspension']]
        ,['ordonnance de la cour d\'appel',
                        ['cour d(\')?appel .*(- )?ordonnance']]
        ,['ordonnance du tribunal de commerce', 
                        ['tribunal d(e|u) commerce .*(- )?ordonnance']]
        ,['ordonnance du tribunal du travail', 
                        ['tribunal d(e|u) travail .*(- )?ordonnance']]
        ,['arrêté de la commission communautaire francaise',
                        ['arrete.*du college.*commission.*francaise']]
        ,['arrêté du gouvernement de la communauté germanophone',
                        ['arrete du gouvernement.*germanophone']]
        ,['arrêté de la commission communautaire commune',
                        ['arrete.*du college reuni.*commission communautaire commune']]
        ,['arrêté du gouvernement flamand',
                        ['arrete du gouvernement flamand']]
        ,['arrêté du gouvernement wallon',
                        ['arrete du gouvernement wallon']]
        ,['arrêté du gouvernement de la région de bruxelles-capitale',
                        ['arrete du gouvernement de la region de bruxelles-capitale']]
        ,['arrêté du gouvernement de la communauté francaise',
                        ['arrete du gouvernement de la communaute francaise']]
        ,['circulaire coordonnée',
                        ['circulaire coordonnee']]
        ,['arrêtés concernant les membres des commissions paritaires',
                        ['arretes concernant les membres des commissions paritaires']]
        ,['règlement d\'application',
                        ['reglement d(\')?application']]
        ,['arrêté de la commission bancaire et financière',
                        ['arrete de la commission bancaire et financiere']]
        ,['décision du comité de ministres de l\'union économique benelux',
                        ['decision du comite de ministres de lunion economique benelux']]
        ,['enquete(s) publique(s)',
                        ['enquete(s)? publique(s)?']]
        ,['accord international',
                        ['accord entre le royaume'
                        ,'convention entre la belgique'
                        ,'convention internationale']]
        ,['ordre du jour des séances plénières',
                        ['seances? plenieres? ordre du jour']]
        ,['agrément',
                        ['agrement d\'?un expert'
                        ,'agrements?.*experts'
                        ,'agrements? en tant que'
                        ,'agrements?.*es ecoles'
                        ,'agrement.*laboratoires?']]
        ,['enregistrement',
                        ['enregistrements? en tant']]

        ,['approbations',
                        ['actes? dapprobation']]
        ,['code judiciaire',
                        ['code judiciaire']]
        ,['code pénal',
                        ['code penal']]
        ,['code des sociétes',
                        ['code des societes']]
        ,['code civil',
                        ['code civil']]
        ,['constitution reserve de recrutement',
                        ['(constitue|constitution) .* reserve de recrutement'
                        ,'examen de recrutement']]
        ,['communiqué d\'etat',
                        ['communique d\'?etat']]
        ,['consulats étrangers',
                        ['consulats etrangers']]
        ,['règlement d\'ordre interieur',
                        ['reglement d(\')?ordre interieur']]
        ,['arrêté royal',
                        ['arrete royal'
                        ,'par arretes royaux']]
        ,['arrêté ministeriel',
                        ['arretes? ministeriels?']]
        ,['demande de concession',
                        ['demande de concession']]
        ,['décision ministerielle',
                        ['decision (de la|du) ministre']]
        ,['accord de cooperation',
                        ['accord de cooperation']]
        ,['modification de la constitution',
                        ['modification a la constitution'
                        ,'revision de la constitution']]
        ,['arrêté d\'application',
                        ['arrete d\'application']]
        ,['règlement spécial',
                        ['reglement special']]
        ,['décret spécial',
                        ['decret special']]
        ,['décret-programme',
                        ['decret-programme']]
        ,['décret',
                        ['decret']]
        ,['loi-programme',
                        ['loi-programme']]
        ,['arrêté-loi',
                        ['arrete-?loi']]
        ,['changement d\'adresse',
                        ['changement dadresse']]
        ,['arrêt',
                        ['arret']]
        ,['ordonnance',
                        ['ordonnance']]
        ,['rapport',
                        ['rapport']]
        ,['circulaire',
                        ['circulaire']]
        ,['loi',
                        ['loi']]
        ,['nominations',
                        ['personnel.*nominations?']]
        ,['avis',
                        ['avis']]
        ,['jugement',
                        ['jugement']]
        ,['avenant',
                        ['avenant']]
        ,['règlement',
                        ['reglement']]
        ,['ratification',
                        ['ratification']]
        ,['adhésion',
                        ['adhesion']]
        ,['autorisation',
                        ['autorisation']]
        ,['liste',
                        ['liste des']]
        ,['protocole',
                        ['protocole']]
        ,['vacance d\'emploi',
                        ['vacance(s) d\'emploi(s)']]
        ,['communication',
                        ['communication']]
        ,['recrutement',
                        ['recrutement']]
        ,['plan de secteur',
                        ['plan de secteur']]
        ,['remise de lettres de créance',
                        ['remise de lettres de creance']]
        ,['composition',
                        ['composition']]
        ,['journal officiel des communautés européennes',
                        ['journal officiel des communautes europeennes']]
        ,['indices du prix',
                        ['indices du prix']]
        ,['publication de l\'ordre judiciaire', []],
        ,['document concernant l\'ordre judiciaire', []]
        ,['document concernant les services judiciaires', []]
        ,['nomination par arrete royal', []]
        ],
    nl => [
        ['erratum',
                        ['errat(um|a)']]
        ,['proces-verbaal',
                        ['proces-verbaal']]
        ,['arrest van de raad van state',
                        ['raad van state.? (- )?vernietiging'
                        ,'vernietiging door de raad van state',
                        ,'raad van state.? (- )?arrest'
                        ,'raad van state (- )?gedeeltelijke schorsing'
                        ,'raad van state.? (- )?schorsing']]
        ,['arrest van het grondwettelijk hof',
                        ['arrest .*(- )?prejudiciele vraa?g(en)?'
                        ,'arrest .*(- )?beroep(en)? tot gedeeltelijke vernietiging'
                        ,'arrest .*(- )?beroep(en)? tot vernietiging'
                        ,'arrest .*(- )?vordering(en)? tot schorsing']]
        ,['beschikking van het hof van beroep',
                        ['hof van beroep .*(- )?beschikking']]
        ,['beschikking van de rechtbank van koophandel',
                        ['rechtbank van koophandel .*(- )?beschikking']]
        ,['beschikking van de arbeidsrechtbank',
                        ['arbeidsrechtbank .*(- )?beschikking']]
        ,['besluit van de franse gemeenschapscommissie',
                        ['besluit.*van het college.*franse gemeenschapscommissie']]
        ,['besluit van de regering van de duitstalige gemeenschap',
                        ['besluit van de regering van de duitstalige gemeenschap']]
        ,['besluit van de gemeenschappelijke gemeenschapscommissie',
                        ['besluit.*van.*gemeenschappelijke gemeenschapscommissie']]
        ,['besluit van de vlaamse regering',
    		            ['besluit van de vlaamse regering']]
        ,['besluit van de waalse regering',
                        ['besluit van de waalse regering']]
        ,['besluit van de brusselse hoofdstedelijke regering',
                        ['besluit van de brusselse hoofdstedelijke regering']]
        ,['besluit van de regering van de franse gemeenschap',
                        ['besluit (van de regering )?van de franse gemeenschap']]
        ,['gecoordineerde omzendbrief',
                        ['gecoordineerde omzendbrief']]
        ,['besluiten betreffende de leden van de paritaire comites',
                        ['besluiten betreffende de leden .* paritaire comites']]
        ,['toepassingsreglement',
                        ['toepassingsreglement']]
        ,['besluit van de commissie voor het bank- en financiewezen',
                        ['besluit van de commissie .*financiewezen']]
        ,['beschikking van het comite van ministers van de benelux economische unie',
                        ['beschikking van het comite van ministers van de benelux economische unie']]
        ,['publicatie(s) ter kritiek',
                        ['publicatie(s)? ter kritiek']]
        ,['internationale overeenkomst',
                        ['overeenkomst tussen het koninkrijk'
                        ,'overeenkomst tussen belgie'
                        ,'internationa(al|le) verdrag']]
        ,['agenda van de plenaire vergaderingen',
                        ['plenaire vergadering(en)? agenda']]
        ,['erkenning',
                        ['erkenning van een deskundige'
                        ,'erkenning.*deskundigen'
                        ,'erkenning(en)? als'
                        ,'erkenning.*scholen'
                        ,'erkenning.*laboratori(a|um)']]
        ,['registratie',
                        ['registratie als' ]]
        ,['goedkeuringen',
                        ['akten? tot goedkeuring']]
        ,['gerechtelijk wetboek',
                        ['gerechtelijk wetboek']]
        ,['strafwetboek',
                        ['strafwetboek']]
        ,['wetboek van vennootschappen',
                        ['wetboek van vennootschappen']]
        ,['burgerlijk wetboek',
                        ['burgerlijk wetboek']]
        ,['samenstelling wervingsreserve',
                        ['wervings?reserve'
                        ,'aanleggen van een personeelsreserve'
                        ,'wervingsexamen']]
        ,['mededeling van de Staat',
                        ,['mededeling van de staat']]
        ,['buitenlandse consulaten',
                        ,['buitenlandse consulaten']]
        ,['huishoudelijk reglement',
                        ['huishoudelijk reglement']]
        ,['koninklijk besluit',
                        ['koninklijk besluit'
                        ,'bij koninklijke besluiten']]
        ,['ministerieel besluit',
                        ['ministeriee?le? besluit(en)?']]
        ,['concessieaanvraag',
                        ['concessieaanvraag']]
        ,['ministeriele beslissing',
                        ['beslissing van de minister']]
        ,['samenwerkingsakkoord',
                        ['samenwerkingsakkoord']]
        ,['wijziging aan de grondwet',
                        ['wijziging aan de grondwet'
                        ,'herziening van de grondwet']]
        ,['toepassingsbesluit',
                        ['toepassingsbesluit']]
        ,['bijzonder reglement',
                        ['bijzonder reglement']]
        ,['bijzonder decreet',
                        ['bijzonder decreet']]
        ,['programmadecreet',
                        ['programmadecreet']]
        ,['decreet',
                        ['decreet']]
        ,['programmawet',
                        ['programmawet']]
        ,['besluitwet',
                        ['besluitwet']]
        ,['adreswijziging',
                        ['adreswijziging']]
        ,['arrest',
                        ['arrest']]
        ,['beschikking',
                        ['ordonnantie','beschikking']]
        ,['verslag',
                        ['verslag']]
        ,['omzendbrief',
                        ['omzendbrief|circulaire']]
        ,['wet',
                        ['wet']]
        ,['benoemingen',
                        ['personeel.*benoeming(en)?']]
        ,['bericht',
                        ['bericht','advies']]
        ,['vonnis',
                        ['vonnis']]
        ,['bijakte',
                        ['bijakte']]
        ,['overeenkomst',
                        ['overeenkomst']]
        ,['bekrachtiging',
                        ['bekrachtiging']]
        ,['toetreding',
                        ['toetreding']]
        ,['vergunning',
                        ['verordening','vergunning']]
        ,['lijst',
                        ['lijst van']]
        ,['protocol',
                        ['protocol']]
        ,['vacante bettreking',
                        ['vacante bettreking(en)']]
        ,['mededeling',
                        ['mededeling']]
        ,['aanwerving',
                        ['(aan)?werving']]
        ,['gewestplan',
                        ['gewestplan']]
        ,['overhandiging van geloofsbrieven',
                        ['overhandiging van geloofsbrieven']]
        ,['samenstelling',
                        ['samenstelling']]
        ,['publicatieblad van de europese gemeenschappen',
                        ['publicatieblad van de europese gemeenschappen']]
        ,['indexcijfers',
                        ['indexcijfers']]
        ,['bekendmaking van de juridische orde', []]
        ,['document betreffende de rechterlijke ordre', []]
        ,['document betreffende de gerechtelijke diensten', []]
        ,['benoeming door koninklijk besluit', []]
        ]
    );


sub new
    {
    my $class= shift;

    my $content = shift;
	my $numac = shift;
	my $pub_date = shift;
	my $lang    = shift;
    my $dict    = shift;
	my $raw_content = $content;

    $content = unac_string("UTF-8",$raw_content);
    $content = lc($content);
	$content =~ s/<\/?sup>//ig;
	$content =~ s/ministrieel/ministerieel/g;

    my $self = {
        _content => $content,
		_raw	=> $raw_content,
		_numac  => $numac,
		_pub_date => $pub_date,
		_lang => $lang,
        _dict => $dict
        };
    bless $self, $class;
    return $self;
    }


## Getters
sub getPromDate
    {
    my ($self) = @_;
    my %months = (
        janvier=>1,fevrier=>2,mars=>3,avril=>4,mai=>5,juin=>6,juillet=>7,aout=>8,septembre=>9,octobre=>10,novembre=>11,decembre=>12,
        januari=>1,februari=>2,maart=>3,april=>4,mei=>5,juni=>6,juli=>7,augustus=>8,september=>9,oktober=>10,november=>11,december=>12
        );

    if ($self->{_content} =~ m/<h3><center><u>\s(\d{1,2})\s(\w{1,})\s(\d{4})\./i)
        {
        if (exists($months{$2}))
            {
            return $3."-".$months{$2}."-".$1;
            }
        }

    return '--';
    }

sub getType
    {
    my ($self,$debug) = @_;
    # $debug = 1;
    my $dict = $self->{"_dict"};  
    my $title = normalize($self->getTitle());
    $title =~ s/\s{2,}/ /;

	if ($title =~ m/^\s*$/i)
        {
        print "     NO TITLE   default title given to $self->{_numac}\n" if $debug;
        return "document";
        }
 
    ## if it's just a vacancy
    if ($title =~ m/vacante betrekking(en)?|openstaande plaats|openstaande betrekking|vacature|oproep.*kandidaten|te begeven betrekkingen|recrutering/
       or $title =~ m/places? vacantes?|emplois? vacants?|vacance.+emploi|vacance.+mandat|appel aux candidats|emplois a conferer|mandat vacant/)
       {
       return 'vacance d\'emploi' if ($self->{_lang} eq "fr");
       return 'vacante bettreking';
       }
    ## it's a benoeming
    if ($title =~ m/benoeming(en)?.*bij +koninklijke? +besluit(en)?/
       or $title =~ m/nominations?.*par +arretes? +roya(l|ux)/)
       {
       return 'benoeming door koninklijk besluit' if ($self->{_lang} eq "nl");
       return 'nomination par arrete royal';
       }


    my $lang = $self->{_lang};
    my $docmask = "";
    my %item;
    my $item;
    while( my ($sourcemask, $values) = each %SOURCE_TYPES)
        {
        if ($self->getSource() =~ m/^\s*$sourcemask\s*$/)
            {
            print "match source \'$self->{_numac}\' ".$self->getSource()." matches source $sourcemask\n" if $debug;
            foreach my $item ( @{$values})
                {
                $docmask = $item->{mask};
                if ($title =~ m/\W$docmask\W|^$docmask\W/)
                    {
                    return $item->{$lang};
                    }
                }
            }
        }

    my $term = "";
    for my $termdef (@{$TYPES{$lang}})
        {
        $term  = @{$termdef}[0];
        for my $mask (@{@{$termdef}[1]})
            {
            if ($title =~ m/^$mask|^(\s*(\d+\/){2}\d+\s*\.?|\s*\d+(er)?\s+\w+\s+\d+\s*\.?|[^\.]*\.\s*|\s*)-?\s*[^a-z]{0,}$mask/i)
                {
                return $term;
                }
            }
        }
      
    ########### Begin checks with autocorrect, simple, then complex
    my ($title_begin) = ($title =~ /(([^\s]*\s*){4,14})/);
    $title_begin =~ s/[^a-z\s]//g;
    $title_begin =~ s/\s{2,}/ /g;
    my @title_words = split(/\s+/,$title_begin);

    my $title_corrected = "";
    foreach my $w (@title_words)
        {
        $title_corrected .= $dict->correct_short($w)." " if ($w =~ /[a-z]+/g);
        }

    for my $termdef (@{$TYPES{$lang}})
        {
        $term  = @{$termdef}[0];
        for my $mask (@{@{$termdef}[1]})
            {
            if ($title_corrected =~ m/\W$mask\W/i)
                {
                print "    SHORT_COR   $self->{_numac} found \"$mask\" in short corrected way\n" if $debug;
                print "                Title : ".substr($title,0,90)."\n" if $debug;
                return $term;
                }
            }
        }
    
    # Search any type, anywhere in title
    for my $termdef (@{$TYPES{$lang}})
        {
        $term  = @{$termdef}[0];
        for my $mask (@{@{$termdef}[1]})
            {
            if ($title =~ m/\W$mask\W/i)
                {
                print "       LONGF   $self->{_numac} found \"$term\" in long way\n" if $debug;
                print "                Title : ".substr($title,0,90)."\n" if $debug;
                return $term;
                }
            }
        }

    print "      NO TYPE   No type found for $self->{_numac} in lang $self->{_lang}\n" if $debug;
    print "                Content : ".substr($title,0,90)."\n" if $debug;
    print "                Source  : ".$self->getSource()."\n" if $debug;
    print "                Url     : ".$self->makeUrl()."\n" if $debug;
    return "document";
    }

sub getSource
    {
    my ($self) = @_;
    if ($self->{_content} =~ m/no article available with such references/i)
        {
		return "nosource";
        }

    if ($self->{_content} =~ m/^<font>([^<]+)<\/font><\/td><\/tr>/im)
		{
        return $1;
		}
    return "nosource";
    }

sub getEli
    {
    # example : http://www.ejustice.just.fgov.be/eli/arrete/2000/03/29/2000022322/moniteur
    my ($self) = @_;
    if ($self->{_content} =~ m/no article available with such references/i)
        {
		return "noeli";
        }

    if ($self->{_content} =~ m/(\/eli\/[a-zAz_0-9\/\-\.\_]+)/im)
		{
        return $1;
		}
    return "noeli";
    }

sub getPdf
    {
    # example : <INPUT type=hidden name=urlpdf value="/mopdf/2000/04/19_1.pdf#Page2   ">
    my ($self) = @_;
    if ($self->{_content} =~ m/no article available with such references/i)
        {
		return "nopdf";
        }

    if ($self->{_content} =~ m/name=urlpdf value="([^"]+)"/im)
		{
        return $1 =~ s/^\s+|\s+$//rg;;
		}
    return "nopdf";
    }

sub getChrono
    {
    # example : <a href="http://reflex.raadvst-consetat.be/reflex/?page=chrono&c=detail_get&d=detail&docid=66713&tab=chrono" target=_blank > 
    my ($self) = @_;

    if ($self->{_content} =~ m/no article available with such references/i)
        {
		return "nochrono";
        }

    if ($self->{_content} =~ m/"(http:\/\/reflex.raadvst-consetat.be\/[^"]+)"/im)
		{
        return $1 =~ s/^\s+|\s+$//rg;;
		}

    return "nochrono";
    }

sub getTitle
	{
	my ($self) = @_;
	my $text = $self->{_raw};
	my $final_title= '';

	if ($text =~ m/<h3><center><u>(([^\.]+\.)?(\s\-)?\s?(?<title>.+?)|\s*)<\/u><\/center><\/h3><br>\s*(?<sub_title>[\s\S]{0,250})[^<]*<br>(?<text_begin>[\s\S]{0,100})/im)
		{
		my $title 		= $+{title};
		my $sub_title = $+{sub_title};
		my $text_begin = $+{text_begin};
		if ($title !~ m/^\s*$/)	
			{
			$final_title = $title;
			}
		elsif ($sub_title !~ m/^\s*$/)
			{
			my $hs = HTML::Strip->new();
			$final_title = $hs->parse($sub_title.'(...) '.$text_begin.'(...)');
			$hs->eof;
			}
		}

	if ($text =~ m/SSFetch Text failed:/im)
		{
		print "TITLE           SSFetch Failed for ".$self->{_numac}." in lang ".$self->{_lang}."\n";
		}
	elsif ($final_title eq '' and
	    $text !~ m/No article available with such references/im)
		{
		print "TITLE           could not match title for ".$self->{_numac}." in lang ".$self->{_lang}."\n";
		$final_title = '';
		}

	$final_title =~ s/\([^\)]+\)//i;
	return $final_title;
	}

sub makeUrl
	{
	my ($self) = @_;
	my $url = "http://www.ejustice.just.fgov.be/cgi/article_body.pl?language=fr&caller=summary&pub_date=$self->{_pub_date}&numac=$self->{_numac}";
	return $url;
	}

sub typeIndex
    {
    my ($self, $value) = @_;
    my @list = ();
    return 0 if ($value eq 'document');
    my $lang = $self->{_lang};
    my $i = 0;

    for my $termdef (@{$TYPES{$lang}})
        {
        my $term  = @{$termdef}[0];
        return $i+1 if ($value eq $term);
        $i++;
        }
    return 0;
    }

sub getTypeByIndex
    {
    my ($self,$index) = @_;
    return 'document' if ($index == 100);
    $index--;
    my $lang = $self->{_lang};
    my $i = 0;
    for my $termdef (@{$TYPES{$lang}})
        {
        my $term  = @{$termdef}[0];
        return $term if ($i == $index);
        $i++;
        }
    }


sub cleanType
    {
    my $type = shift;
    $type =~ s/[^a-z\-' ]//g;
    return $type;

}

sub normalize
    {
    my $txt = shift;
	return '' if ($txt eq '');

    my $txt_utf8 = Encode::is_utf8($txt) ? $txt : encode("UTF-8",$txt);
    if (unac_string("UTF-8", $txt_utf8))
        {
        $txt_utf8 = unac_string("UTF-8", $txt_utf8);
        }
    else
        {
        print "Following string can't unaccent:\n\t" . $txt ."\n\n\n";
        die
        }
    $txt = decode_utf8($txt_utf8);
    
    $txt = lc($txt);
    $txt =~ s/\s/ /g;
    $txt =~ s/[^A-Za-z0-9\.;:\- ]//g;
    return $txt;
    }

1;
