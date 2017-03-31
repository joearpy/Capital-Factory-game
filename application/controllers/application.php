<?php

require_once(APP_DIR . 'models/Game.php');

class Application extends Controller {

	public function start($language){
		unset($_SESSION['cfgame']);
		$_SESSION['cfgame']['language'] = $language;
		$hash = Game::createHash();
		$game = Game::getInstance($hash, $language);
		$initData = $game->init();

		$template = $this->loadView('application');
		$translations = include(APP_DIR . 'config/translations/' . $language . '.php');
		$template->set('t', $translations);
		$template->set('hash', $hash);
		$template->set('initData', json_encode($initData));
		$template->set('language', $language);
		$template->render();
	}
}

?>
