from datetime import datetime
import time
import requests
from bs4 import BeautifulSoup
import re

import logging
logger = logging.getLogger(__name__)

ROOT_DOMAIN = 'https://www.ejustice.just.fgov.be'
SERVICE_DIR = 'cgi/summary.pl'

class obj:
    # A bit of a clumsy class to extract data from MB

    def __init__(self, dateobj=None):
        self.numac_mask = re.compile(r'^\d{10}$')
        self.soup = None
        self.content = None
        self.date = dateobj
        self.current_edition = 0

    def has_editions(self):
        if not self.content:
            self.update()
        return bool(self.content.find('div', class_='editions').find(class_="button__small"))

    def numacs(self):
        return [
                a.get_text(strip=True) for a in self.content.find_all("a", href=lambda href: href and "article.pl" in href)
                if self.numac_mask.match(a.get_text(strip=True))
                ]

    def set_date(self, dateobj):
        self.date = dateobj
        self.content = None
        self.soup = None

    def editions(self):
        try:
            last_num = self.content.select_one("div.editions .button__small:last-child").get_text()
            if last_num.isdigit():
                return int(last_num)
            logger.warn("Last edition number not a digit, returning default (1)")
            return 1
        except Exception as e:
            logger.error("failed to find edition number, returning default (1)")
            logger.exception(e)
            return 1

    def load_edition(self, edition):
        if self.current_edition == edition:
            return True;
        self.update(edition)

    def get_params(self, edition):
        date_obj = datetime(self.date['year'], self.date['month'], self.date['day'])
        return {
                'language': 'nl',
                'sum_date': date_obj.strftime("%Y-%m-%d"),
                's_editie': edition
                }

    def get_numacs(self):
        numacs = []
        editions = 0
        if self.has_editions():
            editions = self.editions()
            for edition in range(1, editions + 1):
                self.load_edition(edition)
                ed_numacs = self.numacs()
                numacs.extend(ed_numacs)
        return numacs

    def update(self, edition = 1):
        params = self.get_params(edition)
        r = self.query(SERVICE_DIR, params)
        self.content = BeautifulSoup(r.text, "html.parser")
        self.current_edition = edition
        logger.info("Updated edition %s successfully", edition)

    def last_date(self):
        params={
            'language': 'nl',
            'view_numac': ''
        }
        r = self.query(SERVICE_DIR, params)
        self.content = BeautifulSoup(r.text, "html.parser")
        active_lang = self.content.find("a", class_="nav__language-button active")
        date_obj = {'day':'', 'month':'', 'year':''}

        if active_lang and active_lang.has_attr("href"):
            href = active_lang["href"]
            logger.info("Found active ling %s", href)
            try:
                return self.str2date(href)
            except RuntimeError:
                logger.error("No active date found")
        return date_obj

    def today(self):
        # Why not simple use datetime for everything..
        t = datetime.today()
        return self.dt2date(t)

    def str2date(self, datestr):
        date_match = re.search(r"(\d{4})-(\d{2})-(\d{2})", datestr)
        if date_match:
            year, month, day = date_match.groups()
            date_obj = {
                'year': int(year),
                'month': int(month),
                'day' : int(day)
            }
            return date_obj
        raise RuntimeError(f"Date format not valid (received {datestr})")

    def dt2date(self, dt):
        return {
            'year': dt.year,
            'month': dt.month,
            'day': dt.day
        }

    def query(self, path, params):
        headers = {'User-Agent': 'etaamb scraper'}
        loops = 1
        while True:
            r = requests.get(
                    f"{ROOT_DOMAIN}/{path}",
                    params=params,
                    headers=headers
                    )
            if r.status_code == 200:
                return r
            else:
                loops += 1
                time.sleep(loops/10)
                if loops > 10:
                    raise RuntimeError("Failed to query resource")
