<?php
class certbot_book_qcloud extends certbot_hook {
	
	protected $host = 'cns.api.qcloud.com';
	protected $path = "/v2/index.php";
	
	public function add($domain, $params) {
		if(!($content = isset($params[0]) ? $params[0] : '')) {
			throw new Exception('解析记录为空');
		}
		$json = $this->response($this->curl->post($this->url(), $this->params(array(
			'Action' => 'RecordCreate',
			'domain' => $this->_domain($domain),
			'subDomain' => $this->_record($domain),
			'recordType' => 'TXT',
			'recordLine' => '默认',
			'value' => $content
		))));
		return isset($json->data) && $json->data;
	}
	
	public function delete($domain, $params) {
		$recordId = $this->record_id($domain);
		$json = $this->response($this->curl->post($this->url(), $this->params(array(
			'Action' => 'RecordDelete',
			'domain' => $domain,
			'recordId' => $recordId
		))));
		return isset($json->codeDesc) && $json->codeDesc == 'Success';
	}
	
	protected function record_id($domain) {
		$_record = $this->_record($domain);
		$json = $this->response($this->curl->post($this->url(), $this->params(array(
			'Action' => 'RecordList',
			'domain' => $this->_domain($domain),
			'subDomain' => $_record,
			'recordType' => 'TXT'
		))));
		$recordId = false;
		if(isset($json->data) && isset($json->data->records) && is_array($json->data->records)) {
			foreach($json->data->records As $record) {
				if($record->name == $_record) {
					$recordId = $record->id;
					break;
				}
			}
		}
		if(!$recordId) {
			throw new Exception('未找到解析记录');
		}
		return $recordId;
	}
	
	protected function response($re) {
		if(!$re) {
			throw new Exception('HTTP返回空');
		}
		if(!($json = @json_decode($re))) {
			throw new Exception('解析JSON失败：' . $re);
		}
		return $json;
	}
	
	protected function url() {
		return 'https://' . $this->host . $this->path;
	}
	
	protected function params($data) {
		$params = array_merge($data, array(
			'Timestamp' => time(),
			'Nonce' => time() . mt_rand(1000, 9999),
			'SecretId' => $this->setting->key,
			'SignatureMethod' => 'HmacSHA1'
		));
		$params['Signature'] = $this->sign($params);
		return $params;
	}
	
	protected function sign($params) {
		$tmps = array();
		ksort($params);
		foreach($params as $key => $value) {
			array_push($tmps, str_replace("_", ".", $key) . "=" . $value);
		}
		$str = 'POST' . $this->host . $this->path . "?" . implode("&", $tmps);
		return base64_encode(hash_hmac("sha1", $str, $this->setting->secret, true));
	}
	
}