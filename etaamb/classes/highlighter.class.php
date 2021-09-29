<?php
/*
Highlighter: avec des mots clefs, cette classe
 peut mettre ces mots en surbrillance, avec une
 ancre assiciée.
*/

class highlighter
	{
	var $keywords = array();
	public function keywords_set($keywords)
		{
		foreach ($keywords as $keyword)
			{
			$key_norm = new normalize($keyword);
			$this->keywords[] = $key_norm->toLower()
										 ->noHtml()
										 ->doTrim()
										 ->str();
			}
		return $this;
		}

	public function parse($text)
		{
		return $this->text_parse($text);
		}

	private function text_parse($text)
		{
		$i=0;
		foreach ($this->keywords as $keyword)
			{
			$keyword = $this->keyword_clean($keyword);
			$mask = "#(?<=[ a-z0-9,.;:])($keyword)(?=[ a-z0-9,.;:])#iu";
			$repl = "<span class=\"match keyw_$i\">$1</span>";
			$text = preg_replace($mask,$repl,$text);
			$i++;
			}
		return $text;
		}

	public function js_integrate()
		{
		if (count($this->keywords) == 0) return '[]';
		return '[\''.implode("','",$this->keywords).'\']';
		}

	private function keyword_clean($word)
		{
		$letters = array(
			"a" => "àáâãäåæ",
			"c" => "ç",
			"e" => "èéêë",
			"i"	=> "ìíîï",
			"o" => "òóôõöø",
			"u" => "ùúû");
		foreach ($letters as $key => $val)
			{
			$fin = "#([$val])#u";
			$rep = "[$1$key]";
			$word = preg_replace($fin,$rep,$word);
			}
		return $word;
		}

	public function keywords_count()
		{
		return count($this->keywords);
		}
	}
