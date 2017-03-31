<?php

require(APP_DIR . 'models/Game.php');

class Ajax extends Controller {

	protected $_rp = null;

	protected function _init($settings = array()) {
		if (!isset($settings['disableHttpx'])) { # @log : hack attempt - nem ajax
			if ( empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) {
				WarnLog::write(WarnLog::WARN_1, $this->_rp['h']);
				if (!Testing::is('showHackId')) { die(); } else { die(WarnLog::WARN_1); } # sima die => production mode, die('x') => dev mode
			}
		}

		if (!isset($_SESSION['cfgame']['hash'])) {
			$data = array(
				'sessionTimeout' => '!Session lejárt, kérlek jelentkezz be újra!' # @lang
			);
			$response = Ajax_Response_Json::error($data);
			die($response);
		}

		$this->_rp = $_REQUEST;

		if (!isset($this->_rp['h'])) { # @log : hack attempt - nincs "h" parameter hivaskor - azaz hash
			WarnLog::write(WarnLog::WARN_2);
			if (!Testing::is('showHackId')) { die(); } else { die(WarnLog::WARN_2); } # sima die => production mode, die('x') => dev mode
		}

		$hash = $this->_rp['h'];
		if (!Game::isValidHashFormat($hash)) {
			WarnLog::write(WarnLog::WARN_3, $hash);
			if (!Testing::is('showHackId')) { die(); } else { die(WarnLog::WARN_3); } # sima die => production mode, die('x') => dev mode
		}

		if (!Game::isValidHash($hash)) {;
			WarnLog::write(WarnLog::WARN_3, $hash, ['hashInSession' => $_SESSION['cfgame']['hash']]);
			if (!Testing::is('showHackId')) { die(); } else { die(WarnLog::WARN_3); } # sima die => production mode, die('x') => dev mode
		}

	}

	public function start() {
		$this->_init();

		$hash = $this->_rp['h']; # team hash
		$language = $_SESSION['cfgame']['language'];

		$game = Game::getInstance($hash, $language);
		$initData = $game->start();

		$response = Ajax_Response_Json::success($initData);

		die($response);
	}

	public function answer() {
		$this->_init();

		$hash = $this->_rp['h']; # team hash
		$question = $this->_rp['q']; # question
		$answer = $this->_rp['a']; # answer
		$language = $_SESSION['cfgame']['language'];

		$game = Game::getInstance($hash, $language);
		$initData = $game->answer($question, $answer);

		$response = Ajax_Response_Json::success($initData);

		die($response);
	}

	public function saveuserdata() {
		$this->_init();

		$hash = $this->_rp['h'];
		$name = $this->_rp['n'];
		$email = $this->_rp['e'];
		$language = $_SESSION['cfgame']['language'];
                
                //Ellenőrzés: létezik e már a név és e-mail cím
                $sql = 'SELECT * FROM user';
                
                $db = DB::getConnection();
		$statement = $db->prepare($sql);
		$statement->execute();
		$rows = $statement->fetchAll();
                
		$game = Game::getInstance($hash, $language);
                
		foreach ($rows as $row) {
                    if ($row['name'] == $name AND $row['email'] == $email) {
                        $data = array('messageError' => $game->t('FINISH_DATA_ERROR'));
                        $response = Ajax_Response_Json::error($data);
                        die($response);
                    }
		}                

		// Eredmenyek kikerese
		if (!$game->wasUserDateSavedBefore()) {
			$game->saveUserData($name, $email);
			$data = array('message' => $game->t('FINISH_DATA_SAVED'));
		} else {
			WarnLog::write(WarnLog::WARN_5, $hash, ['hashInSession' => $_SESSION['cfgame']['hash']]);
			if (!Testing::is('showHackId')) { die(); } else { die(WarnLog::WARN_5); }
		}
                $response = Ajax_Response_Json::success($data);

		die($response); 
	}

	public function halloffame() {
		$this->_rp = $_REQUEST;

		$year = strip_tags($this->_rp['year']);
		$month = strip_tags($this->_rp['month']);
		$week = strip_tags($this->_rp['week']);

		$sql = 'SELECT `date`, `name`, YEAR(game.date) as `year`, MONTH(game.date) as `month`, WEEK(game.date) as `week`, points FROM `user` LEFT JOIN `game` ON user.game = game.id LEFT JOIN halloffame ON user.id = halloffame.user ';

		$where = array('`name` != ""');
		$sqlData = array();

		if ($year != '') {
			if (!preg_match('/^[0-9]{4}$/', $year, $matches)) {
				$response = Ajax_Response_Json::error();
				die($response);
			}

			$sqlData['year'] = $year;
			$where[] = 'YEAR(date) = :year';

			if ($month != '') {
				if (!preg_match('/^[0-9]{1,2}$/', $month, $matches)) {
					$response = Ajax_Response_Json::error();
					die($response);
				}

				$sqlData['month'] = $month;
				$where[] = 'MONTH(date) = :month';
			}

			if ($week != '') {
				if (!preg_match('/^[0-9]{1,2}$/', $week, $matches)) {
					$response = Ajax_Response_Json::error();
					die($response);
				}

				$sqlData['week'] = $week;
				$where[] = 'WEEK(date) = :week';
			}
		}

		if (count($where)) {
			$sql.= ' WHERE ' . implode(' AND ', $where) . ' ';
		}

		$sql.= ' ORDER BY `points` DESC';

		$db = DB::getConnection();
		$statement = $db->prepare($sql);
		$statement->execute($sqlData);
		$rows = $statement->fetchAll();

		$result = array();
		$result['data'] = array();
		$position = 1;

		foreach ($rows as $row) {
			$result['data'][] = array(
				$position,
				$row['name'],
				$row['date']
			);
			$position++;
		}

		$response = json_encode($result);

		die($response);
	}

}

?>
