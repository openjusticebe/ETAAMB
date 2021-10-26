<?php
// Etaamb config file

// Set location of class declarations
if (!defined('CLASS_DIR'))
	define("CLASS_DIR",'./classes/');

// Set folder containing of etaamb code
if (!defined('WEB_DIR'))
	define("WEB_DIR",'/');

// Set date format
if (!defined('DATE_FORMAT'))
	define("DATE_FORMAT",'d/m/Y');

// Set anon service type
if (!defined('ANON_SERVICE'))
    define('ANON_SERVICE', 'etaamb');

if (!defined('ANON_HOST'))
    define('ANON_HOST', 'http://localhost:8050');

// Set code version (updates cached renders)
if (!defined('VERSION'))
	define("VERSION","0.1");

// Set how many pages should be precalculated on each iteration
if (!defined('PRECALC_NUM'))
	define("PRECALC_NUM",10000);

if (!defined('MAX_PRECALCED_DOCS'))
	define("MAX_PRECALCED_DOCS",50);

if (!defined('PRECALC_ERROR_TRESHOLD'))
	define("PRECALC_ERROR_TRESHOLD",0.7);

// Set method to use to obtain user language (see below)
if (!defined('LANG_METHOD'))
	define("LANG_METHOD","get");

// Set environment
if (!defined('ENVIRONMENT'))
	define("ENVIRONMENT","prod");

if(!defined('KEYWORDS_LIST_TRESHOLD'))
	define('KEYWORDS_LIST_TRESHOLD',25); // %

if(!defined('KEYWORDS_LIST_LENGTH'))
	define('KEYWORDS_LIST_LENGTH',10);

if(!defined('KEYWORDS_STOP_TRESHOLD'))
	define('KEYWORDS_STOP_TRESHOLD',5); // %

if(!defined('KEYWORDS_MIN_TRESHOLD'))
	define('KEYWORDS_MIN_TRESHOLD',2);

// Set url masks
if(!defined('URL_PROTOCOL'))
    define("URL_PROTOCOL", "http");

if(!defined('URLMASK_GET'))
	define("URLMASK_GET"
		  ,"http://%host%page?ln=%ln");

if(!defined('URLMASK_DOMAIN'))
	define("URLMASK_DOMAIN"
		  ,"http://%ln.%host%page");

if(!defined('URLMASK_URL'))
	define('URLMASK_URL'
		  ,'http://%host/%ln%page');
		
if (!isset($DB_CONFIG)) {
    $DB_CONFIG= array(
                "host" => getenv('DB_HOST'),
                "port" => getenv('DB_PORT'),
                "user" => getenv('DB_USER'),
                "pasw" => getenv('DB_PASSWORD'),
                "database" => getenv('DB_DATA')
    );
}

define("DOMAIN_PROD" ,'etaamb.be');
// Default Dev Domain
define("DOMAIN_DEV" ,'127.0.0.1:8042/');
     		 
/********************************************************************
 CACHE VARIABLES
 *******************************************************************/
if (!defined('RENDERED_TEXT_CACHE'))  define ('RENDERED_TEXT_CACHE',true);
if (!defined('PARSER_LINKS_CACHE'))   define('PARSER_LINKS_CACHE',true);

/********************************************************************
 TEST VARIABLES
 *******************************************************************/
if (!defined('REFERER_TEST'))		  define('REFERER_TEST',false);
if (!defined('RENDERER_TEST'))	 	  define ('RENDERER_TEST',false);
//if (!defined('PARSER_TEST'))	 	  define ('PARSER_TEST',false);
if (!defined('QUERY_TEST'))			  define ('QUERY_TEST',false);
if (!defined('REDIRECTION_TEST'))     define ('REDIRECTION_TEST',false);
if (!defined('ANONYMISE_TEST'))       define ('ANONYMISE_TEST',false);
if (!defined('QRCODE_TEST'))       	  define ('QRCODE_TEST',false);

/********************************************************************
 LOG VARIABLES
 *******************************************************************/
if (!defined('TAGGER_CLASS_LOG')) 	  define ('TAGGER_CLASS_LOG',false);
if (!defined('REFERER_CLASS_LOG')) 	  define ('REFERER_CLASS_LOG',false);
if (!defined('COLLECTION_CLASS_LOG')) define ('COLLECTION_CLASS_LOG',false);
if (!defined('CONNECTOR_CLASS_LOG'))  define ('CONNECTOR_CLASS_LOG',false);
if (!defined('DEFAULT_CLASS_LOG'))    define ('DEFAULT_CLASS_LOG',false);
if (!defined('LANG_CLASS_LOG')) 	  define ('LANG_CLASS_LOG',false);
if (!defined('NUMAC_CLASS_LOG')) 	  define ('NUMAC_CLASS_LOG',false);
if (!defined('OBSERVER_FILE_LOG')) 	  define ('OBSERVER_FILE_LOG',false);
if (!defined('STATS_CLASS_LOG')) 	  define ('STATS_CLASS_LOG',false);
if (!defined('RENDERER_CLASS_LOG'))	  define ('RENDERER_CLASS_LOG',false);
if (!defined('ROUTER_CLASS_LOG'))	  define ('ROUTER_CLASS_LOG',false);
if (!defined('URL_CLASS_LOG'))	  	  define ('URL_CLASS_LOG',false);
if (!defined('PARSER_CLASS_LOG'))	  define ('PARSER_CLASS_LOG',false);
if (!defined('TITLE_CLASS_LOG'))	  define ('TITLE_CLASS_LOG',false);
if (!defined('RSS_CLASS_LOG'))	  	  define ('RSS_CLASS_LOG',false);
if (!defined('INDEX_LOG'))			  define ('INDEX_LOG',false);
if (!defined('ANONER_LOG'))  		  define ('ANONER_LOG',false);
if (!defined('OBSERVER_SILENT_LOG'))  define ('OBSERVER_SILENT_LOG',true);

/********************************************************************
 FUNCTIONALITIES & SETTINGS
 *******************************************************************/
if (!defined('LIMIT_EXTRACTED_LINKS'))   define ('LIMIT_EXTRACTED_LINKS',6);
if (!defined('FILTER_NUMAC_EXTRACTION')) define ('FILTER_NUMAC_EXTRACTION',true);

if (!defined('AUTO_ANONYMISE'))        	 define ('AUTO_ANONYMISE',true);
if (!defined('PRIVATE_LIFE_FORM'))  	 define ('PRIVATE_LIFE_FORM',true);
if (!defined('PRIVATE_LIFE_EVERYWHERE'))  	 define ('PRIVATE_LIFE_EVERYWHERE',true);
if (!defined('EMOD_KEYWORDS_PATH'))		 define('EMOD_KEYWORDS_PATH','../c_stuff/relation_tokenizer/');
if (!defined('EU_POPUP'))                define('EU_POPUP',true);
if (!defined('POLICY_PAGES'))           define('POLICY_PAGES',true);
if (!defined('LEGAL_FOOTER'))           define('LEGAL_FOOTER',true);

/********************************************************************
 FEATURES
 *******************************************************************/
if (!defined('SHOW_QRCODE'))  			 define ('SHOW_QRCODE',true);
if (!defined('SHOW_REVERSE_LINKS'))  	 define ('SHOW_REVERSE_LINKS',true);
if (!defined('SHOW_SEARCH_BAR'))  		 define ('SHOW_SEARCH_BAR',true);
if (!defined('SHOW_RSS_LINK'))			 define ('SHOW_RSS_LINK',true);
if (!defined('SHOW_TAG_WORDS'))			 define ('SHOW_TAG_WORDS',false);
