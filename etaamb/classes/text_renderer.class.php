<?php
// Classe spécifique au rendu du texte (masques repris de notitia)
define ('RENDER_P_LIST_PATTERN','/[\s]{3}[(]?[a-z0-9][0-9]{0,}[.°)] [^;.]+[;.]\s/u');


class text_renderer
	{
	/// Public Functions
	static $version = 5;
	static private $dolog   = false;
	static private $observer= '';
	static private $debug	= false;
	static public function make($text,$ln)
		{
		if (RENDERER_CLASS_LOG) 
			{
			self::$dolog    = true;
			self::$observer = observer::getinstance();
			}

		self::$debug = RENDERER_TEST;
		$t = $text;
		if (self::$dolog) self::log('step 0 text length: '.strlen($t));
		if (self::$debug) self::debug_render($t,'INITIAL STATE');
		$t = self::clean_head($t);
		$t = self::clean_tail($t);
		if (self::test($t,$ln))
			{
			if (self::$dolog) self::log('Did validate article styling');
			$t = self::p_source($t,$ln);
			$t = self::p_title($t,$ln);
			$t = self::p_livre($t,$ln);
			$t = self::p_list($t,$ln);
			$t = self::p_chapitre($t,$ln);
			$t = self::p_section($t,$ln);
			$t = self::p_vus($t,$ln);
			$t = self::p_article($t,$ln);
			}
		else
			{
			if (self::$dolog) self::log('Did not validate article styling');
			if (self::test_list($t))
				{
				if (self::$dolog) self::log('Did validate list styling');
				$t = self::p_list($t,$ln);
				}
			}

		$t = self::p_subdivision($t,$ln);
		$t = self::p_endoftext($t,$ln);
		$t = self::p_paragraphs($t,$ln);
		if (self::$dolog) self::log('final text length: '.strlen($t));
		$t = trim($t);
		return $t;
		}

	static private function clean_head($t)
		{
		if (preg_match('#^\s{3}(FR|NL)\s(FR|NL)#',$t) > 0)
            {
            if (self::$dolog) self::log('Clean NL/FR Line');
			$t = preg_replace('#^[^\n]*\n{2,3}#','',$t);
            }
		if (preg_match('#^(Banque|Kruisp)#',$t) > 0)
            {
            if (self::$dolog) self::log('Clean belgielex Line (old)');
			$t = preg_replace('#^[^\n]*\n\n[^\n]*\n{3}#','',$t);
            }
		if (preg_match('#^\s{3}(belgique|belgië)lex.be#',$t) > 0)
            {
            if (self::$dolog) self::log('Clean belgielex Line (new)');
			$t = preg_replace('#^[^\n]*\n{2,3}#','',$t);
            }
		if (preg_match('#^\s{3}(Raad van State|Conseil d\'Etat)#',$t) > 0)
            {
            if (self::$dolog) self::log('Clean belgielex Line (new)');
			$t = preg_replace('#^[^\n]*\n{2,3}#','',$t);
            }
		if (preg_match('#^\s{3}ELI\s#',$t) > 0)
            {
            if (self::$dolog) self::log('Clean ELI');
            $t = preg_replace('#^([^\n]*\n){1,2}\s{3}http://www.*/eli/[^\n]*\n{2,3}#','',$t);
            }
		if (preg_match('#^\s{3}(einde|fin)\s#',$t) > 0)
            {
            if (self::$dolog) self::log('Clean Fin');
			$t = preg_replace('#^[^\n]*\n{2,3}#','',$t);
            }
		if (preg_match('#^\s{3}(Publicatie|Publié)\s#',$t) > 0)
            {
            if (self::$dolog) self::log('Clean Publication');
			$t = preg_replace('#^[^\n]*\n{1,2}#','',$t);
            }
		if (preg_match('#^\s{3}Numac\s:\s#',$t) > 0)
            {
            if (self::$dolog) self::log('Clean Numac');
			$t = preg_replace('#^[^\n]*\n{1}#','',$t);
            }

        //$t = preg_replace('#^\s{3}\w*\n*\s{3}[^\n]*\n*\s{3}#','',$t);
		if (self::$dolog) self::log('clean_head text length: '.strlen($t));
		return $t;
		}

	static private function clean_tail($t)
		{
        $revt = strrev($t);
        $revt = preg_replace('#^(.*?\n){2,6}(nigeb|tubed)\s{3}\n{3,6}#','',$revt);
        $t = strrev($revt);
		if (self::$dolog) self::log('clean_tail text length: '.strlen($t));
		return $t;
		}
	

	///	Filter definitions
	static private function p_source($t,$ln)
		{
		$params = array();
		$params["mask"] = '';
		$params["pattern"] = '/^[A-Z \',\n.]{1,}\n{3}/';
		if (self::$dolog) self::log('p_source text length: '.strlen($t));
		//if (self::$debug) self::debug_render($t,'P_SOURCE STATE');
		return self::apply($t,$params);
		}

	static private function p_title($t,$ln)
		{
		if (self::$debug) self::debug_render($t,'P_TITLE STATE');
		$params = array();
		$params["mask"] = '<span class="p_title">%s</span>'."\n";
		$params["pattern"] = '/^\s{0,}(\d{1,2}(er)?\s\w{1,}\s\d{4}([^\n]+\n){1,})/';
		$params["index"] = 1;
		if (self::$dolog) self::log('p_title text length: '.strlen($t));
		return self::apply($t,$params);
		}


	static private function p_livre($t,$ln)
		{
		$params = array();
		$params["mask"] = '<span class="p_livre">%s</span>'."\n";
		$params["pattern"] = $ln == 'fr' ?
			'/LIVRE ((I|V|X){1,}|PREMIER).[^\n]+/' :
			'/NL_SETTING/';
			
		if (self::$dolog) self::log('p_livre text length: '.strlen($t));
		return self::apply($t,$params);
		}
	
	static private function p_chapitre($t,$ln)
		{
		$params = array();
		$params["mask"] = '<span class="p_chapitre">%s</span>'."\n";
		$params["pattern"] = $ln == 'fr' ?
			'/CHAPITRE [\s\S]*?(?=Art(icle|\.)|Section \d{1,4}\.|\d\.)/' :
			'/HOOFDSTUK [\s\S]*?(?=Art(ikel|\.)|Afdeling \d{1,4}\.|\d\.)/';
			
		if (self::$dolog) self::log('p_chapitre text length: '.strlen($t));
		return self::apply($t,$params);
		}

	static private function p_section($t,$ln)
		{
		$params = array();
		$params["mask"] = '<span class="p_section">%s</span>'."\n";
		$params["pattern"] = $ln == 'fr' ?
			"/(^[\s\S]?|\s\s)(Sous-)?Section [^\n]*$(\n([^\s]{1,}\n)+)?/m" :
			"/(^[\s\S]?|\s\s)(Onder-)?Afdeling [^\n]*$(\n([^\s]{1,}\n)+)?/m" ;
			
		if (self::$dolog) self::log('p_section text length: '.strlen($t));
		return self::apply($t,$params);
		}

	static private function p_vus($t,$ln)
		{
		$params = array();
		$params["mask"] = '<span class="p_vus">%s</span>'."\n";
		$params["pattern"] = $ln == 'fr' ?
			'/^Vu([^:;]+[:;]$)/m' :
			"/^(Gelet op|Overwegende dat|Op)[^:;]+[:;]$/m";
			
		if (self::$dolog) self::log('p_vus text length: '.strlen($t));
		return self::apply($t,$params);

		}

	static private function p_article($t,$ln)
		{
		$reg_art = $ln == 'fr' ?
			'(Art(icle|\.) )(\d{1,})(\/\d{1,})?((er)?(\w{3,})?\. )' : //fr
			'(Art(ikel|\.) )(\d{1,})(\/\d{1,})?((er)?(\w{3,})?\. )' ; //nl
		$t  = str_replace('" Art','" &#65rt',$t);

		$params = array();
		$params["mask"] = '<p><span class="p_article">%s</span>';
		$params["pattern"] = '/'.$reg_art.'/';
		$t = self::apply($t,$params);

		$params["pattern"] = $ln == 'fr' ?
			'/'.$reg_art.'[\s\S]+?(?=Art(icle|\.)|<span|\n{2})/' : //fr
			'/'.$reg_art.'[\s\S]+?(?=Art(ikel|\.)|<span|\n{2})/' ; //nl

		$t = self::apply($t,$params);

		$params["pattern"] = '/'.$reg_art.'[^<][\s\S]+?(?=\n)/';
		$t = self::apply($t,$params);

		if (self::$dolog) self::log('p_article text length: '.strlen($t));
		return $t;
		}
	
	static private function p_list($t,$ln)
		{
		$params = array();
		$params["mask"] 	= '<span class="p_list">%s</span>';
		$params["pattern"]	= RENDER_P_LIST_PATTERN;
		$t  = self::apply($t,$params);
		return $t;
		}

	static private function p_paragraphs($t,$ln)
		{
        $t = '<p>'.preg_replace('#([\.;])[\r\n]\s*([A-Z])#','$1</p><p>$2',$t);
		$t = preg_replace('#([A-Z]\.)</p><p>([A-Z])#','$1 $2',$t);
		//$t = preg_replace('#[\r\n]([A-Z])#','<br>$1',$t);
        $t = preg_replace('#[\n\r]{3,}#','</p><p><br>',$t);
        $t = preg_replace('#[\n\r]{2}#','</p><p>',$t);
        $t = preg_replace('#[\n\r]{1}#',' ',$t);
		if (self::$dolog) self::log('p_paragraphs text length: '.strlen($t));
		return $t;
		}
		
	static private function p_subdivision($t,$ln)
		{
		$params = array();
		$params["mask"] = '<br>%s'."\n";
		$params["pattern"] = $ln == 'fr' ?
			"/^([0-9a-zA-Z]+[)°]|§ [0-9]+)/mu" :
			"/^([0-9a-zA-Z]+[)°]|§ [0-9]+)/mu" ;
			
		if (self::$dolog) self::log('p_subdivision text length: '.strlen($t));
		return self::apply($t,$params);
		}
	static private function p_endoftext($t,$ln)
		{
		//$pattern=("#^(\D*\d{1,2}(er)?\D*\d{4}\.)\n(([^\n]+\n)+)#");
		$pattern="#^([A-Z][^\d\n]+,[^\d\n]*\d{1,2}(er)?\D*\d{4}\.)$([\s\S]+)#m";
		$mask = '<span class="p_endoftext">%s</span>';

		if (self::$dolog) self::log('p_endoftext text length: '.strlen($t));
		return preg_replace_callback($pattern,function($m) use ($mask)
			{
			$text = trim($m[0]);
			return nl2br(sprintf($mask,$text));
			},
			$t);
		}

	/// Tools 
	static private function apply($t,$params)
		{
		$mask = $params["mask"];
		$pattern = $params["pattern"];
		$index = isset($params["index"]) ? $params["index"] : 0;

		return preg_replace_callback($pattern, function($m) use ($mask,$index)
			{
			$text = trim($m[$index]);
			return sprintf($mask,$text);
			},
			$t);
		}

	static private function test($t,$ln)
		{
		switch($ln)
			{
			case 'fr':
			$ret = preg_match('/^Vu([^:;]+[:;]$)/m',$t) || 
				   preg_match('/(Art(icle|\.) )(\d{1,})(\/\d{1,})?((er)?(\w{3,})?\. )/',$t);
				break;
			case 'nl':
			$ret = preg_match('/^Gelet op[^:;]+[:;]$/m',$t) || 
				   preg_match('/(Art(ikel|\.) )(\d{1,})(\/\d{1,})?((er)?(\w{3,})?\. )/',$t);
				break;
			default: $ret = false;
			}
		return $ret;
		}

	static private function test_list($t)
		{
		$pattern = RENDER_P_LIST_PATTERN;
		return preg_match($pattern,$t);
		}

	static private function log($m)
		{
		self::$observer->msg($m,'renderer');
		}

	static private function debug_render($t,$title)
		{

		echo "<br><hr><br><pre>"
				 .$title."\n--------------------\n\n"
				 .str_replace(
				 	array("\r","\n","\t"," "),
					array("\\r\r","\\n\n","\\t\t","&middot;"),
					$t)
				 ."</pre>";
		}
	}
