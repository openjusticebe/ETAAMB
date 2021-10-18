<?php
// Etaamb Docker config file
define("LANG_METHOD","url");
define("ENVIRONMENT","prod");
define("DOMAIN" , getenv('ETAAMB_DOMAIN'));
define("ANON_HOST" , getenv('ETAAMB_ANON_HOST'));

define("URLMASK" ,"http://%host/%ln%page");


define("PRECALC_NUM",1200);
define ('RENDERED_TEXT_CACHE',false);
