<?php
// Objet d'interaction avec les collections


class collection_class
	{
	var $filter = array();
	var $do_log = false;
    private $observer;

    public $db;
    public $ln;

	public function __construct()
		{
		$this->observer = observer::getInstance();
		if (COLLECTION_CLASS_LOG) $this->do_log = true;
		return $this;
		}

	public function setConnector($conn)
		{
		$this->db = $conn;
		return $this;
		}

	public function reset()
		{
		$this->filter = array();
		if (defined("CURRENT_LANG"))
			$this->setLanguage(CURRENT_LANG);
		else
			{
			global $precalc_lang;
			$this->setLanguage($precalc_lang);
			}

		return $this;
		}

	public function setLanguage($ln)
		{
		$this->ln = $ln;
		// fr | nl
		return $this;
		}

	public function lastMod()
		{
		$sql = 'select SQL_CACHE UNIX_TIMESTAMP(date) from done_dates ORDER BY date DESC LIMIT 1';
		$ret= $this->db->query($sql,Q_FLAT);
		return $ret[0]+7220;
		}
	
	public function lastDocs()
		{
		$sql = 'select SQL_CACHE UNIX_TIMESTAMP(pub_date) from docs ORDER BY pub_date DESC LIMIT 1';
		$ret= $this->db->query($sql,Q_FLAT);
        if ( count($ret)> 0 )
            return $ret[0]+7220;
        return 0;
		}

	public function setFilter($type,$data=false)
		{
			if ( in_array($type,array('day','month','year','stamp'))
		   && !isset($data['dateType']))
		   $data['dateType'] = 'pub';
		switch ($type)
			{
			case 'day':
				list($y,$m,$d) = $data;
				$type = $this->datetype($data['dateType']);
				$temp = "$type = '$y-$m-$d'";
				break;
			case 'month':
				list($y,$m) = $data;
				$type = $this->datetype($data['dateType']);
				$temp = "year($type) = '$y' and month($type) = '$m'";
				break;
			case 'year':
				$y = $data[0];
				$type = $this->datetype($data['dateType']);
				$temp = "year($type) = '$y'";
				break;
			case 'stamp':
				$s = $data[0];
				$type = $this->datetype($data['dateType']);
				$temp = sprintf("$type = '%s-%s-%s'",date('Y',$s),date('m',$s),date('d',$s));
				break;
			case 'numac':
				$n = $data[0];
				$temp = "docs.numac = '$n'";
				break;
			case 'numaclist':
				$list = $data;	
				$temp = "docs.numac in (".implode(', ',$list).")";
				break;
			case 'linkto':
				$n = $data;
				$temp = "links_cache.linkto = '$n'";
				break;
			case 'lang':
				$lang = $data;
				$temp = "docs.languages like '%".$lang."%'";
				$this->setLanguage($lang);
				break;
			default:
				$temp = false;
			}
		if ($temp)	
			$this->filter[] = $temp;

		if ($this->do_log)
			{
			$this->log('filter set:'.$type);
			$this->log('filter type:'.$temp);
			}
		return $this;
		}

	private function getCollection()
		{
		$baseQ =array('select SQL_CACHE * from docs');
		if ($this->filter)
			{
			array_push($baseQ,'where');
			array_push($baseQ,$this->filter);
			}
		$query = implode(' ',$baseQ);
		if ($this->do_log) $this->log('Obtaining collection');
		$this->collection = $this->db->query($query);
		return $this->collection;
		}

	private function dateType($type)
		{
		switch ($type)
			{
			case 'prom':
				return 'prom_date';
			case 'pub':
			default:
				return 'pub_date';
			}
		}

	public function yearSpan($date="pub")
		{
		$type = $this->datetype($date);
		$baseQ = "SELECT SQL_CACHE DISTINCT YEAR($type) as year FROM `docs`";
		$sql = $this->toQuery($baseQ). ' order by YEAR desc';
		if ($this->do_log) $this->log('Obtaining yearspan');
		return $this->db->query($sql,Q_FLAT);
		}

	public function monthSpan($date="pub")
		{
		$type = $this->datetype($date);
		$baseQ = "select SQL_CACHE year($type) as year ,"
				 ."month($type) as month,"
				 ."concat(year($type),month($type)) as tmp "
				 .'from docs ';
		$sql = $this->toQuery($baseQ)
				."group by tmp order by month($type) asc";
		if ($this->do_log) $this->log('Obtaining monthspan');
		return $this->db->query($sql);
		}

	public function daySpan($date="pub")
		{
		$type = $this->datetype($date);
		$baseQ = "select SQL_CACHE distinct $type as t,"
				 . "year($type) as year ,"
				 . "month($type) as month,"
				 . "day($type) as day "
				 . 'from docs';
		$sql = $this->toQuery($baseQ)
			   . " order by day($type) asc";
		if ($this->do_log) $this->log('Obtaining dayspan');
		return $this->db->query($sql);
		}

	public function numacs()
		{		
		$baseQ = 'select SQL_CACHE numac from docs';
		$sql = $this->toQuery($baseQ)
				.' order by numac asc';
		if ($this->do_log) $this->log('Obtaining numacs');
		return $this->db->query($sql);
		}

	public function numacCheck()
		{
		$baseQ = 'select SQL_CACHE languages as c from docs';
		$sql = $this->toQuery($baseQ);
		if ($this->do_log) $this->log('Checking numac presence');
		return $this->db->query($sql,Q_FLAT);
		}

	public function anonCheck()
		{
		$baseQ = 'select anonymise as anon from docs';
		$sql = $this->toQuery($baseQ);
		if ($this->do_log) $this->log('Checking numac presence');
		$res = $this->db->query($sql,Q_FLAT);
		return $res[0] == 1 ? true : false;
		}



	public function reverseLinks()
		{
		$baseQ = "SELECT SQL_CACHE numac from links_cache";
		$sql = $this->toQuery($baseQ);
		if ($this->do_log) $this->log('Obtaining reverse links');
		return $this->db->query($sql,Q_FLAT);
		}

	public function docsMeta()
		{
		$baseQ = 'select SQL_CACHE
					docs.numac as numac,
					docs.anonymise as anon,
					titles.raw as title_raw,
					titles.pure as title_pure,
					source_%LN   as source,
					type_%LN    as type,
                    docs.chrono_id as chrono_id,
                    docs.senate_id as senate_id,
                    docs.senate_leg as senate_leg,
                    docs.chamber_id as chamber_id,
                    docs.chamber_leg as chamber_leg,
					DATE_FORMAT(pub_date,  \'%Y%m%d\') as pub_date,
					DATE_FORMAT(prom_date, \'%Y%m%d\') as prom_date
					 FROM `docs` 
					join titles on docs.numac = titles.numac 
					join sources on docs.source = sources.id 
					join types on docs.type = types.id';
		$baseQ = str_replace('%LN',$this->ln,$baseQ);
		$this->filter[] = sprintf('titles.ln  = \'%s\'',$this->ln);;
		$sql = $this->toQuery($baseQ)
					.' order by types.ord ASC';
		if ($this->do_log) $this->log('Obtaining docs meta');
		return $this->db->query($sql);
		}

	public function doc()
		{
		$baseQ = 'select  SQL_CACHE
					docs.numac as numac,
					docs.anonymise as anon,
					docs.eli_type_fr as eli_type_fr,
					docs.eli_type_nl as eli_type_nl,
					titles.raw as title_raw,
					titles.pure as title_pure,
					source_%LN   as source,
					type_%LN    as type,
                    docs.chrono_id as chrono_id,
                    docs.senate_id as senate_id,
                    docs.senate_leg as senate_leg,
                    docs.chamber_id as chamber_id,
                    docs.chamber_leg as chamber_leg,
                    doc_links.chrono as chrono,
                    doc_links.eli as eli,
                    doc_links.pdf as pdf,
					DATE_FORMAT(pub_date,  \'%Y%m%d\') as pub_date,
					UNIX_TIMESTAMP(pub_date) as pub_date_stamp,
					DATE_FORMAT(prom_date, \'%Y%m%d\') as prom_date,
					text.raw as text,
					text.pure as textpure,
					text.id	 as textid
					 FROM `docs` 
					join titles on docs.numac = titles.numac 
                    join doc_links on docs.numac = doc_links.numac
					join sources on docs.source = sources.id 
					join text on docs.numac = text.numac
					join types on docs.type = types.id';
		$baseQ = str_replace('%LN',$this->ln,$baseQ);
		$this->filter[] = sprintf('titles.ln  = \'%s\'',$this->ln);
		$this->filter[] = sprintf('text.ln  = \'%s\'',$this->ln);
		$sql = $this->toQuery($baseQ);
		if ($this->do_log) $this->log('Obtaining doc');
		return $this->db->query($sql);
		}

	public function getTypes()
		{
		$sql = 'SELECT SQL_CACHE DISTINCT type_%LN AS
		TYPE FROM types ORDER BY ord ASC';
		$sql = str_replace('%LN',$this->ln,$sql);
		if ($this->do_log) $this->log('Obtaining Types');
		return $this->db->query($sql, Q_FLAT);
		}

	public function getOldDates()
		{
		$sql = 'select SQL_NO_CACHE '
			  .'DATE_FORMAT(prom_date, \'%Y%m%d\') as prom_date '
			  .'from docs where prom_date  <= cast("1997-06-03" as date) '
			  .'and prom_date != cast("0000-00-00" as date)';
		if ($this->do_log) $this->log('Obtaining old dates');
		return $this->db->query($sql,Q_FLAT);
		}

	private function toQuery($baseQ)
		{
		$q = !empty($baseQ) ? array ($baseQ) : array();
		if (count($this->filter)>0)
			{
			array_push($q,'where');
			array_push($q,implode(' and ',$this->filter));
			}
		return implode(' ',$q);
		}

	private function log($m,$t='')
		{
		$this->observer->msg($m, 'collection', $t);
		return $this;
		}
	}

