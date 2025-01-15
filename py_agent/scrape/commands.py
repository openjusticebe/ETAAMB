import click
from classes import mb, db
from datetime import datetime, timedelta

import logging
logger = logging.getLogger(__name__)


@click.command()
def version():
    """Display the current version."""
    click.echo("Salut !")

@click.command()
def last_date():
    """Return the date of the last MB edition"""
    click.echo("Getting last date from MB...")
    mb_obj = mb.obj()
    last_date = mb_obj.last_date()
    ld_str = f"{last_date['day']}/{last_date['month']}/{last_date['year']}"
    mb_obj.set_date(last_date)
    if mb_obj.has_editions():
        editions = mb_obj.editions()
    else:
        editions = 'N/A'
    click.echo(f"\tLast MB date: {ld_str} (ediions: {editions})")


@click.command()
@click.option('-r', '--dry-run', is_flag=True, show_default=True, default= False, help='Dry-run')
@click.option('-d', '--date',  help='Single date (yyyy-mm-dd)')
@click.option('-t', '--today', is_flag=True, show_default=True, default=False, help='Single date (yyyy-mm-dd)')
@click.option('-s', '--start',  help='Start date (yyyy-mm-dd)')
@click.option('-e', '--end',  help='End date (yyyy-mm-dd)')
def get_numacs(dry_run, date, today, start, end):
    """Extract and store numacs"""
    click.echo("Extracting numacs...")
    mb_obj = mb.obj()

    if dry_run:
        # Dry-run: nothing gets written
        click.echo("\tDry-run enabled")
        db_obj = False
    else:
        db_obj = db.obj(db.get_config())

    # Single date specified: Get numacs
    if date:
        date_obj = mb_obj.str2date(date)
        ld_str = f"{date_obj['day']}/{date_obj['month']}/{date_obj['year']}"
        click.echo(f"\tProvided date parsed to {ld_str}")
        mb_obj.set_date(date_obj)

        nms = mb_obj.get_numacs()
        if nms:
            editions = mb_obj.editions()
            click.echo(f"\tAcquired {len(nms)} numacs in {editions} edition(s)")
        else:
            click.echo("\tNo editions found for that date")

        if db_obj and nms:
            db_obj.store_numacs(nms, date_obj)
            click.echo(f"\tDB Updated")


    if today:
        date_obj = mb_obj.today()
        ld_str = f"{date_obj['day']}/{date_obj['month']}/{date_obj['year']}"
        click.echo(f"\tToday's date parsed to {ld_str}")
        mb_obj.set_date(date_obj)
        nms = mb_obj.get_numacs()
        if nms:
            editions = mb_obj.editions()
            click.echo(f"\tAcquired {len(nms)} numacs in {editions} edition(s)")
        else:
            click.echo("\tNo editions found for Today")

        if db_obj and nms:
            db_obj.store_numacs(nms, date_obj)
            click.echo(f"\tDB Updated")

    if start:
        st_d = mb_obj.str2date(start)
        en_d = mb_obj.today() if not end else mb_obj.str2date(end)

        current_date = datetime(st_d['year'], st_d['month'], st_d['day'])
        end_date = datetime(en_d['year'], en_d['month'], en_d['day'])

        while current_date <= end_date:
            date_obj = mb_obj.dt2date(current_date)
            ld_str = f"{date_obj['day']}/{date_obj['month']}/{date_obj['year']}"
            click.echo(f"\tParsing date {ld_str}")
            mb_obj.set_date(date_obj)
            nms = mb_obj.get_numacs()
            if nms:
                editions = mb_obj.editions()
                click.echo(f"\t\tAcquired {len(nms)} numacs in {editions} edition(s)")
            else:
                click.echo("\t\tNo editions found")

            if db_obj and nms:
                db_obj.store_numacs(nms, date_obj)
                click.echo(f"\t\tDB Updated")

            current_date += timedelta(days=1)



