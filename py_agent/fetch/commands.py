import asyncio
import random
import click
import aiohttp
import logging
from classes import db

logger = logging.getLogger(__name__)

_ROOT = 'https://www.ejustice.just.fgov.be'
_HEADERS = {'User-Agent': 'etaamb-agent/2.0'}
_TIMEOUT = aiohttp.ClientTimeout(total=15)


# ── HTTP helpers ───────────────────────────────────────────────────────────────

def _article_url(numac, pub_date, lang):
    lg_txt = 'f' if lang == 'fr' else 'n'
    return (
        f"{_ROOT}/cgi/article.pl"
        f"?language={lang}&sum_date={pub_date}"
        f"&lg_txt={lg_txt}&caller=sum&numac_search={numac}&view_numac="
    )


async def _fetch(session, url):
    async with session.get(url, headers=_HEADERS, timeout=_TIMEOUT) as resp:
        resp.raise_for_status()
        return await resp.text()


# ── Per-document coroutine ─────────────────────────────────────────────────────

async def _process(session, sem, numac, pub_date, sleep_min, sleep_max,
                   dry_run, force, db_obj):
    async with sem:
        logger.info("Fetching %s (%s)…", numac, pub_date)
        try:
            page_fr, page_nl = await asyncio.gather(
                _fetch(session, _article_url(numac, pub_date, 'fr')),
                _fetch(session, _article_url(numac, pub_date, 'nl')),
            )
        except Exception as exc:
            logger.error("Failed to fetch %s: %s", numac, exc)
            return False

        if not dry_run:
            db_obj.store_raw_page(numac, pub_date, page_fr, page_nl, force=force)

        await asyncio.sleep(random.randint(sleep_min, sleep_max) / 1000)
        return True


# ── Async runner ───────────────────────────────────────────────────────────────

async def _run(rows, concurrency, sleep_min, sleep_max, dry_run, force, db_obj):
    sem = asyncio.Semaphore(concurrency)
    async with aiohttp.ClientSession() as session:
        tasks = [
            _process(session, sem, row['doc_id'], str(row['date']),
                     sleep_min, sleep_max, dry_run, force, db_obj)
            for row in rows
        ]
        results = await asyncio.gather(*tasks)
    return sum(1 for r in results if r)


# ── CLI command ────────────────────────────────────────────────────────────────

@click.command()
@click.option('-r', '--dry-run',     is_flag=True, default=False,
              help='Fetch pages but do not write to DB.')
@click.option('-n', '--numac',       'numacs', multiple=True,
              help='Target a specific numac (repeatable: -n X -n Y).')
@click.option('-l', '--limit',       default=250,  show_default=True,
              help='Max documents to fetch per run.')
@click.option('-f', '--force',       is_flag=True, default=False,
              help='Re-fetch and overwrite pages already stored.')
@click.option('-c', '--concurrency', default=5,    show_default=True,
              help='Number of concurrent HTTP requests.')
@click.option('--sleep-min',         default=30,   show_default=True,
              help='Minimum polite sleep between requests (ms).')
@click.option('--sleep-max',         default=80,   show_default=True,
              help='Maximum polite sleep between requests (ms).')
def fetch_raws(dry_run, numacs, limit, force, concurrency, sleep_min, sleep_max):
    """Fetch raw FR/NL article pages from ejustice and store them."""
    db_obj = db.obj(db.get_config())

    if numacs:
        rows = db_obj.get_numac_dates(list(numacs))
        if not rows:
            click.echo("None of the provided numacs were found in raw_ids.")
            return
        missing = set(numacs) - {str(r['doc_id']) for r in rows}
        if missing:
            click.echo(f"\tWarning: not found in raw_ids: {', '.join(missing)}")
    else:
        rows = db_obj.get_numacs_to_fetch(limit=limit, force=force)

    click.echo(
        f"Fetching {len(rows)} document(s) "
        f"[dry-run={dry_run}, force={force}, concurrency={concurrency}]"
    )
    if not rows:
        click.echo("Nothing to fetch.")
        return

    done = asyncio.run(_run(rows, concurrency, sleep_min, sleep_max, dry_run, force, db_obj))
    click.echo(f"Done — {done}/{len(rows)} fetched successfully.")
