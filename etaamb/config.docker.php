<?php
// Etaamb Prod Docker config file
// The environment variables in this file should be configured on the host
// See Dockerfile for more build-time required arguments
define("LANG_METHOD","url");
define("ENVIRONMENT","prod");
define("DOMAIN" , getenv('ETAAMB_DOMAIN'));
define("ANON_HOST" , getenv('ETAAMB_ANON_HOST'));
define("URL_PROTOCOL", getenv('ETAAMB_PROTOCOL'));
define("URLMASK" , URL_PROTOCOL."://%host/%ln%page");
define('MAIL_ADMIN', getenv('ADMIN_MAIL'));
