<?php

class lang 
	{
	private $langs = array("fr","nl", "fr-nl");
	private $key   = "ln";
	private $cookie_life = 2592000; // 3600*24*30  - 1 month
	private $debug = false;
	private $cookiePath = '/';
	private $do_log = false;
    private $observer;
    private $lang;

	public function __construct()
		{
		if (LANG_CLASS_LOG)
			{
			$this->observer = observer::getinstance();
		    $this->do_log = true;
			}

		$this->set($this->obtain());
		}

	public function get()
		{
		return $this->lang;	
		}

	public function __toString()
		{
		return $this->get();
		}

	public function set($l)
		{
		if (in_array($l,$this->langs))
			{
			$this->lang = $l;
			}
		}

	public function menu($mask,$url,$page)
		{
		$ret = array();
		foreach ($this->langs as $l)
			{
			if ($this->do_log) $this->log("Checking Url for lang $l");
			if (!$page->isLangOk($l)) 
				continue;

			if ($l == $page->otherLang())
				{
				if ($this->do_log) $this->log("Preparing Url for other lang $l");
				if ( $page->otherLangUrl())
					{
					$d_url = a($page->otherLangUrl());
					}
				else
					$d_url = $url;
				}
			else
				$d_url = $url;


			$str = $mask;
			$query = $l;
			$str = str_replace('%lang',$l,$str);
			$str = str_replace('%title',$l,$str);
			$str = str_replace('%url',$d_url,$str);
			$str = str_replace('%class',($l == $this->lang ? 'selected_lang' : ''),$str);
			$ret[] = $str;
			}
		return implode(' ',$ret);
		}

	public function setCookiePath($p)
		{
		$this->cookiePath = $p;
		return $this;
		}

	public function save()
		{
		if (!headers_sent())
			setcookie($this->key,$this->lang, time()+$this->cookie_life
					 ,$this->cookiePath);

		if (isset($_SESSION))
			$_SESSION[$this->key] = $this->lang;
		}

	private function browser()
		{
		if (!isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) return false;
		$str = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
		foreach($this->langs as $l)
			{
			if (substr_count($str,$l) > 0)
				return $l;
			}
		return false;

		}

    private function obtain()
        {
		if ($this->domain())
			{
			if ($this->do_log) $this->log('Found lang in DOMAIN:'.$this->domain());
			return $this->domain();
			}

        if ($this->url())
			{
			if ($this->do_log) $this->log( 'Found lang in URL:'.$this->url());
			return $this->url();
			}
		
		
        if ($this->session())
			{
			if ($this->do_log) $this->log( 'Found lang in SESSION:'.$this->session());
			return $this->session();
			}
        if ($this->cookie())    
			{
			if ($this->do_log) $this->log( 'Found lang in COOKIE:'.$this->cookie());
			return $this->cookie();
			}
        if ($this->browser())   
			{
			if ($this->do_log) $this->log( 'Found lang in BROWSER:'.$this->browser());
			return $this->browser();
			}
		if ($this->do_log) $this->log( 'No Lang Found, setting DEFAULT: fr');
        return $this->langs[0];
        }

	private function cookie()
		{
		$k = $this->key;
		return (isset($_COOKIE[$k])) ? $this->check($_COOKIE[$k]) : false;
		}

	private function url()
		{
		$k = $this->key;
		return (isset($_GET[$k])) ? $this->check($_GET[$k]) : false;
		}

	private function session()
		{
		$k = $this->key;
		return (isset($_SESSION[$k])) ? $this->check($_SESSION[$k]) : false;
		}

	private function domain()
		{
		if (!isset($_SERVER['HTTP_HOST'])) return false;
		preg_match('#^(nl|fr)\.etaamb\.be$#',$_SERVER['HTTP_HOST'],$match);
		return isset($match[1]) ? $match[1] : false;
		}

	private function referer()
		{
		if (!isset($_SERVER['HTTP_REFERER'])) return false;
		preg_match('#hl=(fr|nl)&#i',$_SERVER['HTTP_REFERER'],$match);
		return isset($match[1]) ? $match[1] : false;
		}

	private function check($var)
		{
		if (in_array($var,$this->langs))
			return $var;
		else
			return false;
		}


	private function log($m,$t='')
		{
		$this->observer->msg($m, 'lang', $t);
		return $this;
		}


	}
