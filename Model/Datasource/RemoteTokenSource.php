<?php

App::uses('DataSource', 'Model/Datasource');
App::uses('HttpSocketOauth', 'HttpSocketOauth.Lib');
App::uses('OauthConfig', 'Apis.Lib');

class TokenSource extends DataSource {

	public $description = 'DataSource for Oauth Tokens';

	public function __construct($config = array()) {
		$this->Http = new HttpSocketOauth();
		parent::__construct($config);
	}

	public function read(\Model $model, array $queryData) {
		$request = $this->{'_' . $model->findQueryType . $this->config['authMethod']}($queryData);
		if (!empty($queryData['options'])) {
			$request = array_merge($request, $queryData['options']);
		}
		$response = $this->Http->request($request);
		if ($response->isOK()) {
			$json = array('application/json', 'application/javascript', 'text/javascript');
			if (in_array($json, $contentType = explode(';', $response->getHeader('Content-Type'))[0])) {
				return json_decode($response->body, true);
			} else{
				$token = parse_str($response->body, $token);
				return $token;
			}
		} else {
			$model->onError();
			return false;
		}
	}

	protected function _accessOauth($queryData) {
		$request = $this->_getRequest('access');
		$request['auth']['oauth_verifier'] = $queryData['requestToken']['verifier'];
		$request['auth']['oauth_request_token'] = $queryData['requestToken']['request_token'];
		$request['auth']['oauth_request_token_secret'] = $queryData['requestToken']['token_secret'];
		return $request;
	}

	protected function _accessOauthV2($queryData) {
		$request = $this->_getRequest('access', 'POST');
		$request['body'] = array(
			'client_id' => $request['auth']['client_id'],
			'client_secret' => $request['auth']['client_secret'],
			'code' => $queryData['requestToken']
		);
		unset($request['auth']);
		return $request;
	}

	protected function _requestOauth($queryData) {
		
	}

	protected function _buildQuery($query, $escape = false) {
		if (is_array($query)) {
			$query = substr(Router::queryString($query, array(), $escape), '1');
		}
		return $query;
	}

	/**
	 * 
	 * @return array
	 */
	protected function _getRequest($path, $method = 'GET') {
		$request = array(
			'method' => $method,
			'uri' => array(
				'host' => $this->config['host'],
				'path' => $this->config[$path],
				'scheme' => $this->config['scheme']
			)
		);
		switch ($this->config['authMethod']) {
			case 'OAuth':
				$request['auth'] = array(
					'oauth_consumer_key' => $this->config['login'],
					'oauth_consumer_secret' => $this->config['password']
				);
				break;
			case 'OAuthV2':
				$request['auth'] = array(
					'client_id' => $this->config['login'],
					'client_secret' => $this->config['password']
				);
				break;
			default:
				break;
		}
		return $request;
	}

}
?>