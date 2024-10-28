import click
import os
from classes import db

@click.command()
def config():
    """Print debug parameters"""
    click.echo(f"Current DB Config: {db.get_config()}" )
