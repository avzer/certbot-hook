<?php
class certbot_book_cloudflare extends certbot_hook {
	
	public function add($domain, $params) {
		if(!($content = isset($params[0]) ? $params[0] : '')) {
			throw new Exception('解析记录为空');
		}
		
		$this->initHeader();
		$zoneId = $this->zone_id($domain);
		$url = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records";
		$data = array(
			'type' => 'TXT', 'name' => $this->_record($domain), 'content' => $content,
			'ttl' => 120, 'priority' => 10, 'proxied' => false
		);
		$json = $this->response($this->curl->post($url, json_encode($data)), '添加记录');
		return isset($json->success) && $json->success;
	}
	
	public function delete($domain, $params) {
		$this->initHeader();
		$zoneId = $this->zone_id($domain);
		$recordId = $this->record_id($zoneId, $domain);
		
		$url = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records/$recordId";
		$this->curl->setOpt(CURLOPT_CUSTOMREQUEST, 'DELETE');
		$json = $this->response($this->curl->request($url), '删除记录');
		return isset($json->success) && $json->success;
	}
	
	protected function record_id($zoneId, $domain) {
		$name = $this->_record($domain) . '.' . $this->_domain($domain);
		$json = $this->response($this->curl->get("https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records", array('name' => $name, 'type' => 'TXT')), '获取“Record ID”');
		
		$recordId = false;
		foreach($json->result As $result) {
			if(isset($result->name) && $result->name == $name) {
				$recordId = $result->id;
				break;
			}
		}
		if(!$recordId) {
			throw new Exception('未获取到“Record ID”');
		}
		return $recordId;
	}
	
	protected function zone_id($domain) {
		$json = $this->response($this->curl->get('https://api.cloudflare.com/client/v4/zones', array('name' => $domain)), '获取“Zone ID”');
		
		$zoneId = false;
		foreach($json->result As $result) {
			if(isset($result->id) && preg_match('/^[a-f0-9]{32}$/i', $result->id)) {
				$zoneId = $result->id;
				break;
			}
		}
		if(!$zoneId) {
			throw new Exception('未获取到“Zone ID”');
		}
		return $zoneId;
	}
	
	protected function response($re, $action) {
		if(!$re) {
			throw new Exception($action . ': HTTP内容返回为空');
		}
		if(!($json = @json_decode($re))) {
			throw new Exception($action . ': JSON解析失败:' . $re);
		}
		if(!isset($json->result)) {
			throw new Exception($action . ': 没有result节点');
		}
		return $json;
	}
	
	protected function initHeader() {
		$this->curl->setHeader('Content-Type', 'application/json');
		$this->curl->setHeader('X-Auth-Email', $this->setting->email);
		$this->curl->setHeader('X-Auth-Key', $this->setting->auth_key);
	}
	
}