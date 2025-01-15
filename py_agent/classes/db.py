import os
import pymysql
import logging
logger = logging.getLogger(__name__)

def get_config():
    return {
            'DB_HOST': os.getenv('DB_HOST'),
            'DB_PORT': int(os.getenv('DB_PORT')),
            'DB_USER': os.getenv('DB_USER'),
            'DB_PASSWORD': os.getenv('DB_PASSWORD'),
            'DB_DATA': os.getenv('DB_DATA'),
    }

class obj:

    def __init__(self, config):
        self.config = config
        self.conn = None;

    def test(self):
        try:
            self.query('SELECT COUNT(*) FROM done_dates')
            return True;
        except Exception as e:
            logger.exception(e)
            return False;

    def store_numacs(self, numacs, date_obj):
        self.ensure()
        with self.conn.cursor() as cursor:
            sql = """
            INSERT INTO `raw_ids` (`doc_id`, `date`, `version`) values (%s, %s, %s)
                ON DUPLICATE KEY UPDATE `id` = `id`
            """
            date_str = f"{date_obj['year']}-{date_obj['month']:02}-{date_obj['day']:02}"
            for numac in numacs:
                cursor.execute(sql, (numac, date_str, 2))

    def ensure(self):
        if not self.conn:
            self.connect()

    def query(self, q):
        self.ensure()
        with self.conn.cursor() as cursor:
            cursor.execute(q)
            res = cursor.fetchall()
        return res

    def connect(self):
        self.conn = pymysql.connect(
            host=self.config['DB_HOST'],
            port=self.config['DB_PORT'],
            user=self.config['DB_USER'],
            password=self.config['DB_PASSWORD'],
            database=self.config['DB_DATA'],
            cursorclass=pymysql.cursors.DictCursor
        )
