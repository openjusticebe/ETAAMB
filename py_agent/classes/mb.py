from datetime import datetime
import time
import requests
from bs4 import BeautifulSoup
import re

class obj:
    def __init__(self, logger, dateobj):
        self.numac_mask = re.compile(r'^\d{10}$')
        self.soup = None
        self.logger = logger
        self.date = None
        self.content = None
        self.date = dateobj
        self.current_edition = 0

    def has_editions(self):
        if not self.content:
            self.update()
        return bool(self.content.find('div', class_='editions').find(class_="button_small"))

    def numacs(self):
        return [
                a.get_text(strip=True) for a in self.content.find_all("a", href=lambda href: href and "article.pl" in href)
                if self.numac_mask.match(a.get_text(strip=True))
                ]

    def editions(self):
        try:
            last_num = soup.select_one("div.editions .button__small:last-child").get_text()
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

    def update(self, edition = 1):
        r = self.query(edition)
        self.content = BeautifulSoup(r.text, "html.parser")
        self.current_edition = edition
        self.logger.info("Updated edition %s successfully", edition)

    def query(self, edition):
        headers = {'User-Agent': 'etaamb scraper'}
        loops = 1
        while True:
            r = requests.get(
                    f"{ROOT_DOMAIN}/{SERVICE_DIR}",
                    params=self.get_params(edition),
                    headers=headers
                    )
            if r.status_code == 200:
                return r
            else:
                loops += 1
                time.sleep(loops/10)
                if loops > 10:
                    raise RuntimeError("Failed to query resource")
