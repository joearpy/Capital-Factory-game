<?php

class Main extends Controller {

	function index(){
		unset($_SESSION['cfgame']);
		$template = $this->loadView('index');
		$template->set('helloWorld', 'Hello World');
		$template->render();
	}

}
