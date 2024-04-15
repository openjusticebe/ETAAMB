<?php
/* Bridge class with erlang anoner app */

class anoner {
	// static $single_cmd = "erl_call -a 'anoner check [%s,\"%s\"]' -n anoner@localhost -c FMMCJG-zp98-oaz913-kjsd1414";
	// static $multi_cmd  = "erl_call -a 'anoner multi_check [%s,\"%s\"]' -n anoner@localhost -c FMMCJG-zp98-oaz913-kjsd1414";
	// static $sequential_cmd  = "erl_call -a 'anoner sequence_check [%s,\"%s\"]' -n anoner@localhost -c FMMCJG-zp98-oaz913-kjsd1414";
	static $levels 	   = array('50'=>'red','60'=>'orange','80'=>'green');
	//static $mode	   = 'single';
	//static $mode	   = 'multi';
	static $mode	   = 'sequential';
	static $cache	   = array();
	static $lang	   = false;

	static $common_patterns = array(
        "selor_selection" => array(
            'mask' => "#\s{3}(\d{1,4}\. )[A-Z][A-Za-zÀ-ÿ \-']+, [A-Z][A-Za-zÀ-ÿ \-']+, (\d{4} )?[A-Z][A-Za-zÀ-ÿ \-'()]+\n#",
            'repl' => '<br>$1<span class="anonymized">****</span>, <span class="anonymized">*****</span>, <span class="anonymized">*****</span>, <span class="anonymized">****</span>'
        ),
        "infrabel_agents" => array(
            'mask' => "#([A-Z'\-]+){1,} [A-Z][A-Za-zÀ-ÿ \-']+,? \(id. ?[0-9]+\);?#",
            'repl' => '<span class="anonymized">****</span> <span class="anonymized">*****</span> (id. <span class="anonymized">*</span>)'
        ),
        "infrabel_id" => array(
            'mask' => "#\(id. ?[0-9]+\)#",
            'repl' => '(id. <span class="anonymized">*</span>)'
		)
    );

	static function test()
		{
        switch(ANON_SERVICE)
            {
            case 'etaamb':
                return curl_init(sprintf("%s/status", ANON_HOST)) !== false;
            default:
                return false;
            }
		}

	function check_token($token)
		{
        throw new Exception('Disabled : code obsolete');

        // FIXME
        /*
		$command = sprintf(self::$single_cmd,CURRENT_LANG == 'fr' ? 'french' : 'dutch',$token);
		$res = exec($command);
		return (int)$res;
         */
		}

	static function check_list($list)
		{
        $output = [];

        switch(ANON_SERVICE)
            {
                case 'etaamb':
                    $url = sprintf("%s/sequence_check", ANON_HOST);
                    $t = 0;

                    $payload = [
                        'lang' => self::$lang == 'fr' ? 'french' : 'dutch',
                        'string' => $list,
                    ];
                    while ($t < 4) {
                        try {
                            $ch = curl_init($url); 
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                            $result = curl_exec($ch);
                            curl_close($ch);
                            break;
                        } catch (Exception $e) {
                            $t++;
                        }
                    }
                    $output = explode(' ', $result);
                    break;
                default:
                    throw new Exception('No valid anon service selected');
            }

        return $output;
		}


	static function anonymise($text,$lang)	
		{
		if (!self::test()) self::error();

		self::log("Anonymising in mode ".self::$mode." : "
				 .substr($text,0,120)."...");

		self::$lang=$lang;
		switch(self::$mode)
			{
			case 'single':
				return self::anon_single($text);
			case 'multi':
			case 'sequential':
				return self::anon_multi($text);
			}
		}

	static function badwords_test($text)
		{
		$prep_text = self::prepare($text);
		$tokens    = self::tokenize($prep_text);
		$scores    = self::multi_score($tokens);
		$badtable  = self::bad_tokens($scores);
		return count($badtable);
		}

	static function anon_multi($text)
		{
		$prep_text = self::prepare($text);
		$tokens    = self::tokenize($prep_text);
		$scores    = self::multi_score($tokens);
		$badtable  = self::bad_tokens($scores);
		$badcount  = count($badtable);
		self::log("Token Count : ".count($tokens).", Tokens:".implode(', ',$tokens));
		self::log("Bad Count : ".$badcount.", Table : ".observer::table2html($badtable));
	
		if ($badcount < 1000)
			{
			$text	   = self::remove_common_patterns($text);
			$text	   = self::remove_dates($text);
			$text 	   = self::remove_gender($text);
			$text 	   = self::remove_newnames($text);
			$text 	   = self::remove_living_place($text);
			$text 	   = self::remove_capitalized($badtable, $text);
			$text      = self::parse_text($badtable,$text);
			}
		else
			{
			$text	   = self::remove_common_patterns($text);
			$text	   = self::remove_dates($text);
			$text 	   = self::remove_gender($text);
			$text 	   = self::remove_living_place($text);
			$text 	   = self::quick_parse($badtable,$text);
			}

		return $text;

		/*
		if (ANONYMISE_TEST)
			{
			$prep_text = self::prepare($text);
			$tokens    = self::tokenize($prep_text);
			$scores    = self::multi_score($tokens);
			$badtable  = self::bad_tokens($scores);
			$badcount  = count($badtable);
			//self::log("Token Count : ".count($tokens).", Tokens:".implode(', ',$tokens));
			self::log("Bad Count : ".$badcount.", Table : ".observer::table2html($badtable));
	
			if ($badcount < 1000)
				{
				$text	   = self::remove_common_patterns($text);
				$text	   = self::remove_dates($text);
				$text 	   = self::remove_gender($text);
				$text 	   = self::remove_newnames($text);
				$text 	   = self::remove_living_place($text);
				$text 	   = self::remove_capitalized($badtable, $text);
				$text      = self::parse_text($badtable,$text);
				}
			else
				{
				$text	   = self::remove_dates($text);
				$text 	   = self::remove_gender($text);
				$text 	   = self::remove_living_place($text);
				$text 	   = self::quick_parse($badtable,$text);
				}
			}
		else
			{
				$text	   = self::remove_common_patterns($text);
				$text	   = self::remove_dates($text);
				$text 	   = self::remove_gender($text);
				$text 	   = self::remove_living_place($text);
				$text 	   = self::remove_newnames($text);

			$prep_text = self::prepare($text);
			$tokens    = self::tokenize($prep_text);
			$scores    = self::multi_score($tokens);
			$badtable  = self::bad_tokens($scores);
			$badcount  = count($badtable);
			self::log("Bad Count : ".$badcount.", Table : ".observer::table2html($badtable));
			$text 	   = self::remove_capitalized($badtable, $text);
			$text      = self::parse_text($badtable,$text);
			}
		*/
	
		}

	static function stamp($text)
		{
		return crc32($text);
		}

	static function cache($stamp)
		{
		return isset(self::$cache[$stamp]) ? self::$cache[$stamp] : false;
		}

	static function store($stamp,$text)
		{
		self::$cache[$stamp] = $text;
		}

	static function anon_single($text)
		{
		$prep_text = self::prepare($text);
		$tokens    = self::tokenize($prep_text);
		$scores    = self::scores($tokens);
		$badtable  = self::bad_tokens($scores);
		self::log('Bad Table : '.observer::table2html($badtable));
		$text      = self::parse_text($badtable,$text);
		return $text;
		}

	static function remove_dates($text)
		{
		$mask = '#(^|[^a-z])(née?[ \s]+[aà][ \s]+|geboren[ \s]+te[ \s]+)([a-zÀ-ÿ\- \s,.\']{1,30}(?:\([^)]+\)\s?)*)([ \s,]+(?:le|op|in|en)[ \s]+)((?:\d{1,2}(?:er)?[ \s]+\w*[ \s]+)?\d{4})#uUi';
		$rep = ANONYMISE_TEST ? '$1<span class="anon_dates">$2$3$4$5</span>'
							  : '$1$2 <span class="anonymized">*****</span> $4 <span class="anonymized">**</span> <span class="anonymized">*****</span> <span class="anonymized">****</span>';
		return preg_replace($mask,$rep, $text);
		}

	static function remove_living_place($text)
		{
		$mask = '#(wonende te|demeurant à|résidant à) ([^\s,.;:]+)([\s,.;:])#Ui';
		$rep = ANONYMISE_TEST ? '$1 <span class="anon_living">$2</span>$3'
							  : '$1 <span class="anonymized">*****</span>$3';
		return preg_replace($mask,$rep, $text);
		}

	static function remove_common_patterns($text)
		{
		foreach (self::$common_patterns as $pat)
			{
			$rep = ANONYMISE_TEST ? '<span class="anon_common">$0</span>'
								  : $pat['repl'];
			$text = preg_replace($pat['mask'], $rep, $text);
			}
		return $text;
		}

	static function remove_capitalized($badtable, $text)
		{
		$token_chunks = array_chunk($badtable,30);
		$all_bads = array_map(function($d) { return $d['token'];},$badtable);
		foreach ($token_chunks as $chunk)
			{
			$tokens = array_map(function($d) { return $d['token'];},$chunk);
			$callback = function ($matches) use ($all_bads, $tokens)
							{
							$term = strtolower($matches[2]);
							if (in_array($term,$all_bads))
								return $matches[0];
							return ANONYMISE_TEST 
									? $matches[1].'<span class="anon_capit">'.$matches[2].'</span>'.$matches[3]
									: $matches[1].'<span class="anonymized">****</span>'.$matches[3];
							};

			$tokenlist = sprintf('(?:%s)',implode('|',$tokens));
			$mask_pre  = sprintf('#((?i)%s[ ,-]+)([A-Z][a-zÀ-ÿ]+(?:-[A-Z][a-zÀ-ÿ]+)?)()(?![a-zÀ-ÿ])#U'
								,$tokenlist);	
			$mask_post = sprintf('#(?<![a-zÀ-ÿ])()([A-Z][a-zÀ-ÿ]+(?:-[A-Z][a-zÀ-ÿ]+)?)((?i)[ -,]+%s)#U'
								,$tokenlist);	

			$text = preg_replace_callback($mask_pre,$callback,$text);
			$text = preg_replace_callback($mask_post,$callback,$text);
			}
		return $text;
		}
	
	static function remove_gender($text)
		{
		$mask = '#(?<![a-zA-ZÀ-ÿ])(Mme|Mlle|M\.|Mevr\.|Mej\.|heer)((?:[^a-zA-ZÀ-ÿ]+[A-Z][a-zÀ-ÿ]+)+)([^a-zÀ-ÿ][a-zÀ-ÿ])#U';
		$rep = ANONYMISE_TEST ? '<span class="anon_gender">$1</span><span class="anon_name">$2</span>$3'
							  : '<span class="anonymized">*****</span>$3';
		return preg_replace($mask,$rep, $text);
		}

	static function remove_newnames($text)
		{
		$mask = '#«([^»]+)»#U';
		$rep = ANONYMISE_TEST ? '«<span class="anon_newname">$1</span>»'
							  : '«<span class="anonymized">*****</span>»';
		return preg_replace($mask,$rep, $text);
		}

	static function parse_text($badtable,$text)
		{
		$token_chunks = array_chunk($badtable,30);
		foreach ($token_chunks as $chunk)
			{
			$tokens    = array_map(function($d) { return $d['token'];},$chunk);
			$tokenlist = sprintf('(?:%s)',implode('|',$tokens));
			$rep = ANONYMISE_TEST ? '<span class="anon">$1</span>'
								  : '<span class="anonymized">****</span>';
			$mask  = sprintf('#(?<![a-zÀ-ÿ])((?:[a-zÀ-ÿ]\')?%s)(?![a-zÀ-ÿ])#iU',$tokenlist);
			$text  = preg_replace($mask,$rep,$text);
			}
		return $text;
		}

	static function quick_parse($badtable, $text)
		{
		foreach($badtable as $bad)
			{
			$token = $bad['token'];
			$level = self::get_level($bad['score']);
			$rep = ANONYMISE_TEST ? '<span class="anon" title="'.$token.'">'.$token.'</span>'
								  : '****';
			$text = str_ireplace($token,$rep,$text);
			}

		return preg_replace('#\*+#','<span class="anonymized">****</span>',$text);
		}

	static function get_level($score)
		{
		$return = 'red';
		$score = floor($score/10)*10;
		foreach (self::$levels as $max => $level)
			{
			if ($score >= (int)$max)
				$return = $level;
			}
		return $return;

		}

	static function bad_tokens($scores)
		{
		$bads = array();
		foreach ($scores as $token => $score)
			{
			if ($score < 100)
				{
				$bads[] = array('token' => $token
							   ,'score' => $score);
				}
			}
		return $bads;
		}

	static function scores($tokens)
		{
		$scores = array();
		foreach ($tokens as $token)
			{
			if (self::is_word($token))
				$scores[$token] = self::check_token($token);
			}
		return $scores;
		}

	static function multi_score($tokens)
		{
		$list	   = implode(' ',$tokens);
		$scorelist = self::check_list($list);

		$scores = array();
		for($i=0,$l=count($tokens);$i<$l;$i++)
			{
			$scores[$tokens[$i]] = $scorelist[$i];
			}
		return $scores;
		}

	static function prepare($text)
		{
		$text = preg_replace('#<[^>]*>#Ui','',$text);
		$text =  preg_replace('#[^a-zA-Z\'\-À-ÿ]+#U',' ',$text);
		return $text;
		}

	static function tokenize($text)
		{
		$text = strtolower($text);
		$tokens = preg_split('#[^a-zA-ZÀ-ÿ]#U',$text);
		$tokens = array_unique($tokens);
		sort($tokens);
		if (empty($tokens[0]))
			array_shift($tokens);
		return $tokens;
		}

	static function is_word($text)
		{
		return preg_match('#[a-zA-ZÀ-ÿ\-\']{2,}#U',$text) > 0 ? true : false;
		}
		
	static function error()
		{
		header('HTTP/1.1 503 Service Unavailable');
		echo '<h2>Requested page currently unavailable</h2>
			  <p>The requested page may contain sensitive personal data.<br>
			  The anonymising filter being currently unavailable, access to this page has been blocked</p>
			  <p>Access will be restored as soon as possible.</p>
			  <p><a href="https://www.etaamb.be">Back to the Etaamb homepage...</a></p>
			  ';
		 die;
		}

	static function log($msg,$t='')
		{
		if (ANONER_LOG)
			observer::log($msg,'anoner',$t);
		}
}
