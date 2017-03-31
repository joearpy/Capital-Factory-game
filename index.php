<?php

# Start the Session
session_start();

# Defines
define('ROOT_DIR', 		realpath(dirname(__FILE__)) .'/');
define('APP_DIR', 		ROOT_DIR . 'application/');
define('STATIC_DIR',	ROOT_DIR . 'static/');
define('APP_KEY', 		'a4a16475f95e84979bc01b62cc08f5a15d457032');

define('APP_ENV', 	isset($_SERVER['APPLICATION_ENV']) ? $_SERVER['APPLICATION_ENV'] : 'production');
define('BASE_HOST',	'http://' . $_SERVER['HTTP_HOST'] . '/');
define('STATIC_URL',	BASE_HOST . 'static/');

define('ASSETS',	ROOT_DIR . 'static/assets/');
define('VERSION', 	'1.1.8');

# Includes
require(ROOT_DIR . 'system/pdo.php');
require(APP_DIR  . 'config/config.php');
require(APP_DIR	 . 'plugins/tools.php');
require(ROOT_DIR . 'system/view.php');
require(ROOT_DIR . 'system/controller.php');
require(ROOT_DIR . 'system/pip.php');

# Define base URL
global $config;
define('BASE_URL', $config['base_url']);

# Other defines
define('VIEW_PATH', APP_DIR . 'views' . DIRECTORY_SEPARATOR. 'includes' . DIRECTORY_SEPARATOR);

pip();
