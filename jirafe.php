<?php

if (!defined('_PS_VERSION_') || !defined('_CAN_LOAD_FILES_'))
    exit;

// Set to true if you want to debug the Jirafe API in the test sandbox
define('JIRAFE_DEBUG', true);

// plugin version
define('JIRAFE_MODULE_VERSION', '0.1');

// Urls configuration
define('JIRAFE_API_URL', (JIRAFE_DEBUG) ? 'https://test-api.jirafe.com/v1' : 'https://api.jirafe.com/v1');
define('JIRAFE_API_PORT', 443);
define('JIRAFE_TRACKER_URL', (JIRAFE_DEBUG) ? 'test-data.jirafe.com' : 'data.jirafe.com');
define('JIRAFE_ASSETS_URL_PREFIX', 'https://jirafe.com/dashboard'); // without trailing slash

require_once 'jirafe_base.php';
require_once 'Platform/Interface.php';
require_once 'Platform/Ecommerce.php';
require_once 'Platform/Prestashop14.php';
require_once 'Platform/Prestashop15.php';

if (version_compare(_PS_VERSION_, '1.5') >= 0) {
    require_once 'jirafe15.php';
} elseif (version_compare(_PS_VERSION_, '1.4') >= 0) {
    require_once 'jirafe14.php';
}
