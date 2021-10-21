<?php
// Url Factory Class, to make the url requests

class url_factory {
	var $do_log = false;
	private $url =false;
	private $mask=false;
	private $dom =false;
	private $page=false;
	private $lang=false;
	private $html=false;
	public function __construct($config)
		{
		$this->observer = observer::getinstance();
		if (URL_CLASS_LOG) $this->do_log = true;
		$this->url  = isset($config['url'])
					? $config['url']
					: false;
		$this->mask = isset($config['mask']) 
					? $config['mask']
					: URLMASK;
		$this->dom  = isset($config['dom']) 
				  	? $config['dom']
					: DOMAIN;
		$this->page = isset($config['page']) 
				  	? $config['page']
					: false;
		$this->lang = isset($config['lang']) 
				  	? $config['lang']
					: false;
		$this->html = isset($config['html']) 
				  	? $config['html']
					: false;
		}

	public function raw()
		{
		if (!$this->url) $this->url_build();
		return $this->url;
		}

	public function lang($pmask=false)
		{
		if (!$pmask && $this->lang) return $this->lang;
		$mask = ($pmask !== false) ? $pmask : $this->mask;
		return $this->url_extract('lang',$mask);
		}
	
	public function page($pmask=false)
		{
		$mask = ($pmask !== false) ? $pmask : $this->mask;
		return $this->url_extract('page',$mask);
		}

	public function mask_regex($pmask=false)
		{
		$mask_orig = ($pmask !== false) ? $pmask : $this->mask;
		$mask = preg_replace('#([?.])#','\\\$1',$mask_orig);
		$host = $this->host_getMask();

		if ($mask_orig == URLMASK_GET)
			$mask = str_replace('\?ln=%ln','(\\?ln=%ln)?',$mask);
		if ($mask_orig == URLMASK_DOMAIN)
			$host = str_replace('www.','',$host);

		$mask = str_replace('%ln','(?<lang>fr|nl)',$mask);
		$mask = str_replace('%host',$host,$mask);
		$mask = str_replace('%page','(?<page>/[^?]*)',$mask);
		return "#^$mask$#";
		}

	public function mask_match($pmask=false)
		{
		if (!$this->url) $this->url_build();
		$mask = ($pmask !== false) ? $pmask : $this->mask;
		$regex  = $this->mask_regex($mask);
		$res  = preg_match($regex,$this->url);
		if (REDIRECTION_TEST) printf('<br>Matching : %s<br>Against : %s gives : <b>%s</b><br>'
							  ,htmlentities($regex),$this->url,$res);
		return $res == 0 ? false : true;
		}

	public function host()
		{
		return $this->dom;
		}

	private function host_getMask()
		{
		return $this->dom;
		$ret = preg_replace('#([?.])#','\\\$1',$this->dom);
		$ret = preg_replace('#(\d{1,})#','\\d*',$ret);
		return $ret;
		}
	
	private function url_extract($element,$mask)
		{
		if (!$this->url) $this->url_build();
		$regex  = $this->mask_Regex($mask);
		if ($this->do_log) $this->log("Extracting element $element form ".$this->url." with regex ".htmlentities($regex));
		preg_match($regex,$this->url,$matches);
		if (isset($matches[$element]))
			{
			if ($this->do_log) $this->log('found: '.$matches[$element]);
			if (REDIRECTION_TEST) printf('<br>Searched for %s in %s<br>Found %s<br> '
								  ,$element ,$this->url, $matches[$element]);
			return $matches[$element];
			}
		if (REDIRECTION_TEST) printf('<br>Searched for %s in %s<br>Found %s<br> '
							  ,$element ,$this->url, 'nothing');
		return false;
		}

	public function url_build($pmask=false)
		{
		$mask = ($pmask !== false) ? $pmask : $this->mask;
		$lang = (!$this->lang && $this->url !== false)
				? $this->lang() : $this->lang;
		$page = (!$this->page && $this->url !== false)
				? $this->page() : $this->page;
		$page .= $page == '/' 
				? 'index.html' : '';
		$page .= strpos($page,'.html') == false && $this->html
				? '.html' : '';
		if (substr($page,0,1) !== '/') $page = '/'.$page;
		$url = str_replace('%ln'   ,$lang,$mask);
		$url = str_replace('%page' ,$page,$url);
		$url = str_replace('%host' ,$this->host(),$url);
		$url = preg_replace('#([\w\d])//([\w\d])#m', "$1/$2", $url);
		$this->url = $url;
		return $this->url;
		}

	static public function url_error()
		{
		header("HTTP/1.0 404 Not Found");
		echo '<h2>Unsupported Url Error</h2>
			  <p>The following URL:</p>
			  <p><b>'.self::full().'</b></p>
			  <p>Is not supported by Etaamb. This error has been logged.</p>
			  <p>It could have been caused by:
			  <ul>
			  	<li>a wrongly formatted URL</li>
				<li>an unadressed system error</li>
				<li>a software update that turned out bad</li>
				<li>an old and unsupported URL format</li>
				<li>bad karma</li>
			  </ul></p>	
			  <p>The following links are provided to help you get out of here:
			  <ul>
			  	<li><a href="https://www.etaamb.be">The Etaamb homepage</a></li>
				<li><a href="http://blogspot.etaamb.be">The Etaamb blog</a></li>
				'.(isset($_SERVER['HTTP_REFERER'])  
					? '<li><a href="'.$_SERVER['HTTP_REFERER'].'">Back to previous page</a></li>'
					: '') .'
			  </ul>
			  </p>
			';
		die;
		}

	static public function full()
		{
		return sprintf('%s://%s%s',URL_PROTOCOL, $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
		}

	static public function redirect($url,$code=301)
		{
		if (REDIRECTION_TEST)
			die(sprintf("<br>%s <br> should redirect (%s) to <br>%s", self::full(), $code, $url));
		//Header( "HTTP/1.1 301 Moved Permanently" );
		//Header( "Location: $url" ); 
		header("Location: $url",TRUE,$code);
		exit;
		}

	static public function isRoot($url=false)
		{
		$url = $url!==false ? $url : self::full();
		return $url == sprintf('%s://%s/',URL_PROTOCOL, DOMAIN);
		}


	private function log($m,$t='')
		{
		$this->observer->msg($m, 'url', $t);
		return $this;
		}

	}

