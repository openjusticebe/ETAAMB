<?php
// Classe de normalisation, enfin bien faite.
define('NO_SPACE',false);

class normalize
	{
    private $rawterm;
    private $term;

	var $stopwords = array (
			"fr" => "alors au aucuns aussi autre avant avec avoir bon car ce cela ces ceux chaque ci comme comment dans des du dedans dehors depuis deux devrait doit donc dos droite début elle elles en encore essai est et eu fait faites fois font force haut hors ici il ils je	juste la le les leur là ma maintenant mais mes mine moins mon mot même ni nommés notre nous nouveaux ou où par parce parole pas personnes peut peu pièce plupart pour pourquoi quand que quel quelle quelles quels qui sa sans ses seulement si sien son sont sous soyez sujet sur ta tandis tellement tels tes ton tous tout trop très tu valeur voie voient vont votre vous vu ça étaient état étions été être",
			"nl" => "aan af al alles als ben bij daar dan dat de der deze die dit doch doen door dus een eens en er ge geen haar had heb hebben heeft hem het hier hij hoe hun iets ik in is ja je kan kon maar me meer men met mij mijn moet na naar niet niets nog nu of om omdat ons ook op over reeds te tegen toch toen tot u uit uw van veel voor want waren was wat we wel werd wezen wie wij wil zal ze zei zelf zich zij zijn zo zou"
			);

	public function __construct($term)
		{
		$this->rawterm=$term;
		$this->term=$term;
		return $this;
		}
	
	public function noHtml()
		{
		$this->term = strip_tags($this->term);
		return $this;
		}

	public function toLower()
		{
		$this->term =  strtolower($this->term);
		return $this;
		}

	public function nl2br()
		{
		$this->term = nl2br($this->term);
		return $this;
		}

    private function utf8_dec($term)
        {
        if (!$term) return '';
        return mb_convert_encoding($term, "UTF-8", mb_detect_encoding($term));
        }
	
	public function noAccents()
		{
		$a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ'; 
		$b = 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYBsaaaaaaaceeeeiiiidnoooooouuuyybyrr'; 
		$temp = $this->utf8_dec($this->term);     
		$this->term = strtr($temp, $this->utf8_dec($a), $b); 
		return $this;
		}

	public function noXmlEntities()
		{
		$this->term = preg_replace('#&[^;]+;#','',$this->term);
		$this->term = preg_replace('#\\x\d{2,4}#','',$this->term);
		return $this;
		}

	public function escapeAmpersands()
		{
		$this->term = str_replace('&','&amp;',$this->term);
		return $this;
		}

	public function noSpaces()
		{
		$this->term = preg_replace('#\s{0,}#','',$this->term);
		return $this;
		}

	public function replace($f,$r)
		{
		$this->term = str_replace($f,$r,$this->term);
		return $this;
		}

	public function regreplace($f,$r)
		{
		$this->term = preg_replace($f,$r,$this->term);
		return $this;
		}

	public function stopwords()
		{
		$words = explode(' ',$this->term);
		$ret = array();
		foreach ($words as $keyword)
			{
			if ( strpos($keyword, $this->stopwords['nl']) !== false
			   ||strpos($keyword, $this->stopwords['fr']) !== false)
				continue;
			if (strlen($keyword) >=3)
				$ret[] = $keyword;
			}
		$this->term = implode(' ',$ret);
		return $this;
		}

	public function length($l,$ellipsis='...')
		{
		if (strlen($this->term) > $l)
			{
			$this->term = mb_substr($this->term,0,$l-strlen($ellipsis)+1).$ellipsis;
			}
		return $this;
		}

	public function truncate($l,$ellipsis=' [...]')
		{
		if (mb_strlen($this->term) > $l)
			{
			$len = mb_strpos($this->term,' ',$l);
			$this->term = mb_substr($this->term,0,$len).$ellipsis;
			}
		return $this;
		}

	public function append($s,$space=true)
		{
		$this->term .= ($space? ' ' : '').$s;
		return $this;
		}

	public function doTrim()
		{
		$this->term = trim($this->term);
		return $this;
		}

    public function fixSpace()
        {
        $this->term = preg_replace("#\s{1,}#"," ", $this->term);
        $this->term = preg_replace("#([\.;:])([\w\d])#","$1 $2", $this->term);
		return $this;
        }

	public function str()
		{
		return $this->term;
		}

	public function __toString()
		{
		return $this->str();
		}
	}

