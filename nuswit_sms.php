<?php
# nuswit api class using curl (feb/2012)
#
# make sure php is installed with curl support.
# you can check by calling:
# 	if (NuswitSMS::has_curl()) { print("curl is installed\n"); }
#
# Example:
#
#	$api = new NuswitSMS("qoaRUVgUaHGRcDafC0Nma9mGY04adEQnvas7J0nj/Mw=");
#
#	$api->setDebug(true);
#
#
# Sending a single message (returns message id), sender is optional.
#	$res = $api->send('1555555555','message');
#	$res = $api->send('1555555555','message','john');
#
# Sending to multiple destinations (again, sender is optional):
#	$res = $api->bulk(array('1555555553','155555553'),'message');
#
# Check credits with:
#	$credits = $api->credits();
#	print($credits . "\n");
#
# Report on all,one or many message ids.
#	$report = $api->report();
#	$report = $api->report('123123123123');
#	$report = $api->report(array('123123123123','2121212121'));

class NuswitSMS {

	private $key_ = null;
	/* base url */
	private $url_ = "https://sms.nuswit.com/";
	private $ssl_check_ = true;

	/* timeout, in secs */
	private $timeout_ = 10;

	/* debug, will show up as STDERR */
	private $debug_ = false;

	/* public methods */
	public function __construct($apikey, $url = null) {
		$this->key_ = $apikey;

		if ($url != null) {
			$this->url_ = $url;
		}
	}

	public function setSSLCheck($t) {
		$this->ssl_check_ = $t;
	}

	public function setDebug($t) {
		$this->debug_ = $t;
	}

	public function setTimeout($t) {
		$this->timeout_ = $t;
	}

	/* returns a confirmation id */
	public function send($phone, $message, $sender = null) {
		$params = array('phone'   => $phone,
				'message' => $message);
		if ($sender) {
			$params['sender'] = $sender;
		}
		$result = $this->dispatch('send', $params);

		if ($result != null) {
			return trim($result);
		}

		return null;
	}

	public function bulk($phonelist, $message, $sender = null) {
		$params = array('phonelist' => implode($phonelist,','),
				'message' => $message);

		if ($sender) {
			$params['sender'] = $sender;
		}

		$result = $this->dispatch('bulk', $params);

		if ($result == null) {
			return null;
		}

		$result = trim($result);
		$ret = array();

		if (strpos($result,',')) {
			//credit is present
			$v = explode(',', $result, 2);
			$ret['sent'] = intval($v[0]);
			$ret['credits'] = floatval($v[1]);
		} else {
			$ret['sent'] = intval($result);
			$ret['credits'] = -1;
		}

		return $ret;
	}

	/* returns credits available */
	public function credits() {
		$result = $this->dispatch('credits');

		if ($result != null) {
			return floatval(trim($result));
		}

		return -1;
	}

	/* accepts a single id or array of ids
	 * returns array formatted as
	 * ret[ID]['status']
	 * ret[ID]['sentdate']
	 * ret[ID]['donedate']
	 *
	 * ret[ANOTHER_ID]['status']
	 * ret[ANOTHER_ID]['sentdate']
	 * ret[ANOTHER_ID]['donedate']
	 */
	public function report($values = null) {
		//make it easier to process
		$endpoint = 'report';
		if (is_array($values)) {
			$endpoint .= '/' . implode(',',$values);
		} elseif ($values != null) {
			$endpoint .= '/'. $values;
		}

		$result = $this->dispatch($endpoint);
		if ($result == null) {
			return null;
		}

		if (trim($result) == 'NO_DATA') {
			//allows the devel to differentiate between null and false
			return false;
		}

		$p = xml_parser_create();
		xml_parse_into_struct($p, $result, $vals, $index);
		xml_parser_free($p);

		if (!array_key_exists('MESSAGE', $index)) {
			return false;
		}

		$ret = array();
		foreach($index['MESSAGE'] as $m) {
			$mv = $vals[$m]['attributes'];
			$ret[$mv['ID']] = array('status'   => $mv['STATUS'],
						'sentdate' => $mv['SENTDATE'],
						'donedate' => $mv['DONEDATE']);
		}

		return $ret;
	}

	/* private methods */
	private function dispatch($op, $params = null) {
		$ch = curl_init();


		$headers = array('Authorization: Nuswit ' . $this->key_);

		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL, $this->url_ . $op);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout_);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		if ($params) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		if ($this->ssl_check_ == false) {
			/* disable ssl check for martians */
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}

		if ($this->debug_) {
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_STDERR, STDOUT);
		}

		$buf = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		if ($buf == false || $info['http_code'] != 200) {
			return null;
		} else {
			return $buf;
		}
	}

	/* static methods */
	public static function has_curl() {
		return in_array('curl', get_loaded_extensions());
	}
}
?>