<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Application Paths & Constants Configuration
|--------------------------------------------------------------------------
*/

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
    define('CONFIG_PATH', BASE_PATH . '/config');
    define('DATA_PATH', BASE_PATH . '/data');
    define('SRC_PATH', BASE_PATH . '/src');
    define('CONTROLLER_PATH', BASE_PATH . '/src/Controllers');
    define('SERVICE_PATH', BASE_PATH . '/src/Services');
    define('UTIL_PATH', BASE_PATH . '/src/Utils');
    define('VIEW_PATH', BASE_PATH . '/resources/views');
    define('LAYOUT_PATH', BASE_PATH . '/resources/views/layouts');
    define('PARTIAL_PATH', BASE_PATH . '/resources/views/partials');
    define('TEMPLATE_PATH', BASE_PATH . '/resources/views/templates');
    define('STORAGE_PATH', BASE_PATH . '/storage');
    define('LOG_PATH', BASE_PATH . '/storage/logs');
    define('TMP_PATH', BASE_PATH . '/storage/tmp');
    define('UPLOAD_PATH', BASE_PATH . '/storage/uploads');
    define('PUBLIC_PATH', BASE_PATH . '/public');
}
