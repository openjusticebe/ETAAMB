<?php 
require_once('config.php');
require_once('config.default.php');
require_once(CLASS_DIR.'observer.class.php');
require_once(CLASS_DIR.'anoner.class.php');
require_once(CLASS_DIR.'router.class.php');
require_once(CLASS_DIR.'connector.class.php');
require_once(CLASS_DIR.'collection.class.php');
require_once(CLASS_DIR.'page_factory.class.php');
require_once(CLASS_DIR.'lang.class.php');
require_once(CLASS_DIR.'dict.class.php');
require_once(CLASS_DIR.'normalize.class.php');
require_once(CLASS_DIR.'monthcal.class.php');
require_once(CLASS_DIR.'docdisplay.class.php');
require_once(CLASS_DIR.'text_renderer.class.php');
require_once(CLASS_DIR.'parser.class.php');
require_once(CLASS_DIR.'referer.class.php');
require_once(CLASS_DIR.'highlighter.class.php');
require_once(CLASS_DIR.'statistics.class.php');
require_once(CLASS_DIR.'url.class.php');
require_once(CLASS_DIR.'tagger.class.php');
require_once('./tools/calendar.php');

$observer = observer::getInstance();
if (INDEX_LOG) {
	$observer->msg('Init','index','chapter');
	$observer->msg('Url: /'.$_SERVER['QUERY_STRING'],'index','chapter');
	}

$url = new url_factory(array('url' => url_factory::full()));
//////////////////// FLOW
if (INDEX_LOG) $observer->msg('Flow Start','index','chapter');
$ln = new lang();

if ($url->lang() !== 'false')
	$ln->set($url->lang());

define('CURRENT_LANG',strval($ln));

if (!$url->mask_Match() && url_factory::isRoot())
	{
	$url = new url_factory(array('page' => '/' ,'lang' => CURRENT_LANG));
	url_factory::redirect($url->raw(),302);
	}
else if (!$url->mask_Match())
	{
	if ($url->mask_Match(URLMASK_DOMAIN))
		$new_url = new url_factory(array('page' => $url->page(URLMASK_DOMAIN) ,'lang' => CURRENT_LANG));
	else if ($url->mask_Match(URLMASK_URL))
		$new_url = new url_factory(array('page' => $url->page(URLMASK_URL) ,'lang' => CURRENT_LANG));
	else if ($url->mask_Match(URLMASK_GET))
		$new_url = new url_factory(array('page' => $url->page(URLMASK_GET) ,'lang' => CURRENT_LANG));

	if (isset($new_url))
		url_factory::redirect($new_url->raw());
	else
		url_factory::url_error();
	}

$router = new url_router($url->page());

if ($router->noDateType())
	{
	$new_url = new url_factory(array('page' => 'pub'.$url->page()
									,'lang' => CURRENT_LANG));
	url_factory::redirect($new_url->raw());
	}


$dict = new dict(strval($ln));

if (INDEX_LOG) $observer->msg('Page Init','index','chapter');
$page = page_factory::getInstance($router->type());
$page->setData($router->getParsed())
     ->setTimes()
	 ->setDict($dict)
	 ->headers();

$ln->save();


$connector = new connector_class();
$connector->setConfig($DB_CONFIG);


$collection = new collection_class();
$collection->setConnector($connector)
	   	   ->setLanguage($dict->l());

$parser = new parser();
$parser->setCollection($collection)
	   ->setDict($dict);


$page->setCollection($collection)
	 ->setParser($parser);

if (!$page->isDataOk())
	{
	$reason = $page->error;
	unset($page);
	$page = page_factory::getInstance('error');
	$page->error = $reason;
	$page->setData($router->getParsed())
		 ->setDict($dict)
		 ->errorLog();
	}

$page->predisplay();



$referer = new referer();
$referer->setDict($dict);

if (REFERER_TEST)
	{
	$Turl = "http://www.google.be/search?q=%22loi+numac+%C3%A0+l%27application+arret%C3%A9+de+reconnaissance+mutuelle+des+d%C3%A9cisions+judiciaires+en+mati%C3%A8re+p%C3%A9nale+entre+les+Etats+membres+de+l%27Union+europ%C3%A9enne%22&hl=fr&prmd=ivnsb&ei=ICR7TdeBMIKLhQfD07n8Bg&start=10&sa=N";
	$referer->set($Turl);
	}



$highlighter = new highlighter();
$highlighter->keywords_set($referer->keywords());

$tagger = new tagger();
$tagger->setConnector($connector);

$page->init()
	 ->setHighlighter($highlighter)
	 ->setTagger($tagger)
	 ->setReferer($referer);

$p_title = new normalize($page->getTerm('title'));
$p_title->noHtml()
	  	->noAccents();
//////////////////// Begin of page display

//$p_main = $page->main();


if (INDEX_LOG) $observer->msg('Init Done. Display Started','index','chapter');
?><!DOCTYPE html>
<html>
	<head>
	   <title><?php echo $p_title?></title>
       <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	   <meta http-equiv="content-language" content="<?php echo $ln?>">
	   <meta name="description" content="<?php $page->display('description')?>" >
	   <meta http-equiv="X-UA-Compatible" content="IE=edge" >
	   <meta name="google-site-verification" content="liTBFiv7YynOwP85ZXv3hsDPriOmk7qSsd6LQyR4KaY" >
	   <meta name="msvalidate.01" content="F0E964CE6711F065A11268CFBD644C7B" >
	   <meta name="viewport" content="width=device-width, height=device-height, user-scalable=no, initial-scale=1">
       <meta name="robots" content="<?php switch(get_class($page))
                                        {
                                        case 'title':
                                        case 'numac':
                                            echo "index, follow";
                                            break;
                                        default:
                                            echo "noindex, follow";
                                        } ?>">
	   <link rel="shortcut icon" href="<?php echo a('assets/favicon.ico');?>">

	   <link href="<?php echo a('css/fontello.css')?>" rel="stylesheet"  type="text/css">

       <!-- Default smartphone -->
	   <link href="<?php echo a('css/smphone.css')?>" media="only screen and (max-device-width : 480px)" rel="stylesheet"  type="text/css">
	   <link href="<?php echo a('css/smphone.css')?>" media="screen and (max-width : 960px)" rel="stylesheet"  type="text/css">

       <!-- Iphones -->
	   <link href="<?php echo a('css/smphone.css')?>" media="only screen and (min-device-width: 320px) and (max-device-width : 480px) and (-webkit-min-device-pixel-ratio: 2)" rel="stylesheet"  type="text/css">
	   <link href="<?php echo a('css/smphone.css')?>" media="only screen and (min-device-width: 320px) and (max-device-width: 568px) and (-webkit-min-device-pixel-ratio: 2)" rel="stylesheet"  type="text/css">
	   <link href="<?php echo a('css/smphone.css')?>" media="only screen and (min-device-width: 375px) and (max-device-width: 667px) and (-webkit-min-device-pixel-ratio: 2)" rel="stylesheet"  type="text/css">
	   <link href="<?php echo a('css/smphone.css')?>" media="only screen and (min-device-width: 414px) and (max-device-width: 736px) and (-webkit-min-device-pixel-ratio: 3)" rel="stylesheet"  type="text/css">

       <!-- Galaxys -->
	   <link href="<?php echo a('css/smphone.css')?>" media="screen and (device-width: 320px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 2)" rel="stylesheet"  type="text/css">
	   <link href="<?php echo a('css/smphone.css')?>" media="screen and (device-width: 320px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 3)" rel="stylesheet"  type="text/css">
	   <link href="<?php echo a('css/smphone.css')?>" media="screen and (device-width: 360px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 3)" rel="stylesheet"  type="text/css">


	   <link href="<?php echo a('css/style.css');?>" media="screen and (min-width: 960px)" rel="stylesheet" type="text/css" >
	   <link href="<?php echo a('css/large.css');?>" media="only screen and (min-width: 1800px)" rel="stylesheet" type="text/css" >
	   <link href="<?php echo a('css/textdisplay.css');?>" media="" rel="stylesheet" type="text/css" > 
	   <link href="<?php echo a('css/print.css');?>" media="print" rel="stylesheet" type="text/css" > 
       <?php
       if (EU_POPUP)
           echo '<link href="'.a('css/eupopup.css').'" media="" rel="stylesheet" type="text/css" >';

	   if (ENVIRONMENT == 'dev')
           echo '<link href="'.a('css/debug.css').'" media="screen" rel="stylesheet" type="text/css" >';

	   $page->meta();
	   ?>

	   <script type="text/javascript">
		   var _gaq = _gaq || [];
		   _gaq.push(['_setAccount', 'UA-4219222-6']);
		   _gaq.push(['_trackPageview']);

		   (function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
		</script>
	</head>

	<body class="<?php if (EU_POPUP) echo 'eupopup eupopup-bottom';?>">
	<?php //flush();?>
		<div id="main">
			<?php echo $page->main();
			if (INDEX_LOG) $observer->msg('Main Page Display Done','index','chapter');
			?>
		</div>
		<div id="header">
			<?php
			if ($page->header()) echo $page->header(); else
			include('./page_parts/header.php');

			?>
		</div>
		<div id="up_arrow"><a href="#">^</a></div>
        <?php
            if ($page->footer()) echo $page->footer(); 
            if (LEGAL_FOOTER) include('./page_parts/footer.php');
        ?>
		<?php 
			if (PRIVATE_LIFE_FORM) 
				include('./page_parts/private_life.php');

			if (INDEX_LOG) 
				$observer->msg('All Done','index','chapter');

			if (!OBSERVER_SILENT_LOG)
				{
				echo '<div id="debug" class="third">';
				printf('<div>%s</div>', $observer->buff_read('<br>'));
				echo '</div>';
				}
			else
				$observer->buff_read();


		if (ANONYMISE_TEST) echo '<div style="position:fixed;bottom:0;right:0;left:0;height:25px;	
								  text-align:center;font-weight:bold;background-color:red;color:white;z-index:1000">
								  Warning: Anon Test Active </div>';
		?>
   <script type="text/javascript" src="<?php echo a('js/jquery-1.4.2.min.js');?>"></script>
   <script type="text/javascript" src="<?php echo a('js/onload.js');?>"></script>

   <?php
   if (EU_POPUP)
       echo '<script type="text/javascript" src="'.a('js/jquery-eucookielaw.js') .'"></script>';
   ?>
   <script type="text/javascript">
   dict.set('<?php echo $dict->l();  ?>');
   var Words = <?php echo $highlighter->js_integrate(); ?>;
   var Host = '<?php echo $url->host();?>';
   </script>
	</body>
</html><?php

//////////////////// Some Functions
function l($s)
   {
   global $dict;
   echo $dict->get($s);
   }

function a($s)
	{
	if (preg_match('#\.[a-z]{2,4}$#i',$s) == 0)
		$s .= '.html';
	if (strpos($s,'.html') !== false)
		{
		$url = new url_factory(array('page' => $s,'lang' => CURRENT_LANG));
		return $url->raw();
		}
	return '/'.$s;
	}

function aP($s)
	{
	$s= preg_replace_callback('#(<a.*href=")([^"]+)(">[^<]+</a>)#'
                ,function($match) {
				   return $match[1].a($match[2]).$match[3];
                }
				// ,create_function(
				// 	'$match'
				//    ,'return $match[1].a($match[2]).$match[3];')
				,$s);
	return $s;
	}



function normalize($content)
    {
    $str = strip_tags($content);
    $str = strtolower($str);
    $a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ'; 
    $b = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'; 
    $str = utf8_decode($str);     
    $str = strtr($str, utf8_decode($a), $b); 
    return $str;
    }

function c_type($type)
	{
	global $dict;
	$find =    array('arrete','ministeriel','region','decret','decision','reglement','cooperation','coordonnee');
	$replace = array('arrêté','ministériel','région','décret','décision','règlement','coopération','coordonnée');
	return str_replace($find,$replace, $type);
	}

function priv_filter($numac,$string,$anon)
	{
	return $string;
	if ($anon === 0)
		return $string;
	return anoner::anonymise($string);
	}

function array2html($arr,$he=array())
	{
	$h = '<table border=1>';
	$h .= '<tr>';
	foreach ($he as $head)
		{
		$h .= "<th>$head</th>";
		}
	$h .= '</tr>';
	foreach ($arr as $key => $val)
		{
		$h .= "<tr><th>$key</th>";
		if (is_array($val))
			{
			foreach ($val as $v)
				$h.= "<td>$v</td>";
			}
		else
			{
			$h.= "<td>$val</td></tr>\n";
			}
		}
	return $h.'</table>';
	}

function page_type($p)
	{
	if ($p instanceof day)
		return 'day';
	if ($p instanceof month)
		return 'month';
	if ($p instanceof year)
		return 'year';
	if ($p instanceof numac)
		return 'numac';
	if ($p instanceof title)
		return 'title';
	if ($p instanceof index)
		return 'index';
	if ($p instanceof error)
		return 'error';
	return false;
	}
