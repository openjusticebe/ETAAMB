<?php
// Router class to route url requests, and to parse them.

class url_router {
	var $do_log = false;

	public function __construct($url=false)
		{
		$this->observer = observer::getinstance();
		if (ROUTER_CLASS_LOG) $this->do_log = true;
		$this->url= $url ? $url : false;
		$this->arr=array();

		if ($this->url) $this->parse();
		}

	private function parse($url = false)
		{
		$parse = $url ? $url : $this->url;
		if ($this->do_log) $this->log("Parsing URL:<b>$parse</b>");
		$parse = str_replace('.html','',$parse);
		$parse = str_replace('.rss','',$parse);
		$parse = preg_replace('#^/?index#','',$parse);
		$tokens = explode('/',$parse);
		$tokens = array_filter(
					$tokens,
					function ($el) { return empty($el) ? false : true ;});
		$this->arr = $this->datetype_enrich(array_values($tokens));
		return $this->arr;
		}

	private function datetype_enrich($tokens)
		{
		if (empty($tokens)) return $tokens;
		if (preg_match('#^(prom|pub)$#',$tokens[0],$match) > 0)
			{
			$tokens = array_slice($tokens,1);
			$tokens['dateType'] = $match[1];
			if ($this->do_log) $this->log("Date Type found:<b>".$match[1]."</b>");
			}
		return $tokens;
		}

	public function noDateType()
		{
		if (!in_array($this->type(),array('year','month','day')))
			return false;
		$check =  !isset($this->arr['dateType']);
		if (REDIRECTION_TEST && $check)
			echo '<br>No Datetype Found !<br>';
		return $check;
		}
			

	public function type($arr = false)
		{
		$parse = $arr ? $arr : $this->arr;
		$value='error';
		if (isset($parse['dateType'])) $count = count($parse) - 1;
		else $count = count($parse);
	
		switch($count)
			{
			case '0': 
                $value='index';
				break;
			case '1':
				if (preg_match('#^\d{4}$#',$parse[0]) > 0) $value ='year';
				if (preg_match('#^\d{10}$#',$parse[0]) > 0) $value ='numac';
				if (preg_match('#^[a-z\-0-9]{1,}_n\d{10}$#',$parse[0]) > 0) $value='title';
				if (preg_match('#^rss$#',$parse[0]) > 0) $value = 'rss';
                if (POLICY_PAGES)
                    {
                    if (preg_match('#^[a-z\-]*-(policy|conditions)$#',$parse[0]) > 0) $value = 'policy';
                    }
				break;
			case '2':
				if (preg_match('#^\d{4}$#',$parse[0]) > 0
				 && preg_match('#^\d{2}$#',$parse[1]) > 0) $value ='month';
				if (preg_match('#^rss$#',$parse[0]) > 0) $value = 'rss';
				break;
			case 3:
				if (preg_match('#^\d{4}$#',$parse[0]) > 0
				 && preg_match('#^\d{2}$#',$parse[1]) > 0
				 && preg_match('#^\d{2}$#',$parse[1]) > 0) $value ='day';
				break;
			default:
				$value = 'error';
				break;
			}
		if ($this->do_log) $this->log("URL type is <b>$value</b> (count:$count), by parsing ".str_replace("\n",'',print_r($parse,true)));
		return $value;
		}

	private function set($url)
		{
		$this->url = $url;
		}

	public function getParsed($url=false)
		{
		return $this->arr ? $this->arr : $this->parse($url);
		}

	/* delete when possible */
	public function getRaw()
		{
		return $this->url;
		}

	private function log($m,$t='')
		{
		$this->observer->msg($m, 'router', $t);
		return $this;
		}

	}
