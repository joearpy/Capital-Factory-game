<?php

class Game {

	/**
	 * @var string Az alkalmazas azonositoja
	 */
	protected $_hash = false;

	/**
	 * @var string Alkalmazas nyelve
	 */
	protected $_language = false;

	/**
	 * @var string Tartalmazza a template a nyelvfuggo template root path-t. STATIC_DIR . '/languages/hu/'
	 */
	protected $_templateRootPath = '';

	/**
	 * @var string Tartalmazza a template a nyelvfuggo template root path-t. STATIC_URL . '/languages/hu/'
	 */
	protected $_templateRootUrl = '';

	/**
	 * @var array Tartalmazza a configok hibait
	 */
	protected $_configErrors = array();

	/**
	 * @var array Tartalmazza a beolvasott config-ot
	 */
	protected $_config = array();

	/**
	 * @var bool A jatek vegen a poziciot tarolja
	 */
	protected $_position = false;

	/**
	 * @var bool Az adatbazisba beszurt sor ID-jat tartalmazza; ez a user update-jéhez kell
	 */
	protected $_dbId = false;
        
	/**
	 * @var bool Az adatbazisba beszurt sor ID-jat tartalmazza; a role update-jéhez kell
	 */
	protected $_roleDbId = false;

	/**
	 * @var bool Indikalja, hogy a felhasznalo adatait mentettuk e mar
	 */
	protected $_userDataSavedBefore = false;

	/**
	 * @var int Timeline-hoz szukseges napok osszege
	 */
	protected $_time = 0;

	protected $_staffs = array();

	/**
	 * @var array Feladatokat tartalmazza;
	 */
	protected $_tasks = array();

	protected $_securedTasks = array();

	protected $_solutions = array();

	protected $_metricsSettings = array();

	protected $_metrics = array();

	protected $_points = array();

	protected $_translations = array();

	/**
	 * @var object Application instance
	 */
	protected static $_applicationInstance;

	/**
	 * @param $hash Hash - lenyegeben az alkalmazas peldany azonositoja
	 * @param $language Nyelv
	 * @return Game|object
	 */
	public static function getInstance($hash, $language) {
		if (self::$_applicationInstance == null) {
			self::$_applicationInstance = new self($hash, $language);
		}

		return self::$_applicationInstance;
	}

	/**
	 * @param $hash Application egyedi azonositoja
	 * @param $language Application nyelve
	 */
	public function __construct($hash, $language) {
		$this->_hash = $hash;
		$this->_language = $language;
		$this->_templateRootPath = STATIC_DIR . 'languages/' . $this->_language . '/';
		$this->_templateRootUrl = STATIC_URL . 'languages/' . $this->_language . '/';

		# Config beolvasasa
		$this->_readConfig();

		# Staff-ok beolvasasa
		$this->_loadStaffs();

		# Kerdesek generalasa
		$this->_generateTasks();

		# Kulcs => adatok alapon eltaroljuk a metrikakat
		$this->_setMetricsSettings();

		# Nyelvesitesek beolvasasa
		$this->_setTranslations();

		return $this;
	}

	public function init() {
		# Hash eltarolasa a session-ben
		$_SESSION['cfgame']['hash'] = $this->_hash;

		# Osszeallitom az inicializalashoz szukseges adatokat
		$data = array(
			'logo' => $this->_config['logo'], # app logo
			'points' => $this->_config['points'],
			'welcome' => file_get_contents($this->_templateRootPath . $this->_config['welcome']), # welcome szoveg kiszedese
			'metricsInit' => $this->_config['metrics'] # meroszamok
		);

		return $data;
	}

	/**
	 * Ez a metodus inditja az elso kerdest
	 */
	public function start() {
		# Kuldendo adatok osszegyujtese
		$keys = array_keys($this->_tasks);
		$taskHash = $keys[0];
		$data = array(
			'task' => $this->_getTaskPublicData($taskHash)
		);

		return $data;
	}

	public function answer($taskHash, $answerHash) {
		$this->_storeAnswer($taskHash, $answerHash);
		$this->_pushMetrics($taskHash, $answerHash);

		if ($this->_isLastTask($taskHash)) {
			$this->_saveResult();
		}
                
		$data = $this->_prepareAnswerData($taskHash, $answerHash);

		return $data;
	}

	public function saveUserData($name, $email) {
		$this->_saveUserData($name, $email);
	}

	/**
	 * Megmondja, hogy a user adatok el lettek e mar mentve
	 * @return bool
	 */
	public function wasUserDateSavedBefore() {
		$this->_readUserDataSavedBeforeFromSession();

		return $this->_userDataSavedBefore;
	}

	public function t($expression) {
		return $this->_t($expression);
	}

	/**
	 * Letrehozza a hash-t, amit az alkalmazas azonositokent fog hasznalni
	 * @return string
	 */
	static public function createHash() {
		return MD5(APP_KEY . rand(0,99));
	}

	static public function isValidHashFormat($hash) {
		if (strlen($hash) != 32) {
			return false;
		}
		if (!preg_match('/[a-zA-Z0-9]{32}/', $hash, $matches)) {
			return false;
		}

		return true;
	}

	static public function isValidHash($hash) {
		# Session-ben stimmel e a hash
		if ($_SESSION['cfgame']['hash'] != $hash) {
			return false;
		}
		# @todo: megnezni, hogy a hash nincs e mar az adatbazisban
		return true;
	}

	protected function _t($expression) {
		if (isset($this->_translations[$expression])) {
			$return = $this->_translations[$expression];
		} else {
			$return = $expression;
		}

		return $return;
	}

	/**
	 * Elinditja a config validalasat
	 */
	protected function _checkConfigErrors() {
		$this->_validateConfig();
		if ($this->_hasConfigErrors()) {
			$message = $this->_getConfigErrorsFormatted();
			die($message);
		}
	}

	/**
	 * Visszaadja, hogy a config-ban van e hiba
	 * @return bool
	 */
	protected function _hasConfigErrors() {
		if (count($this->_configErrors)) {
			return true;
		}
		return false;
	}

	/**
	 * Visszaadja a hibakat a kovetkezo formaban:
	 * HIBA-SORSZAMA : HIBA MEGNEVEZESE, SORTORES
	 *
	 * @return string
	 */
	protected function _getConfigErrorsFormatted() {
		$errorsString = 'Application can\'t start because has the following error(s): <br/><br/>';
		foreach ($this->_configErrors as $idx => $error) {
			$errorsString.= $idx . ' : ' . $error . '';
		}

		return $errorsString;
	}

	/**
	 * Levalidalja a konfigot az alabbi szempontok szerint:
	 * - Megnezni, hogy a config fajl megvan e
	 * - Megnezni, hogy a config file-ban a json valid e (http://php.net/manual/en/function.json-last-error.php)
	 * - Megnezni, hogy a config-ban levo file-ok megvannak e ott, ahol jelolve vannak
	 */
	protected function _validateConfig() {
		# Megnezni, hogy a config fajl megvan e
		if (!$this->_existsConfigFile()) {
			$this->_configErrors[] = 'Missing config file: "' . $this->_getConfigFilePath() . '"';
			$message = $this->_getConfigErrorsFormatted();
			die($message);
		}

		# Megnezni, hogy a config file-ban a json valid e (http://php.net/manual/en/function.json-last-error.php)
		$config = json_decode(file_get_contents($this->_getConfigFilePath()), true);
		$hasJsonError = true;
		$jsonError = 'JSON error (' . $this->_getConfigFilePath() . '): ';
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$hasJsonError = false;
				break;
			case JSON_ERROR_DEPTH:
				$this->_configErrors[] = 'Maximum stack depth exceeded';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$this->_configErrors[] = $jsonError . 'Underflow or the modes mismatch';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$this->_configErrors[] = $jsonError . 'Unexpected control character found';
				break;
			case JSON_ERROR_SYNTAX:
				$this->_configErrors[] = $jsonError . 'Syntax error, malformed JSON';
				break;
			case JSON_ERROR_UTF8:
				$this->_configErrors[] = $jsonError . 'Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
			default:
				$this->_configErrors[] = $jsonError . 'Unknown error';
				break;
		}
		if ($hasJsonError) {
			$message = $this->_getConfigErrorsFormatted();
			die($message);
		}

		# Megnezni, hogy a config-ban levo file-ok megvannak e ott, ahol jelolve vannak
		$welcome = $this->_templateRootPath . $config['welcome'];
		if (!file_exists($welcome)) {
			$this->_configErrors[] = 'Welcome template missing on this path: "' . $welcome . '"';
			$message = $this->_getConfigErrorsFormatted();
			die($message);
		}
		$finish = $this->_templateRootPath . $config['finish'];
		if (!file_exists($finish)) {
			$this->_configErrors[] = 'Finish template missing on this path: "' . $finish . '"';
			$message = $this->_getConfigErrorsFormatted();
			die($message);
		}

		return true;
	}

	/**
	 * Megvizsgalja, hogy az adott string egy kulso link
	 * vagy egy belso utvonal e.
	 *
	 * @param $link A vizsgalando link/path
	 *
	 * @return bool
	 */
	protected function _isOuterLink($link) {
		if (preg_match('#^https?://#i', $link) === 1) {
			return true;
		}
		return false;
	}

	/**
	 * Az elore megadott strukturaban megkeresi az adott nyelv konfigjat
	 *
	 * @return string
	 */
	protected function _getConfigFilePath() {
		return APP_DIR . 'config' . DIRECTORY_SEPARATOR . $this->_language . '.json';
	}

	protected function _existsConfigFile() {
		if (file_exists($this->_getConfigFilePath())) {
			return true;
		}

		return false;
	}

	protected function _readConfig() {
		$this->_checkConfigErrors();
		$this->_config = json_decode(file_get_contents($this->_getConfigFilePath()), true);
	}

	protected function _loadStaffs() {
		$this->_staffs = array();
		foreach ($this->_config['staffs'] as $idx => $staff) {
			$this->_staffs[$staff['id']] = $staff;
		}
	}

	protected function _generateTasks() {
		$this->_tasks = array();
		foreach ($this->_config['tasks'] as $taskIdx => $task) {
			$taskHash = $this->_generateTaskHash($this->_hash, $taskIdx);
			$this->_securedTasks[$taskHash] = $taskIdx;
			$staffId = $task['staff'];
			$task['staff'] = $this->_staffs[$staffId];
			$task['staff']['image'] = $this->_getTaskImageUrl($this->_staffs[$staffId]['image']);

			$taskData = $task;
			foreach ($taskData['answers'] as $answerIdx => $answer) {
				$answerId = $this->_generateAnswerId($this->_hash, $taskIdx, $answerIdx);
				$taskData['answers'][$answerId] = $answer;
				unset($taskData['answers'][$answerIdx]);
			}
			$this->_tasks[$taskHash] = $taskData;
		}
	}

	protected function _setMetricsSettings() {
		foreach ($this->_config['metrics'] as $idx => $metric) {
			$this->_metricsSettings[$metric['id']] = $metric;
		}
	}

	protected function _setTranslations() {
		$this->_translations = require_once(APP_DIR . 'config/translations/' . $this->_language . '.php');
	}

	# MD5($this->_hash + 'taskId' + APP_KEY)
	protected function _generateTaskHash($hash, $taskId) {
		return MD5($hash . $taskId . APP_KEY);
	}

	# MD5($this->_hash + 'taskId' + 'answerId' + APP_KEY
	protected function _generateAnswerId($hash, $taskId, $answerId) {
		return MD5($hash . $taskId . $answerId . APP_KEY);
	}

	protected function _getTaskImageUrl($image) {
		return STATIC_URL . 'languages/' . $this->_language . '/' . $image;
	}

	protected function _getTaskPublicData($taskHash) {
		$task = $this->_tasks[$taskHash];
		$task['id'] = $taskHash;
		$answers = $task['answers'];
		unset($task['answers']);
		$letter = 'A';
		foreach ($answers as $id => $answer) {
			$task['answers'][$id] = array(
				'letter' => $letter,
				'label' => $answer['label']
			);
			$letter++;
		}

		return $task;
	}

	protected function _storeAnswer($questionHash, $answerHash) {
		$this->_readAnswersFromSession();
		$this->_solutions[$questionHash] = $answerHash;
		$this->_storeAnswerToSession();
	}

	protected function _readAnswersFromSession() {
		if (!empty($_SESSION['cfgame']['solutions'])) {
			$this->_solutions = $_SESSION['cfgame']['solutions'];
		}
	}

	protected function _storeAnswerToSession() {
		$_SESSION['cfgame']['solutions'] = $this->_solutions;
	}

	protected function _prepareAnswerData($taskHash, $answerHash) {
		# Megnezzuk, hogy a kerdes az utolso e.
		if ($this->_isLastTask($taskHash)) { # Ha igen, akkor a finish reszt toltjuk be...
			$data = array(
				'timeline' => $this->_getTimelineData($taskHash, $answerHash),
				'metrics' => $this->_getMetricsSum(),
				'finish' => file_get_contents($this->_templateRootPath . $this->_config['finish']), # finish szoveg kiszedese
				'points' => $this->_getPointsSum(),
				'translations' => $this->_getFinishTranslations()
			);
		} else { # ... egyebkent meg a kovetkezo kerdest
			$nextTaskHash = $this->_getNextTashHash($taskHash, $answerHash);
			$data = array(
				# osszeszedi azokat a metrikakat egy tombbe, ahol valtozas tortent;
				# az ertek a kummulalt ertek lesz; 'money' => '300' formatumban lesznek az adatok
				'metrics' => $this->_getMetricsSum(),
				'task' => $this->_getTaskPublicData($nextTaskHash),
				'timeline' => $this->_getTimelineData($taskHash, $answerHash, $nextTaskHash)
			);
		}

		return $data;
	}

	/**
	 * Visszaadja a $_tasks tombbol hogy az adott taskHash hanyadik a tombben
	 * @param $taskHash
	 * @return integer
	 */
	protected function _getTaskIdxFromTasks($taskHash) {
		$idx = array_search($taskHash, array_keys($this->_tasks));

		return $idx;
	}

	/**
	 * Megnezi taskHash alapjan, hogy az utolso feladathoz ertunk e
	 * @param $taskHash
	 * @return bool
	 */
	protected function _isLastTask($taskHash) {
		$idx = (int)$this->_getTaskIdxFromTasks($taskHash);
		if (($idx+1) == count($this->_tasks)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _getNextTashHash($taskHash, $answerHash) {
		$idx = (int)$this->_getTaskIdxFromTasks($taskHash);
		$nextId = $this->_tasks[$taskHash]['answers'][$answerHash]['next'];
		# Le kell vonni egyet a php tomb/idx miatt
		$nextId--;
		$taskHashes = array_keys($this->_tasks);
		$nextTaskHash = $taskHashes[$nextId];

		return $nextTaskHash;
	}

	protected function _readMetricsFromSession() {
		if (!empty($_SESSION['cfgame']['metrics'])) {
			$this->_metrics = $_SESSION['cfgame']['metrics'];
		}
	}

	protected function _storeMetricsToSession() {
		$_SESSION['cfgame']['metrics'] = $this->_metrics;
	}

	protected function _getMetricsSum() {
		$this->_readMetricsFromSession();
		$metrics = array();
		foreach ($this->_config['metrics'] as $idx => $data) {
			$metrics[$data['id']] = $data['initial'];
		}

		foreach ($this->_metrics as $hash => $data) {
			foreach ($data as $id => $value) {
				$sumValue = $metrics[$id] + $value;
				$max = $this->_metricsSettings[$id]['max'];
				$min = $this->_metricsSettings[$id]['min'];
				if ($sumValue > $max) {
					$sumValue = $max;
				} elseif ($sumValue < $min) {
					$sumValue = $min;
				}

				$metrics[$id] = $sumValue;
			}
		}

		return $metrics;
	}

	protected function _getMultipliedMetrics() {
		$metrics = $this->_getMetricsSum();
		$multipliers = $this->_getMetricsMultipliers();

		foreach ($metrics as $id => $value) {
			$metrics[$id] = $value * $multipliers[$id];
		}

		return $metrics;
	}

	protected function _pushMetrics($taskHash, $answerHash) {
		$this->_readMetricsFromSession();
		$metrics = $this->_tasks[$taskHash]['answers'][$answerHash]['metrics'];
		$this->_metrics[$taskHash] = $metrics;
		$this->_storeMetricsToSession();
	}

	protected function _readPointsFromSession() {
		if (!empty($_SESSION['cfgame']['points'])) {
			$this->_points = $_SESSION['cfgame']['points'];
		}
	}

	protected function _storePointsToSession() {
		$_SESSION['cfgame']['points'] = $this->_points;
	}

	protected function _getPointsSum() {
		$metricsSum = $this->_getMetricsSum();

		$multipliers = $this->_getMetricsMultipliers();

		$points = $this->_config['points'];
		foreach ($metricsSum as $id => $value) {
			$points+= $multipliers[$id] * $value;
		}

		return $points;

	}

	protected function _getMetricsMultipliers() {
		$multipliers = array();

		# Osszeszedi a multiplier erteket egy tombbe:
		#	'money' => 1,
		#	'share' => 20,
		# ...
		foreach ($this->_config['metrics'] as $data) {
			$multipliers[$data['id']] = $data['multiplier'];
		}

		return $multipliers;
	}

	protected function _getResult() {
		$data = array(
			'hash' => $this->_hash,
			'language' => $this->_language,
			'metrics' => $this->_getMetricsSum(),
			'points' => $this->_getPointsSum()
		);

		return $data;
	} 

	protected function _saveResult() {
		$result = $this->_getResult();

		$this->_setPosition($result['points']);

		$db = DB::getConnection();
		$statement = $db->prepare(
			"INSERT INTO `game` (hash, date)
			VALUES (:hash, :date)");

		$data = array(
			'hash' => $result['hash'],
			'date' => date('Y-m-d H:i:s')
		);
		$statement->execute($data);
                
                $gameId = $db->lastInsertId();
                
		$statementToFrame = $db->prepare(
			"INSERT INTO `halloffame` (game, points, metrics)
			VALUES (:game, :points, :metrics)");
                
		$dataFrame = array(
                        'game' => $db->lastInsertId(),
			'metrics' => json_encode($result['metrics']),
			'points' => $result['points']
		);
		$statementToFrame->execute($dataFrame);
                
                $hallOfFameId = $db->lastInsertId();
                
		$statement = $db->prepare(
			"INSERT INTO `role` (role)
			VALUES (:role)");

		$data = array(
			'role' => 1
		);
		$statement->execute($data);
                
                $roleDbId = $db->lastInsertId();
                
		$statement = $db->prepare(
			"INSERT INTO `user` (game, role)
			VALUES (:game, :role)");

		$data = array(
			'game' => $gameId,
			'role' => $db->lastInsertId()
		);
		$statement->execute($data); 
                
		$sql = "UPDATE `halloffame` SET `user`=:user WHERE id = $hallOfFameId";
		$statement = $db->prepare($sql);
		$data = array(
			'user' => $db->lastInsertId()
		);
                
                $this->_dbId = $db->lastInsertId();
                
		$statement->execute($data);                

                $this->_roleDbId = $roleDbId;
		$this->_storeDbIdToSession();
		$this->_storeRoleDbIdToSession();

	}

	protected function _setPosition($points) {
		$db = DB::getConnection();

		$sql = "SELECT * FROM `halloffame` WHERE `points` > $points" . " GROUP BY `points`";
		$statement = $db->prepare($sql);
		$statement->execute();
		$count = $statement->rowCount();

		$this->_position = $count + 1;
		$this->_storePositionToSession();
	}

	protected function _getPosition() {
		$this->_readPositionFromSession();

		return $this->_position;
	}

	protected function _readPositionFromSession() {
		$this->_position = $_SESSION['cfgame']['position'];
	}

	protected function _storePositionToSession() {
		$_SESSION['cfgame']['position'] = $this->_position;
	}

	protected function _readDbIdFromSession() {
		$this->_dbId = $_SESSION['cfgame']['dbId'];
	}

	protected function _storeDbIdToSession() {
		$_SESSION['cfgame']['dbId'] = $this->_dbId;
	}
        
        protected function _readRoleDbIdFromSession() {
		$this->_roleDbId = $_SESSION['cfgame']['roleDbId'];
	}         
        
	protected function _storeRoleDbIdToSession() {
		$_SESSION['cfgame']['roleDbId'] = $this->_roleDbId;
	}  
            
               
	protected function _saveUserData($name, $email) {
		$db = DB::getConnection();

		$this->_readDbIdFromSession();
		$this->_readRoleDbIdFromSession();
                
		$sql = "UPDATE `user` SET `name`= :name, `email`= :email WHERE id = $this->_dbId";
		$statement = $db->prepare($sql);
		$data = array(
			'name' => strip_tags($name),
			'email' => strip_tags($email)
		);

		$statement->execute($data);
                
		$sql = "UPDATE `role` SET `role`= :role WHERE id = $this->_roleDbId";
		$statement = $db->prepare($sql);
		$data = array(
			'role' => 2
		);

		$statement->execute($data);

		$this->_userDataSavedBefore = true;
		$this->_storeUserDataSavedBeforeToSession();
	}

	protected function _readUserDataSavedBeforeFromSession() {
		$this->_userDataSavedBefore =
			!empty($_SESSION['cfgame']['userDataSavedBefore'])
				? $_SESSION['cfgame']['userDataSavedBefore']
				: false
		;
	}

	protected function _storeUserDataSavedBeforeToSession() {
		$_SESSION['cfgame']['userDataSavedBefore'] = $this->_userDataSavedBefore;
	}

	protected function _getTimelineData($taskHash, $answerHash, $nextTaskHash = false) {
		$from = $this->_securedTasks[$taskHash];

		if ($nextTaskHash == false) { # utolso kerdes
			$count = 1;
		} else {
			$to = $this->_securedTasks[$nextTaskHash];
			$count = $to - $from;
		}
		# A feladatok kozul csak azokra fokuszalnuk, ami a ket lepes kozott van
		$tasks = array_slice($this->_tasks, $from, $count);

		$this->_readTimeFromSession();

		# Vegigmegyunk azokon a kerdeseken, amik a $from $to intervallumban vannak
		$idx = 0;
		foreach ($tasks as $hash => $data) {
			if (!empty($data['time'])) {
				$time = $data['time'];
			} else if ($idx == 0) {
				$time = $data['answers'][$answerHash]['time'];
			} else {
				$maxTime = 0;
				foreach ($data['answers'] as $answerHash => $answerData) {
					if ($answerData['time'] > $maxTime) {
						$maxTime = $answerData['time'];
					}
				}
				$time = $maxTime;
			}
			$idx++;
			$this->_time+= $time;
		}

		# Ha tobb az osszeg, mint 360, akkor 360 -ra allitjuk
		if ($this->_time > 360) {
			$this->_time = 360;
		}
		# Ha az utolso kerdes van, akkor is 360-ra kell venni
		if ($this->_isLastTask($taskHash)) {
			$this->_time = 360;
		}

		$this->_storeTimeToSession();

		$months = array(
			30 => 'Január',
			60 => 'Február',
			90 => 'Március',
			120 => 'Április',
			150 => 'Május',
			180 => 'Június',
			210 => 'Július',
			240 => 'Augusztus',
			270 => 'Szeptember',
			300 => 'Október',
			330 => 'November',
			360 => 'December'
		);

		$month = '';
		foreach ($months as $day => $month) {
			if ($day >= $this->_time) {
				$month = $month;
				break;
			}
		}

		$data = array(
			'time' => $this->_time,
			'month' => $month
		);

		return $data;
	}

	protected function _readTimeFromSession() {
		$this->_time = !empty($_SESSION['cfgame']['time']) ? $_SESSION['cfgame']['time'] : 0;
	}

	protected function _storeTimeToSession() {
		$_SESSION['cfgame']['time'] = $this->_time;
	}

	protected function _getFinishTranslations() {
		$translations = array(
			'FINISH_FORM_NAME_TITLE' => $this->_t('FINISH_FORM_NAME_TITLE'),
			'FINISH_FORM_EMAIL_TITLE' => $this->_t('FINISH_FORM_EMAIL_TITLE'),
			'FINISH_ACCEPT_TERMS' => $this->_t('FINISH_ACCEPT_TERMS'),
			'FINISH_SEND_BUTTON' => $this->_t('FINISH_SEND_BUTTON'),
			'FINISH_POSITION_TEXT' => str_replace('###points', $this->_getPosition(), $this->_t('FINISH_POSITION_TEXT'))
		);

		return $translations;
	}

}

?>
