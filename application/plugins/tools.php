<?php

global $config;

class Ajax_Response_Array {

	protected static function _response($status, $data) {
		$response = array(
			'status' => $status
		);
		if (!empty($data)) {
			$response['data'] = $data;
		}
		return $response;
	}

	public static function success($data = array()) {
		return self::_response('success', $data);
	}

	public static function error($data = array()) {
		return self::_response('error', $data);
	}

	public static function unexpectedError($data = array()) {
		if (!isset($data['errorCode'])) {
			throw new Exception('Please add error code e.g. 901');
		}
		return self::_response('unexpectedError', $data);
	}

}

class Ajax_Response_Json {

	public static function response($response = array()) {
		return json_encode($response);
	}

	public static function success($data = array()) {
		return json_encode(Ajax_Response_Array::success($data));
	}

	public static function error($data = array()) {
		return json_encode(Ajax_Response_Array::error($data));
	}

	public static function unexpectedError($data = array()) {
		return json_encode(Ajax_Response_Array::unexpectedError($data));
	}

}

class Testing {

	static protected $_cfg = array(
		'showHackId' => true, # Every hack attempt has an Id, when this option is true it show it in console else not
		'allowChangeRoomWithoutResolved' => true, 
		'wiredUser' => array( # without topmernok.hu's session use this settings
			'allow' => true,
			//'teamId' => 3172999
			'teamId' => 10
		),
		'forceWelcome' => false, # if true, not save "visited" status in DB
		'sendresultDump' => false # ajax controller's sendresult method dump mode on/off
	);

	static public function is($property) {
		if (APP_ENV == 'production') {
			return false;
		}

		if (!isset(self::$_cfg[$property])) {
			$message = 'Unknown property: "' . $property .'"';
			throw new Exception($message);
		}

		$testData = self::$_cfg[$property];
		if (is_array($testData)) {
			if (!isset($testData['allow'])) {
				$message = 'You must have "allow" key with true|false value!';
				throw new Exception($message);
			} else {
				return $testData['allow']; # true | false
			}
		} else {
			return $testData; # true | false
		}
	}

	static public function get() {
		$args = func_get_args();
		$result = self::$_cfg;

		if (!count($args)) {
			return $result;
		}

		foreach ($args as $key) {
			if (isset($result[$key])) {
				$result = $result[$key];
			}
		}

		return $result;
	}
}

class WarnLog {

	const WARN_1 = 'Call ajax request is not an xmlhttprequest';

	const WARN_2 = 'Missing hash from the call';

	const WARN_3 = 'Invalid hash';

	const WARN_4 = 'Invalid taskHash';

	const WARN_5 = 'Try insert result into db with force by ajax';

	static public function write($warnId, $hash = null, $data = array()) {
		$db = DB::getConnection();

		$data = array_merge(
			[
				'request_uri' =>  $_SERVER['REQUEST_URI']
			],
			$data
		);

		$statement = $db->prepare("INSERT INTO `warn` (warn, hash, ip, data, date) VALUES (:warn, :hash, :ip, :data, :date)");
		$insertData = array(
			'warn' => $warnId,
			'hash' => $hash,
			'ip' => self::getClientIp(),
			'data' => json_encode($data),
			'date' => date('Y-m-d H:i:s')
		);
		//DX($insertData);

		$statement->execute($insertData);
	}

// Function to get the client IP address
	static public function getClientIp() {
		$ipaddress = '';
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if (!empty($_SERVER['HTTP_X_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		} else if (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		} else if (!empty($_SERVER['HTTP_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		} else if (!empty($_SERVER['REMOTE_ADDR'])) {
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		} else {
			$ipaddress = 'UNKNOWN';
		}

		return $ipaddress;
	}
}