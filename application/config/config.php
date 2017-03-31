<?php

$config['base_url'] = '/';


$config['db_host'] = 'localhost';
$config['db_name'] = 'szakdolgozat';
$config['db_username'] = 'root';
$config['db_password'] = '';


if (APP_ENV != 'production') {
	ini_set('display_errors',1);
}

# set db connection 
DB::getConnection(
	$config['db_host'],
	$config['db_name'],
	$config['db_username'],
	$config['db_password']
);

$config['default_controller'] = 'application'; // Default controller to load
$config['error_controller'] = 'error'; // Controller used for errors (e.g. 404, 500 etc)