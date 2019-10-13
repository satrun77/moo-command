<?php

if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST']       = 'docker.for.mac.localhost';
    $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
    $_SERVER['REQUEST_METHOD']  = 'GET';
}

define('SS_ENVIRONMENT_TYPE', 'dev'); // dev, test, live

define('SS_DATABASE_CLASS', 'MySQLPDODatabase');
define('SS_DATABASE_SERVER', 'mysql');
define('SS_DATABASE_USERNAME', getenv('MYSQL_USER'));
define('SS_DATABASE_PASSWORD', getenv('MYSQL_PASSWORD'));
define('SS_DATABASE_NAME', getenv('MYSQL_DATABASE'));

define('SS_DEFAULT_ADMIN_USERNAME', 'admin');
define('SS_DEFAULT_ADMIN_PASSWORD', 'admin');

define('SOLR_SERVER', 'docker.for.mac.localhost');
define('SOLR_PORT', getenv('SOLR_PORT'));
define('SOLR_MODE', 'file');
define('SOLR_PATH', '/solr/');
define('SOLR_REMOTEPATH', '/solr/');
define('SOLR_USER', '');
define('SOLR_INDEXSTORE_PATH', '/var/www/solr');

//define('WKHTMLTOPDF_BINARY', '/usr/local/bin/wkhtmltopdf');

date_default_timezone_set('Pacific/Auckland');

global $_FILE_TO_URL_MAPPING;
$_FILE_TO_URL_MAPPING['/var/www/html/public'] = 'http://docker.for.mac.localhost:' . getenv('VIRTUAL_PORT');
