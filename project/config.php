<?php
// HTTP
define('HTTP_SERVER', 'https://erp.codaym.com/');

// HTTPS
define('HTTPS_SERVER', 'https://erp.codaym.com/');

// DIR
define('DIR_APPLICATION', '/home/souqdev/store.codaym.com/catalog/');
define('DIR_SYSTEM', '/home/souqdev/store.codaym.com/system/');
define('DIR_IMAGE', '/home/souqdev/store.codaym.com/image/');
define('DIR_STORAGE', '/home/souqdev/codaym_storage/');
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
define('DB_USERNAME', 'souqdev_codaym_store');
define('DB_PASSWORD', 'souqdev_codaym_store');
define('DB_DATABASE', 'souqdev_codaym_store');
define('DB_PORT', '3306');
define('DB_PREFIX', 'cod_');