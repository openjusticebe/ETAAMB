import click
import os
from classes import db

@click.command()
def config():
    """Print debug parameters"""
    click.echo(f"Current DB Config: {db.get_config()}" )

@click.command()
def test_db():
    """Quick test DB availability"""
    db_obj = db.obj(db.get_config())
    r = db_obj.query('SELECT COUNT(*) as cnt FROM done_dates')
    click.echo(f"Found {r[0]['cnt']} done dates in DB")

