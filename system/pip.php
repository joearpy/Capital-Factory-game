<?php

$_routes = array();
$routeFile = APP_DIR . 'helpers' . DIRECTORY_SEPARATOR . 'router.php';

if (file_exists($routeFile)) {
	$_appRoutes = require_once($routeFile);
	$_routes = array_merge($_routes, $_appRoutes);
}

function pip()
{
	global $config, $_routes;

	// Set our defaults
	$controller = $config['default_controller'];
	$action = 'index';
	$url = '';

	// Get request url and script url
	$request_url = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
	$script_url  = (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : '';
	$redirect_url  = (isset($_SERVER['REDIRECT_URL'])) ? $_SERVER['REDIRECT_URL'] : '';

	// Get our url path and trim the / of the left and the right
	
	if ($request_url != $script_url) {
		if (
			array_key_exists($request_url, $_routes) ||
			array_key_exists($redirect_url, $_routes)
		) { # router oldja fel
			if (array_key_exists($request_url, $_routes)) {
				$route = $_routes[$request_url];
			} else if (array_key_exists($redirect_url, $_routes)) {
				$route = $_routes[$redirect_url];
			}

			$controller = $route['controller'];
			if (!empty($route['action'])) {
				$action = $route['action'];
			} else {
				$action = 'index';
			}

			if (!empty($route['params'])) {
				$params = $route['params'];
			} else {
				$params = array();
			}
		} else { # PIP default routing
			# Split the url into segments
			$url = trim(preg_replace('/'. str_replace('/', '\/', str_replace('index.php', '', $script_url)) .'/', '', $request_url, 1), '/');
			$segments = explode('/', $url);

			// Do our default checks
			if (isset($segments[0]) && $segments[0] != '') {
				$controller = $segments[0];
			}
			if (isset($segments[1]) && $segments[1] != '') {
				$action = $segments[1];
			}
			$params = array_slice($segments, 2);

		}
	}

	// Get our controller file
	$path = APP_DIR . 'controllers/' . $controller . '.php';

	if (file_exists($path)) {
		require_once($path);
	} else {
		$controller = $config['error_controller'];
		require_once(APP_DIR . 'controllers/' . $controller . '.php');
	}

	// Check the action exists
	if (!method_exists($controller, $action)) {
		$controller = $config['error_controller'];
		require_once(APP_DIR . 'controllers/' . $controller . '.php');
		$action = 'index';
	}

	// Create object and call method
	$obj = new $controller;
	die(call_user_func_array(array($obj, $action), $params));
}

?>
