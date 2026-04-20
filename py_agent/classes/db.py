import os
import pymysql
import logging
logger = logging.getLogger(__name__)

# ── Versioning constants ───────────────────────────────────────────────────────
RAW_VERSION        = 1
RAW_SOURCE_VERSION = '052024'
DOC_VERSION        = 18


def get_config():
    return {
        'DB_HOST':     os.getenv('DB_HOST'),
        'DB_PORT':     int(os.getenv('DB_PORT')),
        'DB_USER':     os.getenv('DB_USER'),
        'DB_PASSWORD': os.getenv('DB_PASSWORD'),
        'DB_DATA':     os.getenv('DB_DATA'),
    }


class obj:

    def __init__(self, config):
        self.config = config
        self.conn = None

    # ── Internals ──────────────────────────────────────────────────────────────

    def ensure(self):
        if not self.conn:
            self.connect()

    def connect(self):
        self.conn = pymysql.connect(
            host=self.config['DB_HOST'],
            port=self.config['DB_PORT'],
            user=self.config['DB_USER'],
            password=self.config['DB_PASSWORD'],
            database=self.config['DB_DATA'],
            cursorclass=pymysql.cursors.DictCursor,
        )

    def query(self, q):
        self.ensure()
        with self.conn.cursor() as cursor:
            cursor.execute(q)
            res = cursor.fetchall()
        return res

    # ── Existing (scrape) ──────────────────────────────────────────────────────

    def test(self):
        try:
            self.query('SELECT COUNT(*) FROM done_dates')
            return True
        except Exception as e:
            logger.exception(e)
            return False

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

    # ── Fetch module ───────────────────────────────────────────────────────────

    def get_numacs_to_fetch(self, limit=250, force=False):
        """Return list of {doc_id, date} dicts that still need raw pages fetched."""
        self.ensure()
        with self.conn.cursor() as cursor:
            if force:
                cursor.execute(
                    "SELECT doc_id, date FROM raw_ids LIMIT %s",
                    (limit,),
                )
            else:
                cursor.execute(
                    """
                    SELECT doc_id, date FROM raw_ids
                    LEFT JOIN raw_pages ON raw_ids.doc_id = raw_pages.numac
                    WHERE raw_pages.version != %s
                    UNION
                    SELECT doc_id, date FROM raw_ids
                    LEFT JOIN raw_pages ON raw_ids.doc_id = raw_pages.numac
                    WHERE raw_pages.numac IS NULL
                    LIMIT %s
                    """,
                    (RAW_VERSION, limit),
                )
            return cursor.fetchall()

    def get_numac_dates(self, numacs):
        """Return {doc_id, date} for a specific list of numacs (for targeted fetch)."""
        if not numacs:
            return []
        self.ensure()
        placeholders = ','.join(['%s'] * len(numacs))
        with self.conn.cursor() as cursor:
            cursor.execute(
                f"SELECT doc_id, date FROM raw_ids WHERE doc_id IN ({placeholders})",
                numacs,
            )
            return cursor.fetchall()

    def store_raw_page(self, numac, pub_date, raw_fr, raw_nl, force=False):
        """Insert or conditionally overwrite a raw page record."""
        self.ensure()
        if force:
            sql = """
                INSERT INTO raw_pages (numac, pub_date, raw_fr, raw_nl, version, raw_source_version)
                VALUES (%s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                    pub_date           = VALUES(pub_date),
                    raw_fr             = VALUES(raw_fr),
                    raw_nl             = VALUES(raw_nl),
                    version            = VALUES(version),
                    raw_source_version = VALUES(raw_source_version)
            """
        else:
            sql = """
                INSERT INTO raw_pages (numac, pub_date, raw_fr, raw_nl, version, raw_source_version)
                VALUES (%s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE id = id
            """
        with self.conn.cursor() as cursor:
            cursor.execute(sql, (numac, pub_date, raw_fr, raw_nl, RAW_VERSION, RAW_SOURCE_VERSION))
        self.conn.commit()

    # ── Parse module ──────────────────────────────────────────────────────────

    def get_numacs_to_parse(self, limit=1000, force=False):
        """Return list of numac strings that still need parsing."""
        self.ensure()
        with self.conn.cursor() as cursor:
            if force:
                cursor.execute(
                    "SELECT numac FROM raw_pages WHERE raw_source_version = %s LIMIT %s",
                    (RAW_SOURCE_VERSION, limit),
                )
            else:
                cursor.execute(
                    """
                    SELECT raw_pages.numac FROM raw_pages
                    LEFT JOIN docs ON raw_pages.numac = docs.numac
                    WHERE docs.numac IS NULL
                      AND raw_pages.raw_source_version = %s
                    UNION
                    SELECT raw_pages.numac FROM raw_pages
                    LEFT JOIN docs ON raw_pages.numac = docs.numac
                    WHERE docs.version != %s
                      AND raw_pages.raw_source_version = %s
                    LIMIT %s
                    """,
                    (RAW_SOURCE_VERSION, DOC_VERSION, RAW_SOURCE_VERSION, limit),
                )
            return [row['numac'] for row in cursor.fetchall()]

    def get_raw_page(self, numac):
        """Return {pub_date, raw_fr, raw_nl} for one numac, or None."""
        self.ensure()
        with self.conn.cursor() as cursor:
            cursor.execute(
                "SELECT pub_date, raw_fr, raw_nl FROM raw_pages WHERE numac = %s",
                (numac,),
            )
            return cursor.fetchone()

    def get_or_create_source(self, src_nl, src_fr):
        """Upsert a source pair and return its id."""
        self.ensure()
        with self.conn.cursor() as cursor:
            cursor.execute(
                "SELECT id FROM sources WHERE source_nl = %s AND source_fr = %s",
                (src_nl, src_fr),
            )
            row = cursor.fetchone()
            if row:
                return row['id']
            cursor.execute(
                "INSERT IGNORE INTO sources (source_nl, source_fr) VALUES (%s, %s)",
                (src_nl, src_fr),
            )
        self.conn.commit()
        with self.conn.cursor() as cursor:
            cursor.execute(
                "SELECT id FROM sources WHERE source_nl = %s AND source_fr = %s",
                (src_nl, src_fr),
            )
            row = cursor.fetchone()
            return row['id'] if row else None

    def get_or_create_type(self, type_nl, type_fr):
        """Upsert a type pair and return its id."""
        self.ensure()
        # When only one language has a real type value
        if (type_nl == 'notype') != (type_fr == 'notype'):
            lang  = 'fr' if type_nl == 'notype' else 'nl'
            value = type_fr if type_nl == 'notype' else type_nl
            with self.conn.cursor() as cursor:
                cursor.execute(f"SELECT id FROM types WHERE type_{lang} = %s", (value,))
                row = cursor.fetchone()
                if row:
                    return row['id']
        else:
            with self.conn.cursor() as cursor:
                cursor.execute(
                    "SELECT id FROM types WHERE type_nl = %s AND type_fr = %s",
                    (type_nl, type_fr),
                )
                row = cursor.fetchone()
                if row:
                    return row['id']

        with self.conn.cursor() as cursor:
            cursor.execute(
                "INSERT IGNORE INTO types (type_nl, type_fr) VALUES (%s, %s)",
                (type_nl, type_fr),
            )
        self.conn.commit()
        with self.conn.cursor() as cursor:
            cursor.execute(
                "SELECT id FROM types WHERE type_nl = %s AND type_fr = %s",
                (type_nl, type_fr),
            )
            row = cursor.fetchone()
            return row['id'] if row else None

    def store_doc(self, data, type_id, source_id):
        """Upsert the main doc record. Always overwrites on conflict."""
        self.ensure()
        sql = """
            INSERT INTO docs
                (numac, pub_date, prom_date, type, source, version, anonymise,
                 eli_type_fr, eli_type_nl, chrono_id,
                 chamber_id, senate_id, chamber_leg, senate_leg)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
            ON DUPLICATE KEY UPDATE
                pub_date    = VALUES(pub_date),
                prom_date   = VALUES(prom_date),
                type        = VALUES(type),
                source      = VALUES(source),
                version     = VALUES(version),
                eli_type_fr = VALUES(eli_type_fr),
                eli_type_nl = VALUES(eli_type_nl),
                chrono_id   = VALUES(chrono_id),
                chamber_id  = VALUES(chamber_id),
                senate_id   = VALUES(senate_id),
                chamber_leg = VALUES(chamber_leg),
                senate_leg  = VALUES(senate_leg)
        """
        with self.conn.cursor() as cursor:
            cursor.execute(sql, (
                data['numac'], data['pub_date'], data['prom_date'],
                type_id, source_id, DOC_VERSION,
                1 if data.get('anonymise') else 0,
                data.get('eli_type_fr'), data.get('eli_type_nl'),
                data.get('chrono_id'),
                data.get('chamber_id'), data.get('senate_id'),
                data.get('chamber_leg'), data.get('senate_leg'),
            ))
        self.conn.commit()

    def store_text(self, numac, lang, raw, pure):
        """Upsert plain-text content for one language."""
        self.ensure()
        sql = """
            INSERT INTO text (numac, ln, raw, pure, length)
            VALUES (%s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                raw = VALUES(raw), pure = VALUES(pure), length = VALUES(length)
        """
        with self.conn.cursor() as cursor:
            cursor.execute(sql, (numac, lang, raw, pure, len(pure)))
        self.conn.commit()

    def store_title(self, numac, lang, raw, pure):
        """Upsert a title for one language."""
        self.ensure()
        sql = """
            INSERT INTO titles (numac, ln, raw, pure)
            VALUES (%s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE raw = VALUES(raw), pure = VALUES(pure)
        """
        with self.conn.cursor() as cursor:
            cursor.execute(sql, (numac, lang, raw, pure))
        self.conn.commit()

    def add_doc_language(self, numac, lang):
        """Append a language code to docs.languages via CONCAT_WS."""
        self.ensure()
        with self.conn.cursor() as cursor:
            cursor.execute(
                "UPDATE docs SET languages = CONCAT_WS(',', languages, %s) WHERE numac = %s",
                (lang, numac),
            )
        self.conn.commit()

    def store_doc_links(self, numac, chrono, eli, pdf):
        """Upsert document external links."""
        self.ensure()
        sql = """
            INSERT INTO doc_links (numac, chrono, eli, pdf)
            VALUES (%s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                chrono = VALUES(chrono), eli = VALUES(eli), pdf = VALUES(pdf)
        """
        with self.conn.cursor() as cursor:
            cursor.execute(sql, (numac, chrono, eli, pdf))
        self.conn.commit()
