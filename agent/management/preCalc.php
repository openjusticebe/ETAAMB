#!/usr/bin/php
<?php 
// Parsing options
$LARGE_TEXTS = false;
$SINGLE_STEP = false;
$PRINT_QUERY = false;
$ANON_TEXTS  = false;

foreach ($argv as $arg)
	{
	switch ($arg)
		{
		case 'large_texts': 
			$LARGE_TEXTS = true;break;
		case 'anon_texts':
			$ANON_TEXTS = true;break;
		case 'print_query':
			$PRINT_QUERY = true;break;
		case 'single_step':
			$SINGLE_STEP = true;break;
		case 'help'	: 
			echo "
Etaamb Precalc interface
-----------------------

Options:
  large_texts		precalc the large texts
  anon_texts		precalc anonymised texts
  print_query		print query and die
  single_step		perform single step and die
";
			die;
			break;
		}
	}
// Doc Precalc Module
chdir(dirname(__FILE__));

define('CLASS_DIR','/etaamb/classes/');
define('CURRENT_LANG','fr');
define('EMOD_KEYWORDS_PATH',
	   '../../c_stuff/relation_tokenizer/');
$_SERVER['HTTP_HOST'] = '127.0.0.1';
require_once('/etaamb/config.php');
require_once('/etaamb/config.default.php');
require_once(CLASS_DIR.'default_page.class.php');
require_once(CLASS_DIR.'observer.class.php');
require_once(CLASS_DIR.'anoner.class.php');
require_once(CLASS_DIR.'numac.class.php');
require_once(CLASS_DIR.'connector.class.php');
require_once(CLASS_DIR.'collection.class.php');
require_once(CLASS_DIR.'dict.class.php');
require_once(CLASS_DIR.'parser.class.php');
require_once(CLASS_DIR.'text_renderer.class.php');
require_once(CLASS_DIR.'normalize.class.php');
require_once(CLASS_DIR.'statistics.class.php');
require_once(CLASS_DIR.'docdisplay.class.php');
require_once(CLASS_DIR.'highlighter.class.php');
require_once(CLASS_DIR.'tagger.class.php');


$connector = new connector_class();
$connector->setConfig($DB_CONFIG);
$observer = observer::getInstance();


$raw_query = 'select numac from text 
			  	left join render_cache using (numac,ln) 
				where render_cache.numac is null group by numac';

if ($LARGE_TEXTS)
	$raw_query = 'select numac from text where
				  length > 50000
				  and numac in ('.$raw_query.')
				  group by numac';
else if ($ANON_TEXTS)
	$raw_query = 'select numac from docs where anonymise = 1
				  and numac in ('.$raw_query.') group by nymac';
else 
	$raw_query .= ' limit 0,'.PRECALC_NUM;

$parser = array();
$dict = array();
$collect = array();
$highlighter = array();
$tagger = array();
foreach(array('nl','fr') as $ln)
	{
	$precalc_lang = $ln;
	$dict[$ln]   = new dict(strval($ln));
	$highlighter[$ln] = new highlighter();
	$collection[$ln] = new collection_class();
	$collection[$ln]->setConnector($connector)
					->setLanguage($ln);
	$parser[$ln] = new parser();
	$parser[$ln]->setCollection($collection[$ln])
				->setDict($dict[$ln]);
	$tagger[$ln] = new tagger();
	$tagger[$ln]->setConnector($connector);
	}

$query  = sprintf($raw_query);
if ($PRINT_QUERY) {echo "\n".$query."\n";die;}
printf("\n---- waiting for query...  ");
$numacs = $connector->query($query,Q_FLAT);
echo "done.\n";
$i		= 0;
foreach ($numacs as $numac)
	{
	$page = array();
	$data = array($numac);
	foreach(array('nl','fr') as $ln)
		{
		$page[$ln] = new numac();
		$page[$ln]->setData($data)
				  ->setDict($dict[$ln])
				  ->setTagger($tagger[$ln]->reset())
				  ->setCollection($collection[$ln]->reset())
				  ->setParser($parser[$ln]->reset());

		if ($page[$ln]->isDataOk(NO_REDIRECT))
			$page[$ln]->init()
				      ->preCalc(false);
		}
	if ($SINGLE_STEP)
		{
		echo "Done single numac: $numac.\n";
		die;
		}
	$i++;
	if ($i%10 == 0)  echo ".";
	if ($i%100 == 0) echo "\n";
	}

exit;

function a($s)
	{
	if (preg_match('#\.[a-z]{2,4}$#i',$s) == 0)
		$s .= '.html';
	return WEB_DIR.$s;
	}

function aP($s)
	{
	$s= preg_replace_callback('#(<a.*href=")([^"]+)(">[^<]+</a>)#'
				,create_function(
					'$match'
				   ,'return $match[1].a($match[2]).$match[3];')
				,$s);
	return $s;
	}

function c_type($type)
	{
	global $dict;
	$find =    array('arrete','ministeriel','region','decret','decision','reglement','cooperation','coordonnee');
	$replace = array('arrêté','ministériel','région','décret','décision','règlement','coopération','coordonnée');
	return str_replace($find,$replace, $type);
	}

function priv_filter($numac,$string)
	{
	global $PRIVACY;
	if (!isset($PRIVACY[''.$numac])) return $string;
	foreach ($PRIVACY[$numac] as $name)
		$string = preg_replace("#$name#ui",'*****',$string);
	return $string;
	}
