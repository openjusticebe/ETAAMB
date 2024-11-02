import click
from classes import mb

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
def get_numacs(dry_run, date):
    """Extract and store numacs"""
    click.echo("Extracting numacs...")
    mb_obj = mb.obj()
    numacs = []

    if dry_run:
        # Dry-run: nothing gets written
        click.echo("\tDry-run enabled")

    if date:
        # Single date: just get numacs for that date
        date_obj = mb_obj.str2date(date)
        ld_str = f"{date_obj['day']}/{date_obj['month']}/{date_obj['year']}"
        click.echo(f"\tDate parsed to {ld_str}")
        mb_obj.set_date(date_obj)

        editions = 0
        if mb_obj.has_editions():
            editions = mb_obj.editions()
            for edition in range(1, editions + 1):
                mb_obj.load_edition(edition)
                ed_numacs = mb_obj.numacs()
                numacs.extend(ed_numacs)
        else:
            click.echo("\tNo editions found for that date")

        click.echo(f"\tAcquired {len(numacs)} numacs in {editions} edition(s)")

