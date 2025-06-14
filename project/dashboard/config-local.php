<?php
// HTTP
define('HTTP_SERVER', 'https://app.test/dashboard/');
define('HTTP_CATALOG', 'https://app.test/');

// HTTPS
define('HTTPS_SERVER', 'https://app.test/dashboard/');
define('HTTPS_CATALOG', 'https://app.test/');

// DIR
define('DIR_APPLICATION', 'C:/laragon/www/app/dashboard/');
define('DIR_SYSTEM', 'C:/laragon/www/app/system/');
define('DIR_IMAGE', 'C:/laragon/www/app/image/');
define('DIR_STORAGE', 'C:/laragon/www/app/storage/');
define('DIR_CATALOG', 'C:/laragon/www/app/catalog/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_DATABASE', 'app_db');
define('DB_PORT', '3306');
define('DB_PREFIX', 'cod_');
