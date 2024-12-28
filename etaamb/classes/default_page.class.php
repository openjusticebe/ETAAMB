<?php

abstract class default_page
	{
	var $terms 	= array();
	var $error 	= '';
	public $do_log = false;
	var $years 	= array();
	var $months = array();
	var $days 	= array();
    public $data;
    public $docs;
    public $docsMeta;

    public $parser;
    public $highlighter;
    public $tagger;
    public $referer;

    public $col;
    public $url_object;
    public $expires;
    public $lastMod;
    public $dict;
    public $doc;

    public $observer;

	// abstract
	abstract public function isDataOk();

	public function __construct()
		{
		if (DEFAULT_CLASS_LOG)
			{
			$this->observer = observer::getInstance();
		    $this->do_log = true;
            $this->log('Default page log enabled');
			}
		}

	public function init() {return $this;}
	public function __toString()
		{
		return $this->getType();
		}

	public function setData($data)
		{
		$this->data = $data;
		return $this;
		}

	public function setDict($dict)
		{
		$this->dict = $dict;
		return $this;
		}

	public function setCollection($col)
		{
		$this->col = $col;
		return $this;
		}

	public function setParser($parser)
		{
		$this->parser = $parser;
		return $this;
		}

	public function setHighlighter($highlighter)
		{
		$this->highlighter = $highlighter;
		return $this;
		}

	public function setReferer($referer)
		{
		$this->referer = $referer;
		return $this;
		}

	public function setTagger($Tagger)
		{
		$this->tagger = $Tagger;
		return $this;
		}

	private function getType()
		{
		return get_class($this);
		}

	public function leadZero($t)
		{
		return strlen($t) == 1 ? '0'.$t : $t;
		}
	
	public function years()
		{
		$type = $this->datetype();
		if (isset($this->years[$type])) return $this->years[$type];
		$this->years[$type] = $this->col->yearSpan($type);
		return $this->years($type);
		}

	public function months()	
		{
		$type = $this->datetype();
		if (isset($this->months[$type])) return $this->months[$type];
		$this->months[$type] = $this->col->monthSpan($type);
		return $this->months($type);
		}

	public function days()
		{
		$type = $this->datetype();
		if (isset($this->days[$type])) return $this->days[$type];
		$this->days[$type] = $this->col->daySpan($type);
		return $this->days($type);
		}

	public function otherLangUrl()
		{
		return false;
		}

	private function numacs()
		{
		if (isset($this->numacs)) return $this->numacs;
		$this->numacs = $this->col->numacs();
		return $this->numacs();
		}

    public function utf8_dec($term)
        {
        if (!$term) return '';
        // There's buggy behavior here, but it works...
        return mb_convert_encoding($term, "UTF-8", "ISO-8859-1");
        }

	public function docsMeta($force=false)
		{
		if (isset($this->docsMeta) && !$force) return $this->docsMeta;
		$result = $this->col->docsMeta();
		foreach ($result as &$doc)
            {
			//$doc  = array_map('utf8_encode',$doc);
            $doc  = array_map(
                [$this, 'utf8_dec']
                ,$doc);
            }
		if (!$force) $this->docsMeta = $result;
		return $result;
		}

	public function doc($multi=false, $ignore_cache=false)
		{
        // $multi: load documents published on the same day
		if (isset($this->doc) && !$ignore_cache) return $this->doc;
        if ($this->do_log && $ignore_cache) $this->log('Doc cache ignored');
		$temp = $this->col->doc();
		if (!$multi && count($temp) == 1)
			{
			$doc  = array_map([$this, 'utf8_dec'],$temp[0]);
			$this->doc = $doc;
			}
		else
			{
			foreach ($temp as &$doc)
				$doc  = array_map([$this, 'utf8_dec'],$doc);
			$this->doc = $temp;
			}
		return $this->doc();
		}

	public function displayDate($stamp,$format=DATE_FORMAT)
		{
		if (intval($stamp) == 0) return '--';
		$mask = "#^(\d{4})(\d{2})(\d{2})$#";
		preg_match($mask,$stamp,$matches);
		list($stamp,$year,$month,$day) = $matches;
		$return = $format;
		$return = str_replace(
			 array('d','m','n','Y','y')
			,array($day,$month,intval($month),$year,substr($year,2,2))
			,$return);
        return $return;
		}

	public function completeDate($stamp,$ln=false)
		{
		if (intval($stamp) == 0) return '--';
		$day = $this->displayDate($stamp,'d');
		$year = $this->displayDate($stamp,'Y');
		$month = $this->getTerm('month_'.
			$this->displayDate($stamp,'n'),$ln);
		return sprintf('%s %s %s',$day,$month,$year);
		}

	public function toTitleLink($doc,$ln=false)
		{
		$completedate = $this->completeDate($doc['prom_date'],$ln);
		$title = $doc['type'].' '
				.($completedate !== '--'
				 ? $this->dict->get('of',$ln).' ' .$completedate
				 : '');

		
		$titleO = new normalize($title);
		$titleO->doTrim()
			  ->noAccents()
		      ->length(56)
			  ->regreplace('#[^a-z0-9 ]#','')
			  ->replace(' ','-');
		return $titleO.'_n'.$doc['numac'];
		}

	public function addIndexHomeLink($text)
		{
		$text = str_replace('Index'
				,'<a href="'.a('index').'">Index</a>'
				,$text);
		return $text;
		}

	public function isLangOk($ln)
		{
		return true;
		}

	public function datetype($set = false)
		{
		if ($set)
			{
			$this->data['dateType'] = $set;
			return true;
			}

		if (isset($this->data['dateType']))
			return $this->data['dateType'];
		return 'pub';
		}

	public function otherLang()
		{
		return $this->dict->l() == 'fr' ? 'nl' : 'fr';
		}

	public function lang()
		{
		return $this->dict->l();
		}

	public function givenTitle()
		{
		if (isset($this->url_object)) return $this->url_object->page();
		$this->url_object = new url_factory(array('url' => url_factory::full()));
		return $this->url_object->page();
		}

	public function fullUrl()
		{
		if (isset($this->url_object)) return $this->url_object->raw();
		$this->url_object = new url_factory(array('url' => url_factory::full()));
		return $this->url_object->raw();
		}
		

	public function predisplay() { return $this;}
	public function meta()
		{
		$m = array();
		
		}
	public function display($item)
		{
		echo $this->getTerm($item);
		}

	public function getTerm($item,$ln=false)
	{
		if (isset($this->terms[$item])) return $this->terms[$item];
		if (strpos($item,'month_') > -1)
			{
			$t = $item;
			$item = 'month';
			}
		elseif (strpos($item,'day_') > -1)
			{
			$t = $item;
			$item = 'day';
			}

		switch($item)
			{
			case 'month':
			case 'day':
				$h = $this->dict->get($t,$ln);break;
			default:
				$t = $this->getType().'_'.$item;
				$h = $this->dict->get($t,$ln);
			}
		return $h;
		}

	public function headers() 
		{
		$expires = $this->expires;
		$lastMod = $this->lastMod;
		$etag = '"'.md5($this->dict->l().$lastMod.$expires.VERSION.'bouz').'"';
		
		$res_304 = (isset($_SERVER['HTTP_IF_NONE_MATCH'])
			&& $etag == $_SERVER['HTTP_IF_NONE_MATCH'])
					? true
					: false;

		if ($res_304)
			header("HTTP/1.1 304 Not Modified");

		header("Etag: $etag");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s",$lastMod) . " GMT");
		header("Cache-Control: max-age=$expires, public, must-revalidate");
		header('Expires: '.gmdate('D, d M Y H:i:s',time()+$expires).' GMT');

		if ($res_304)
			{
			//header('Content-Length: 0');
			if ($this->do_log)
				{
				$this->log('304 response sent. Quitting');
				$this->observer->buff_read();
				}
			exit;
			}
		return $this;
		}

	public function redirect($url)
		{
		url_factory::redirect($url);
		}

	public function setTimes()
		{
		if (isset($this->col) && $this->col instanceof collection)
			$lastmod = $this->col->lastMod();
		else
			$lastmod = time();
		$this->expires = 3600*24*7; 
		$this->lastMod = $lastmod;
		return $this;
		}

	public function header()	{return false;}
	public function main() 	{return false;}
	public function footer() 	{return false;}

	public function log($m,$t='')
		{
		$this->observer->msg($m, 'page', 'sub');
		return $this;
		}

    function docsContentTable($docs)
        {
      	$h = array();
	  	// doc as numac, title_raw, title_pure, source, type, pub_date, prom_date
	  	$prev_type= '';
	  	foreach ($docs as $doc)
	  		{
	  		if ($doc['anon'] == 1 && !(ANONYMISE_TEST || AUTO_ANONYMISE)) continue;
	  		if ($prev_type != $doc['type'])
	  			$h[] = sprintf('<a name="%s"></a><h1>%s</h1>',
	  						$this->makeDocLink($doc['type']),c_type($doc['type']));
	  		$prev_type = $doc['type'];
	  		$doc['title_raw'] = $doc['anon'] == 1  
	  							? anoner::anonymise($doc['title_raw'],$this->dict->l())
	  							: $doc['title_raw'];
	  		$d = new docDispay($doc['numac']);
	  		$d->title($doc['title_raw'])
	  		  ->source($doc['source'])
	  		  ->type($doc['type'])
	  		  ->promDate($this->displayDate($doc['prom_date']))
	  		  ->pubDate($this->displayDate($doc['pub_date']))
	  		  ->setDict($this->dict)
	  		  ->addClass('day_list')
	  		  ->spanTemplate()
	  		  ->setLink(a($this->toTitleLink($doc)));
	  		$h[] = sprintf('%s',$d);
	  		}
	  	return implode("\t\n",$h);
        }

    function docsQuickMenu($docs)
        {
		$docTypes = $this->docsTypes($docs);
		$h = '<ul class="quickmenu">';
		foreach ($docTypes as $type => $count)
			{
			$h.= sprintf('<li><a href="#%s">%s (%s)</a></li>',
						$this->makeDocLink($type),c_type($type),$count);
			}
		return $h.'</ul>';
        }

    function docsTypes($docs)
        {
		$r = array();
		foreach ($docs as $doc)
			{
			$t = $doc['type'];
			$r[$t] = isset($r[$t]) ? $r[$t]+1 : 1;
			}
		return $r;
        }

	function makeDocLink($n)
		{
		$o = new normalize($n);
		return sprintf('%s',$o->noHtml()
							  ->toLower()
							  ->noAccents()
							  ->noSpaces());
		}
	}
