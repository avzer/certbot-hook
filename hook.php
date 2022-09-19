<?php
date_default_timezone_set("UTC");

echo '>> ' . implode(' ', $argv) . "\n";

$domain = trim(isset($argv[1]) ? $argv[1] : '');
if(!$action = trim(isset($argv[2]) ? $argv[2] : '')) {
	die("第2个参数错误：add/delete\n");
}

$params = array();
for($i = 3; $i <= count($argv); $i++) {
	if(!isset($argv[$i])) {
		continue;
	}
	$params[] = $argv[$i];
}

$dsettings = parse_ini_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'domain.ini', true);
if(!$domain || !isset($dsettings[$domain])) {
	die("没有域名参数或未找到域名配置\n");
}
$action = strtolower($action);

/**
 * @return certbot_hook
 */
function api($name, $setting) {
	$classname = 'certbot_book_' . $setting->api;
	return new $classname($setting);
}

$setting = (object) $dsettings[$domain];
include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . $setting->api . '.php';

$classname = 'certbot_book_' . $setting->api;
$api = new $classname($setting);

echo '>> ' . $action . " => " . implode(' ', $params) . "\t";
try {
	$return = $api->$action($domain, $params);
	if($return) {
		echo "成功";
		if($action == 'add') {
			echo "等待20秒";
			sleep(20);
		}
		echo "...";
	} else {
		echo '失败';
	}
} catch (Exception $e) {
	echo '错误' . $e->getMessage();
}
echo "\n";

abstract class certbot_hook {
	
	protected $setting = null;
	protected $curl = null;
	public function __construct($setting) {
		$this->setting = $setting;
		$this->curl = new certbot_hook_curl();
	}
	
	protected function _record($domain) {
		$rs = array('_acme-challenge');
		$ks = explode('.', $domain);
		while(count($ks) > 2) {
			$rs[] = array_shift($ks);
		}
		return implode('.', $rs);
	}
	
	protected function _domain($domain) {
		$ks = explode('.', $domain);
		while(count($ks) > 2) {
			array_shift($ks);
		}
		return implode('.', $ks);
	}
	
	abstract public function add($domain, $params);
	abstract public function delete($domain, $params);
}

class certbot_hook_curl {
	
	protected $_ch = null;
	protected $_headers = array();
	public function __construct() {
		$this->_ch = curl_init();
		
		curl_setopt($this->_ch, CURLOPT_HEADER, 0);
		curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, 0);			// 对认证证书来源的检查
		curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, 2);			// 从证书中检查SSL加密算法是否存
		curl_setopt($this->_ch, CURLOPT_ENCODING, 'gzip');				// 压缩
		curl_setopt($this->_ch, CURLOPT_USERAGENT, 'Mozilla/4.0');		// 模拟客户端
		curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, 2);			// 自动处理跳转地址到最终地址.
		curl_setopt($this->_ch, CURLOPT_AUTOREFERER, 1);				// 设置自动referer信息
		//启用cookie
		$cookiefile = tempnam(ini_get('session.save_path'), 'tmp');
		curl_setopt($this->_ch, CURLOPT_COOKIEJAR, $cookiefile);
		curl_setopt($this->_ch, CURLOPT_COOKIEFILE, $cookiefile);
		
		curl_setopt($this->_ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
		
		$this->setHeader('Accept-Encoding', 'gzip');
	}
	
	public function setHeader($k, $val) {
		$this->_headers[$k] = $val;
		return $this;
	}
	
	public function setOpt($opt, $val) {
		curl_setopt($this->_ch, $opt, $val);
		return $this;
	}
	
	public function get($url, $query = array()) {
		if($query) {
			$url .= '?' . http_build_query($query);
		}
		$this->setOpt(CURLOPT_HTTPGET, 1);
		return $this->request($url);
	}
	
	public function post($url, $query = null) {
		if(is_array($query)) {
			$query = http_build_query($query);
		}
		$this->setOpt(CURLOPT_POSTFIELDS, $query);
		$this->setOpt(CURLOPT_POST, 1);
		$this->setOpt(CURLOPT_REFERER, $url); // 伪造来路
		return $this->request($url);
	}
	
	public function request($url) {
		$headers = array();
		foreach($this->_headers As $key => $val) {
			$headers[] = "$key: $val";
		}
		if($headers) {
			$this->setOpt(CURLOPT_HTTPHEADER, $headers);
		}
		$this->setOpt(CURLOPT_URL, $url);
		return curl_exec($this->_ch);
	}
	
	public function __destruct() {
		curl_close($this->_ch);
	}
	
}

