<?php
class certbot_book_aliyun extends certbot_hook {
	
	protected $url = 'https://alidns.aliyuncs.com';
	
	public function add($domain, $params) {
		if(!($content = isset($params[0]) ? $params[0] : '')) {
			throw new Exception('解析记录为空');
		}
		$json = $this->response($this->curl->post($this->url, $this->params(array(
			'Action' => 'AddDomainRecord',
			'DomainName' => $this->_domain($domain),
			'RR' => $this->_record($domain),
			'Type' => 'TXT',
			'Value' => $content
		))));
		return isset($json->RecordId) && $json->RecordId;
	}
	
	public function delete($domain, $params) {
		$recordId = $this->record_id($domain);
		$json = $this->response($this->curl->post($this->url, $this->params(array(
			'Action' => 'DeleteDomainRecord',
			'RecordId' => $recordId
		))));
		return isset($json->RecordId) && $json->RecordId;
	}
	
	protected function record_id($domain) {
		$json = $this->response($this->curl->post($this->url, $this->params(array(
			'Action' => 'DescribeDomainRecords',
			'DomainName' => $this->_domain($domain),
			'RRKeyWord' => $this->_record($domain),
			'TypeKeyWord' => 'TXT',
			'SearchMode' => 'EXACT'
		))));
		$recordId = false;
		if(isset($json->DomainRecords) && isset($json->DomainRecords->Record) && is_array($json->DomainRecords->Record)) {
			foreach($json->DomainRecords->Record As $record) {
				if($record->RR == $this->record) {
					$recordId = $record->RecordId;
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
	
	protected function params($data) {
		$params = array_merge($data, array(
			'Format' => 'JSON',
			'Version' => '2015-01-09',
			'AccessKeyId' => $this->setting->id,
			'SignatureMethod' => 'HMAC-SHA1',
			'Timestamp' => date('Y-m-d\TH:i:s\Z'),
			'SignatureVersion' => '1.0',
			'SignatureNonce' => time() . mt_rand(1000, 9999)
		));
		$params['Signature'] = $this->sign($params);
		return $params;
	}
	
	protected function sign($params) {
		ksort($params);
		$stringToSign = 'POST&' . $this->encode('/') . '&';
		
		$tmp = "";
		foreach($params as $key => $val) {
			$tmp .= '&' . $this->encode($key) . '=' . $this->encode($val);
		}
		$tmp = trim($tmp, '&');
		$stringToSign = $stringToSign . $this->encode($tmp);
		
		$key = $this->setting->secret . '&';
		$hmac = hash_hmac("sha1", $stringToSign, $key, true);
		return base64_encode($hmac);
	}
	
	protected function encode($value = null) {
		$en = urlencode($value);
		$en = str_replace("+", "%20", $en);
		$en = str_replace("*", "%2A", $en);
		$en = str_replace("%7E", "~", $en);
		return $en;
	}
	
}