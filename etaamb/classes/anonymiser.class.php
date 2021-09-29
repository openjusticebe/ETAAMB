<?php
/*
 * Anonymiser class
 */

class anonymiser {
	private $log = false;
	private $hyph_step = 1;
	private $hyph_dict = array( 'norm' => array('NULL' => 0)
							  , 'rev'  => array(0 => 'NULL'));
	private $hyph_dict_index = 0;
	function log($m)
		{
		if ($m === true)
			{
			$this->log = true;
			return $this;
			}

		if ($this->log)
			echo $m."\n";
		return $this;
		}

	function path_sethyphened($path)
		{
		$this->hyphened_path = $path;
		$this->log("Hyphened path = $path");
		return $this;
		}

	function path_setraw($path)
		{
		$this->raw_path = $path;
		$this->log("Raw path = $path");
		return $this;
		}

	function paths_parse()
		{
		$this->words_list = $this->path_raw_parse($this->raw_path);
		$this->hyph_table = $this->path_hyph_parse($this->hyphened_path);
		return $this;

		}

	function path_raw_parse($path)
		{
		$words_raw = file_get_contents($path);
		$words = explode("\n",$words_raw);
		$this->log(sprintf("Raw words parsed, count : %s",count($words)));
		return $words;
		}

	function path_hyph_parse($path)
		{
		$words_raw  = file_get_contents($path);
		$words_list = explode("\n",$words_raw);
		$hyph_array = array();
		$i=1;
		foreach ($words_list as $word)
			{
			$elements = explode('-',$word);
			$hyph_array = $this->insert_hyph($hyph_array,array_reverse($elements));
			if ($i % 220 == 0) {print_r($hyph_array);die;}
			if ($i % 10000 == 0) $this->log("On word #$i, array count:".count($hyph_array));
			$i++;
			}
		$this->log(sprintf("Hyphened words parsed, count : %s",count($hyph_array)));
		print_r($hyph_array);
		return $hyph_array;
		}

	function insert_hyph($array,$elements)
		{
		if (!isset($elements[0]))
			return $array;

		$l = count($elements);
		
		if (isset($elements[$l-2]))
			$next = $elements[$l-2];
		else
			$next = 'NULL';

		$next = $next;

		$current = array_pop($elements);

		if (!isset($array[$current]))
			$array[$current] = array( $next => 1 );
		else if (!isset($array[$current][$next]))
			$array[$current][$next] = 1;
		else
			$array[$current][$next]++;

		return $this->insert_hyph($array,$elements);
		}

	function hyph2num($hyph)
		{
		if ($hyph == 'NULL') return 0;
		if (!isset($this->hyph_dict['norm'][$hyph]))
			{
			$this->hyph_dict['norm'][$hyph] = $this->hyph_dict_index;
			$this->hyph_dict['rev'][$this->hyph_dict_index] = $hyph;
			return $this->hyph_dict_index++;
			}
		else 
			return $this->hyph_dict['norm'][$hyph];
		}

	}
