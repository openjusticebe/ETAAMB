<?php
// text link extractor

class parser {
	var $debug 			= false;		
	var $good_limit 	= 10;
	var $old_date_limit = 1980;
	static $version 	= 9;
	var $do_log			= false;

    private $observer;
    private $useCache;
    private $col;
    private $dict;
    private $types;
    private $oldDates;

    public $text;
    public $title;
    public $lang;
    public $numac;
    public $prom_date;
    public $extractedLinks;


	var $months = array( 'fr' => array(
			'janvier','fevrier','mars',
			'avril','mai','juin','juillet',
			'aout','septembre','octobre',
			'novembre','decembre'),
						'nl' => array(
			'januari','februari','maart',
			'april','mei','juni','juli',
			'augustus','september','oktober',
			'november','december'));

	public function __construct()
		{
		$this->useCache = PARSER_LINKS_CACHE;
		if (PARSER_CLASS_LOG)
			{
			$this->observer = observer::getInstance();
			$this->do_log = true;
			}
		}

	public function set($data)
		{
		$this->text  = $data['text'];
		$this->title = $data['title'];
		$this->lang  = $data['lang'];
		$this->numac = $data['numac'];
		$this->prom_date = $data['prom_date'];
		return $this;
		}

	public function setCollection($col) { $this->col = $col;return $this; }
	public function setDict($dict) { $this->dict = $dict; return $this; }

	public function extractLinks()
		{
		if ($this->do_log) $this->log('Link Extraction Cache check');
		if ($this->cache() !== false) return $this->cache();

		if ($this->do_log) $this->log('Link Extraction started');
		$numacsList   = $this->numacsList_get();

		if ($this->do_log) $this->log('Results Filtering: <b>'.count($numacsList).'</b> found');
		$numacsSorted = $this->numacsList_sort($numacsList);

		if ($this->do_log) $this->log('Link Extraction completed: <b>' .count($numacsSorted) .'</b> links found');
		return $this->cache($numacsSorted);
		}

	private function cache($newVal=false)
		{
		if ($newVal !== false)
			{
			$this->extractedLinks = $newVal;
			if ($this->useCache) 
				$this->record_update($this->extractedLinks);

			return $this->extractedLinks;
			}

		if (isset($this->extractedLinks)) 
			return $this->extractedLinks;

		$cache = $this->record_retrieve();
		if ($cache !==false && $this->useCache)
			{
			if ($this->do_log) $this->log('Retrieving Record');
			$this->extractedLinks = $cache;
			return $this->extractedLinks;
			}

		return false;
		}

	private function numacsList_get()
		{
		if ($this->do_log) $this->log('Pattern Matching Started');
		preg_match_all($this->makeExtractPattern()
					  ,$this->text
					  ,$matches
					  ,PREG_SET_ORDER);

		$list = $this->extractMatchesToArray($matches);
		$list = $this->removeUnavailableDates($list);
		$list = $this->removeDoubleLinks($list);
		return $list;
		}

	private function numacsList_sort($list)
		{
		$numacs = array();
		$cache  = array();
		foreach ($list as $link)
			{
			if ($this->do_log) $this->log('Numac sorting began..');
			$sql  = $this->extractLinkQuery($link);
			$allDocs = $this->col->db->query($sql);

			
			if (count($allDocs) > 0)
				{
				$results = $this->numacsLinks_sort($allDocs);
				if (count($results) > 1)
					{
					if ($this->do_log) $this->log('Switching to Advanced Sorting');
					$advDocs  = $this->enrichResults($link,$results,$allDocs);
					$results = $this->numacsLinks_advancedsort($advDocs);
					}

				if (FILTER_NUMAC_EXTRACTION)
					{
					$results = $this->filterCachedNumacs($results,$cache);
					if (count($results) == 0) continue;
					}

				$numacs[] = $results;
				$cache = array_merge($cache,$results);
				}
			}
		return count($numacs) > 0
			? $numacs
			: array();
		}

	private function filterCachedNumacs($Docs,$Cache)
		{
		$Ret = array();
		foreach ($Docs as $Doc)
			{
			if (!in_array($Doc,$Cache))
				$Ret[] = $Doc;
			}
		return $Ret;
		}


	public function reset()
		{
		unset($this->extractedLinks);
		return $this;
		}

	private function oldfilterDocs1($docs)
		{
		$good = array();
		$bad  = array();
		foreach ($docs as $doc)
			{
			$numac = $doc['numac'];
			$score = $doc['title_score'];
			if ($score < $this->good_limit)
				$bad[] = $numac;
			else
				$good[$numac] = $score;
			}

		if (count($good) == 0) return $bad;
		arsort($good);
		if ($this->debug) print_r($good);
		return array_keys($good);
		}

	private function numacsLinks_sort($docs)
		{
		if ($this->do_log) $this->log('Pattern Matching Started');
		$group = new stat_sorter();
		$group->scorevector_set(array(2,1));
		foreach ($docs as $d)
			{
			$context_calc 		= $this->alignScore('context',$d);
			$titleInLink_calc   = $this->alignScore('titleInLink',$d);
			$vector_calc 		= array($context_calc, $titleInLink_calc);
			$group->add($d['numac'],$vector_calc);
			}

		$result = $group->sortFunctionTwo();

		return array_keys($result);
		}

	private function numacsLinks_advancedsort($docs)
		{
		if ($this->do_log) $this->log('Pattern Matching Started');
		$group = new stat_sorter();
		$group->scorevector_set(array(2,1,2));
		foreach ($docs as $d)
			{
			$titleInSource_calc = $this->alignScore('titleInSource',$d);
			$text_calc 			= $this->alignScore('text',$d);
			$title				= $d['title'];
			$dict 				= calc::make_vector_dict($title.' '.$this->title);
			$linkTitle_vector   = calc::to_vector($title,$dict);
			$linkSource_vector  = calc::to_vector($this->title,$dict);
			$title_similarity   = calc::cosine_similarity($linkTitle_vector,$linkSource_vector);
			$titleSim_calc      = intval($title_similarity *100);

			$vector_calc 		= array($titleInSource_calc, $titleSim_calc,$text_calc);
			$group->add($d['numac'],$vector_calc);
			}
		$result = $group->sortFunctionTwo();
		return array_keys($result);
		}

	private function alignScore($type,$d)
		{
		$score = $d[$type.'_score'];
		$base  = $d[$type.'_base'];
		return calc::to_norm($score,$base);
		}

	private function printExtractedMatches($m)
		{
		printf('<pre>%s</pre>',
			print_r($m,true));
		}

	private function getTypes()
		{
		if (isset($this->types)) return $this->types;
		$this->types = $this->col->reset()->getTypes();
		return $this->getTypes();
		}

	private function makeExtractPattern()
		{
		$pattern = sprintf("#(%s) %s (\d{1,2})(er)? (%s) (\d{4})([^;\n,.]*)#"
			,str_replace('€','|',preg_quote(implode('€',$this->getTypes())))
			,$this->dict->l() == 'fr' ? 'du' : 'van'
			,implode('|',$this->months[$this->dict->l()]));
		$pattern = str_replace(' ','\s*',$pattern);
		if ($this->do_log) $this->log('Pattern:'.$pattern);
		return $pattern;
		}

	private function extractMatchesToArray($matches)
		{
		$r = array();
		$monthkeys = array_flip($this->months[$this->dict->l()]);
		foreach ($matches as $m)
			{
			$context = new normalize($m[6]);
			$r[] = array(
				'title' => $m[0],
				'type'	=> $m[1],
				'day'	=> $m[2],
				'month' => $monthkeys[$m[4]]+1,
				'year'	=> $m[5],
				'context' => $context->noAccents()
									 ->doTrim()
									 ->stopwords()
									 ->str());
			}
		return $r;
		}

	private function removeUnavailableDates($list)
		{
		$cleanList = array();
		if (!isset($this->oldDates))
			$this->oldDates = $this->col->getOldDates();
		foreach ($list as $doc)
			{
			$datestamp = mktime(0,0,0,$doc['month'],$doc['day'],$doc['year']);
			$stamp_limit = mktime(0,0,0,06,03,1997);

			$date_string = date('Ymd',$datestamp);
			$limit_string = date('Ymd',$stamp_limit);
			if ($date_string != $this->prom_date
			   && ( intval($date_string) >= intval($limit_string)
			      || in_array($date_string, $this->oldDates))
			   )
				$cleanList[] = $doc;
			}
		return $cleanList;
		}

	private function removeDoubleLinks($list)
		{
		$cleanList = array();
		$checkList = array();
		foreach ($list as $doc)
			{
			$add = true;
			$y = $doc['year'];
			$m = $doc['month'];
			$d = $doc['day'];
			$t = $doc['type'];
			$c = $doc['context'] != '' ? $doc['context'] : ' ';
			$group = sprintf('%s_%s_%s_%s',$d,$m,$y,$t);
			if (!isset($checkList[$group]))
				{
				$checkList[$group] = array();
				}
			else
				{
				foreach ($checkList[$group] as $test)
					{
					if ( strpos($test,$c) !== false
					   ||strpos($c,$test) !== false)
						$add =false;
					}
				}
			if ($add)
				{
				$cleanList[] = $doc;
				$checkList[$group][]=$c;
				}
			}
		return $cleanList;
		}

	private function extractListToQuery($array)
		{
		$f = array();
		foreach ($array as $doc)
			{
			$f[] = sprintf('( docs.prom_date=\'%s-%s-%s\' and types.type_%s = \'%s\')'
					,$doc['year']
					,$this->leadZero($doc['month'])
					,$this->leadZero($doc['day'])
					,$this->dict->l(),$doc['type']);
			}
		$sql = sprintf('select numac from docs join types on docs.type = types.id where %s'
					,implode(' or ',$f));
		return $sql;
		}

	private function extractLinkQuery($link)
		{
		if ($this->do_log) $this->log(sprintf('Link %s of %s/%s/%s context:%s'
											 ,$link['type']
											 ,$link['day']
											 ,$link['month']
											 ,$link['year']
											 ,$link['context']));
		$nicetitle = new normalize($this->title);
		$nicetitle->noAccents()->stopwords();
		$sql = sprintf('select 
						docs.numac as numac,
						titles.pure as title,
						match(titles.pure) against (\'%s\' in boolean mode) as context_score,
						match(text.pure) against (\'%s\' in boolean mode) as titleInLink_score,
						match(text.pure) against (\'%s\' in boolean mode) as text_score,
						\'%s\' as context_base,
						\'%s\' as titleInLink_base,
						\'%s\' as text_base
						from docs join types on docs.type = types.id 
						     	  join titles on docs.numac = titles.numac
								  join text on text.numac = docs.numac
						where docs.prom_date=\'%s-%s-%s\' 
						and types.type_%s = \'%s\'
						and titles.ln = \'%s\'
						and text.ln = \'%s\''
					   ,addslashes($link['context'])
					   ,$nicetitle
					   ,$this->text
					   ,str_word_count($link['context'],0)
					   ,str_word_count($this->title,0)
					   ,str_word_count($this->text,0)
					   ,$link['year']
					   ,$this->leadZero($link['month'])
					   ,$this->leadZero($link['day'])
					   ,$this->dict->l()
					   ,addslashes($link['type'])
					   ,$this->dict->l()
					   ,$this->dict->l());
		if ($this->debug) echo $sql;
		return $sql;
		}

	private function advancedCompareQuery($link,$numacs,$original_results)
		{
	
		$sql = sprintf('select
						docs.numac as numac,
						match(titles.pure) against (
							 titles.pure as retro_score,
						titles.pure as title
						from docs join titles on docs.numac = titles.numac,
						(select text.pure as text from text where numac = %s and ln=\'%s\')
							as base
						where docs.numac in (\'%s\')
						and titles.ln = \'%s\'
						order by retro_score desc'
					   ,$this->numac
					   ,$this->dict->l()
					   ,implode("', '",$numacs)
					   ,$this->dict->l());
		return $sql;
		}

	private function enrichResults($link,$numacs,$original_results)
		{
		$return = array();
		foreach ($original_results as $result)
			{
			if (!in_array($result['numac'],$numacs)) continue;
			$sql = sprintf('select
						match (text.pure) against (\'%s\' in boolean mode) as score
						from docs join text on text.numac = docs.numac
						where docs.numac = %s
						and text.ln = \'%s\''
						,$result['title']
						,$this->numac
						,$this->dict->l());
			$res = $this->col->db->query($sql);
			$result['titleInSource_score'] = $res[0]['score'];
			$result['titleInSource_base']  = str_word_count($result['title'],0);

			$return[] = $result;
			}
		return $return;
		}

	private function leadZero($t)
		{
		return strlen($t) == 1 ? '0'.$t : $t;
		}

	private function record_update($numacs)
		{
		$sql = sprintf('delete from links_cache where numac = %s and ln = \'%s\''
					  ,$this->numac
					  ,$this->lang);
		$this->col->db->exec($sql);

		$numacsList = array();
		foreach ($numacs as $numac)
			$numacsList = array_merge($numacsList,$numac);

		$numacsList = array_unique($numacsList);
		if (empty($numacsList))
			array_push($numacsList,'0');

		$sql = sprintf("insert into links_cache(numac,linkto,ln,position,version) values (%d,?,'%s',?,%d)"
					  ,$this->numac
					  ,$this->lang
					  ,parser::$version);

		$statement = $this->col->db->prepare($sql);
		$i=1;
		foreach ($numacsList as $numac)
			{
			$pos = $i++;
			$statement->bind_param("ii",$numac,$pos);
			$statement->execute();
			}
		return true;
		}

	private function record_available()
		{
		$sql = sprintf('select count(numac) from links_cache where numac = %s and ln = \'%s\' and version = %s'
					  ,$this->numac
					  ,$this->lang
					  ,parser::$version);
		$result = $this->col->db->query($sql,Q_FLAT);
		return intval($result[0]) > 0 
			   ? true
			   : false;
		}

	private function record_retrieve()
		{
		$sql = sprintf('select linkto from links_cache where numac = %s and ln = \'%s\' and version = %s '
					  .'order by position asc'
					  ,$this->numac
					  ,$this->lang
					  ,parser::$version);
		$result = $this->col->db->query($sql,Q_FLAT);
		if (count($result) == 0)
			return false;
		if ($result[0] == '0')
			return array();
		return array($result);
		}

	private function log($m,$t='')
		{
		$this->observer->msg($m, 'parser', $t);
		return $this;
		}

}	

