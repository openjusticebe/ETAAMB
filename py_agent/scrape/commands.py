import click
from classes import mb


@click.command()
def version():
    """Display the current version."""
    click.echo("Salut !")

@click.command()
def last_date():
    """Return the date of the last MB edition"""
    mb_obj = mb.obj()
    last_date = mb_obj.last_date()
    ld_str = f"{last_date['day']}/{last_date['month']}/{last_date['year']}"
    mb_obj.set_date(last_date)
    if mb_obj.has_editions():
        editions = mb_obj.editions()
    else:
        editions = 'N/A'
    click.echo(f"Last MB date: {ld_str} (ediions: {editions})")
