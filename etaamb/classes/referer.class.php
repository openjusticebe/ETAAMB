<?php
// Classe gestion du Referer
/*
Dans l'idée, cette classe prend le lien du referer,
regarde s'il s'agit d'un moteur de recherche, et 
analyse le lien pour y trouver les mots clés recherchés.

S'il en trouve, le contenu de la page est analysé, 
et chaque élément de contenu à la possibilité de le 
mettre en surbrillance, accompagnés d'ancres (ou pas)

Au niveau du navigateur alors, Le javascript permet de
passer d'un élement à un autre, avec un nouvel élément d'UI
*/
define('MAX_KEYWORDS',14);
class referer {
    private $observer;
    private $do_log;
    private $dict;
    private $checked;

    public $url;

	public function __construct()
		{
		$url = false;
		if (isset($_SERVER['HTTP_REFERER']))
			$url = $_SERVER['HTTP_REFERER'];

		$this->observer = observer::getInstance();
	   	$this->do_log = REFERER_CLASS_LOG;

		$this->set($url);
		return $this;
		}

	public function set($url)
		{
		$this->url=trim($url);
		if ($this->do_log) $this->log('Url set: '.$this->url);
		return $this;
		}

	public function setDict($Dict)
		{
		$this->dict = $Dict;
		return $this;
		}

	private function check()
		{
		if (isset($this->checked))
			return $this->checked;
		else
			$this->checked = (!$this->url)
							 ? false
							 : ref_factory::check($this->url);
		if ($this->do_log) $this->log('Url check: '.($this->checked ? 'true':'false'));
		return $this->check();
		}

	private function build()
		{
		if ($this->check())
			{
			$this->refObj = ref_factory::make($this->url);
			$this->refObj->setStopWords(explode(' ',$this->dict->get('stopwords')))
						 ->setStopWords(explode(' ',$this->dict->get('interfacewords')));
			if ($this->do_log) $this->log('object type: '.$this->refObj->type);
			}
		return $this;
		}

	public function keywords()
		{
		if ($this->check())
			{
			if (!isset($this->refObj)) $this->build();
			$keywords = $this->refObj->extractKeywords();
			if ($this->do_log) $this->log('extracted keywords:'
						.str_replace("\n",'',print_r($keywords,true)));
			return $keywords;
			}
		else
			return array();
		}

	public function type_get()
		{
		return $this->refObj->type;
		}

	private function log($m,$t='')
		{
		$this->observer->msg($m, 'referer', $t);
		return $this;
		}
	}

class ref_factory {
	// FIXME: Bad hard-coded protocol
	static $pattern ='#^https://([a-z0-9]+\.)?(google)\.[a-z]{2,3}/.*$#i';
	static public function make($url)
		{
		preg_match(self::$pattern
				  ,$url
				  ,$match);
		switch ($match[2])
			{
			case 'google':
				return new google_referer($url);
			}
		return default_referer($url);
		}

	static public function check($url)
		{
		return preg_match(self::$pattern,$url) > 0
			   ? true
			   : false;
		}

	}

class default_referer {
	var $type = "default";
	var $queryKey = 'q';
	var $stopWords = array();
	public function __construct($url)
		{
		$this->url = $url;
		//$this->log();
		return $this;
		}

	private function log()
		{
		$file = './referer.log';
		$line = date('r').' '.$this->url."\n";
		@file_put_contents($file,$line,FILE_APPEND);
		return $this;
		}

	public function extractKeywords()
		{
		$query = new normalize(urldecode($this->getQuery()));
		$query->toLower()
			  //->noAccents()
			  ->doTrim()
			  ->regreplace('#[^a-z0-9\- àáâãäåæçèéêëìíîïðñòóôõöøùúûýý]#ui',' ');
		$keywords =  explode(' ',$query);
		$ret = array();
		foreach ($keywords as $keyword)
			{
			if (count($ret) > MAX_KEYWORDS)
				break;
			if (in_array($keyword, $this->stopWords))
				continue;
			if (strlen($keyword) >=3)
				$ret[] = $keyword;
			}
		return $ret;
		}

	private function getQuery()
		{
		preg_match('#'.$this->queryKey.'=([^&\#/]*)#',$this->url,$match);
		if (!isset($match[1]))
			return false;
		return $match[1];
		}

	public function setStopWords($words)
		{
		$this->stopWords = array_merge($this->stopWords, $words);
		return $this;
		}

	}

class google_referer extends default_referer {
	var $type = "google";

	}
