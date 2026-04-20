"""
parse/commands.py — Python port of parseRaws.pl.

Uses asyncio + ThreadPoolExecutor so each concurrent worker gets its own DB
connection (mirrors Perl's Parallel::ForkManager fork-per-doc model).
aiohttp is NOT used here; all I/O is DB-bound and runs in threads.
"""

import asyncio
import re
import click
import logging
from concurrent.futures import ThreadPoolExecutor
from bs4 import BeautifulSoup

from classes import db
from .page import Page, normalize_text

logger = logging.getLogger(__name__)


# ── Data extraction ────────────────────────────────────────────────────────────

def _make_data_object(raw_fr, raw_nl, numac, pub_date):
    """Build the parsed data dict for one document (mirrors makeDataObject)."""
    plain_fr = BeautifulSoup(raw_fr, 'html.parser').get_text()
    plain_nl = BeautifulSoup(raw_nl, 'html.parser').get_text()

    page_fr = Page(raw_fr, numac, pub_date, 'fr')
    page_nl = Page(raw_nl, numac, pub_date, 'nl')

    # Promulgation date: prefer FR, fall back to NL, default to null date
    prom_fr = page_fr.get_prom_date()
    prom_nl = page_nl.get_prom_date()
    if prom_fr != '--':
        prom_date = prom_fr
    elif prom_nl != '--':
        prom_date = prom_nl
    else:
        prom_date = '0000-00-00'

    raw_title_fr   = page_fr.get_title()
    raw_title_nl   = page_nl.get_title()
    type_fr        = page_fr.get_type()
    type_nl        = page_nl.get_type()

    # Cross-language type reconciliation: pick the more specific classification
    nl_idx = page_nl.type_index(type_nl)
    fr_idx = page_fr.type_index(type_fr)
    if nl_idx != fr_idx and raw_title_fr and raw_title_nl:
        if (fr_idx < nl_idx and fr_idx != 0) or nl_idx == 0:
            new_nl = page_nl.get_type_by_index(fr_idx)
            new_fr = type_fr
        else:
            new_fr = page_fr.get_type_by_index(nl_idx)
            new_nl = type_nl
        logger.info(
            "TYPE CORR %s || NL(%s):%s => %s, FR(%s):%s => %s",
            numac, nl_idx, type_nl, new_nl, fr_idx, type_fr, new_fr,
        )
        type_fr, type_nl = new_fr, new_nl

    if type_fr == type_nl == 'document':
        logger.debug("DOCUMENT  %s classified as generic document", numac)

    norm_title_fr = normalize_text(raw_title_fr)
    norm_title_nl = normalize_text(raw_title_nl)

    # Anonymisation flag
    _anon_fr = r'(loi accordant des naturalisations|relative aux noms et pr|reserve de recrutement|article 770|exercer la profession de detective prive|recrutement|etrangers)'
    _anon_nl = r'(betreffende de namen en voornamen|wet die naturalisaties verleent|samenstelling van een wervingreserve|artikel 770|het beroep van prive|aanwerving|vreemdelingen)'
    anonymise = bool(
        re.search(_anon_fr, norm_title_fr, re.I) or
        re.search(_anon_nl, norm_title_nl, re.I)
    )
    if anonymise:
        logger.info("ANON      %s anonymise bit will be set", numac)

    chamber_id, chamber_leg = page_fr.get_chamber_data()
    senate_id,  senate_leg  = page_fr.get_senate_data()

    return {
        'numac':        numac,
        'pub_date':     pub_date,
        'prom_date':    prom_date,
        'raw_fr':       plain_fr,
        'norm_fr':      normalize_text(plain_fr),
        'raw_nl':       plain_nl,
        'norm_nl':      normalize_text(plain_nl),
        'raw_title_fr': raw_title_fr,
        'norm_title_fr':norm_title_fr,
        'raw_title_nl': raw_title_nl,
        'norm_title_nl':norm_title_nl,
        'type_fr':      type_fr,
        'type_nl':      type_nl,
        'source_fr':    page_fr.get_source(),
        'source_nl':    page_nl.get_source(),
        'chrono':       page_fr.get_chrono(),
        'eli':          page_fr.get_eli(),
        'pdf':          page_fr.get_pdf(),
        'eli_type_fr':  page_fr.get_eli_type(),
        'eli_type_nl':  page_nl.get_eli_type(),
        'chrono_id':    page_fr.get_chrono_data(),
        'chamber_id':   chamber_id,
        'chamber_leg':  chamber_leg,
        'senate_id':    senate_id,
        'senate_leg':   senate_leg,
        'anonymise':    anonymise,
    }


def _store_data(db_obj, data):
    """Persist all parsed data for one document."""
    type_id   = db_obj.get_or_create_type(data['type_nl'],   data['type_fr'])
    source_id = db_obj.get_or_create_source(data['source_nl'], data['source_fr'])

    db_obj.store_doc(data, type_id, source_id)
    db_obj.store_text(data['numac'], 'fr', data['raw_fr'], data['norm_fr'])
    db_obj.store_text(data['numac'], 'nl', data['raw_nl'], data['norm_nl'])

    if data['raw_title_fr']:
        db_obj.store_title(data['numac'], 'fr', data['raw_title_fr'], data['norm_title_fr'])
        db_obj.add_doc_language(data['numac'], 'fr')

    if data['raw_title_nl']:
        db_obj.store_title(data['numac'], 'nl', data['raw_title_nl'], data['norm_title_nl'])
        db_obj.add_doc_language(data['numac'], 'nl')

    db_obj.store_doc_links(
        data['numac'],
        data.get('chrono'), data.get('eli'), data.get('pdf'),
    )


# ── Per-document worker (runs in thread pool) ──────────────────────────────────

def _process_one(config, numac, dry_run):
    """Fetch raw pages from DB, parse, and store results.
    Each call creates its own DB connection — safe for ThreadPoolExecutor."""
    thread_db = db.obj(config)
    raw = thread_db.get_raw_page(numac)
    if not raw:
        logger.warning("No raw data found for %s", numac)
        return False

    try:
        data = _make_data_object(
            raw['raw_fr'], raw['raw_nl'],
            numac, str(raw['pub_date']),
        )
    except Exception as exc:
        logger.error("Parse failed for %s: %s", numac, exc)
        return False

    if not dry_run:
        _store_data(thread_db, data)

    logger.info("Parsed %s (type FR: %s / NL: %s)", numac, data['type_fr'], data['type_nl'])
    return True


# ── Async runner ───────────────────────────────────────────────────────────────

async def _run(numacs, concurrency, config, dry_run):
    sem  = asyncio.Semaphore(concurrency)
    loop = asyncio.get_event_loop()

    async def bounded(numac):
        async with sem:
            return await loop.run_in_executor(None, _process_one, config, numac, dry_run)

    results = await asyncio.gather(*[bounded(n) for n in numacs], return_exceptions=True)

    errors = [r for r in results if isinstance(r, Exception)]
    for exc in errors:
        logger.error("Worker exception: %s", exc)

    return sum(1 for r in results if r is True)


# ── CLI command ────────────────────────────────────────────────────────────────

@click.command()
@click.option('-r', '--dry-run',     is_flag=True, default=False,
              help='Parse documents but do not write to DB.')
@click.option('-n', '--numac',       'numacs', multiple=True,
              help='Target a specific numac (repeatable: -n X -n Y).')
@click.option('-l', '--limit',       default=1000, show_default=True,
              help='Max documents to parse per run.')
@click.option('-f', '--force',       is_flag=True, default=False,
              help='Re-parse docs whose version already matches (skip version check).')
@click.option('-c', '--concurrency', default=4,    show_default=True,
              help='Number of parallel parse workers.')
def parse_raws(dry_run, numacs, limit, force, concurrency):
    """Parse stored raw pages and populate docs/text/titles tables."""
    config = db.get_config()
    db_obj = db.obj(config)

    if numacs:
        to_process = list(numacs)
    else:
        to_process = db_obj.get_numacs_to_parse(limit=limit, force=force)

    click.echo(
        f"Parsing {len(to_process)} document(s) "
        f"[dry-run={dry_run}, force={force}, concurrency={concurrency}]"
    )
    if not to_process:
        click.echo("Nothing to parse.")
        return

    done = asyncio.run(_run(to_process, concurrency, config, dry_run))
    click.echo(f"Done — {done}/{len(to_process)} parsed successfully.")
