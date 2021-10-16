<?php
// Etaamb Docker config file
define("LANG_METHOD","url");
define("ENVIRONMENT","prod");
define("DOMAIN" , getenv('ETAAMB_DOMAIN'));

define("URLMASK" ,"http://%host/%ln%page");

define("PRECALC_NUM",1200);
