<?php
// Etaamb Dev Docker config file
// The environment variables in this file should be configured on the host
// See Dockerfile for more build-time required arguments
define("LANG_METHOD","url");
define("ENVIRONMENT","dev");
define("DOMAIN" , getenv('ETAAMB_DOMAIN'));
define("ANON_HOST" , getenv('ETAAMB_ANON_HOST'));
define("URL_PROTOCOL", getenv('ETAAMB_PROTOCOL'));
define("URLMASK" , URL_PROTOCOL."://%host/%ln%page");
define('MAIL_ADMIN', getenv('ADMIN_MAIL'));
define('RENDERED_TEXT_CACHE', false);
define('PARSER_TEST_CACHE', false);
define('REFERER_TEST', false);
define('URL_CLASS_LOG', false);
define('ROUTER_CLASS_LOG', true);
define('OBSERVER_SILENT_LOG', false);

