<?php
define('SITE_ROOT', __DIR__);
define('ADMIN_PACKAGE_ROOT', SITE_ROOT . '/com/yp');
define('CORE_PACKAGE_ROOT', SITE_ROOT . '../../commons-data-php/com/yp');

include_once CORE_PACKAGE_ROOT . '/tools/Configuration.php';
require_once __DIR__ . "/classes.php";

/*
use com\yp\tools\Configuration;
$configFile = SITE_ROOT . '/Config.properties';

define('CLASSES', Configuration::getSubConfig($configFile, 'classname'));
*/
