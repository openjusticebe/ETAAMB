<?php
// Etaamb Docker config file
define("LANG_METHOD","url");
define("ENVIRONMENT","prod");
define("DOMAIN" , getenv('ETAAMB_DOMAIN'));
define("ANON_HOST" , getenv('ETAAMB_ANON_HOST'));
define("URL_PROTOCOL", getenv('ETAAMB_PROTOCOL'));

define("URLMASK" , URL_PROTOCOL."://%host/%ln%page");
