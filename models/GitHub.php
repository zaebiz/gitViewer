<?php

class GitHub
{
	const apiEntryPoint = 'https://api.github.com/';
	const apiRepos = 'repos/:owner/:repo/commits?page=:page&per_page=100';
	const apiUser = 'user';
	
	const requestTokenUri = 'https://github.com/login/oauth/access_token';
	const clientId = '8f203f04d856de2d20d3';
	const clientSecret = '59c518d3b41ca8ba9855827ec32b2813c4b8664e';
	
	public function requestPermissions()
	{
		$url = 'https://github.com/login/oauth/authorize';
		$state = microtime(true).rand(1000, 100000);
		
		$f3 = Base::instance();
		$f3->set('SESSION.githubToken', '');
		$f3->set('SESSION.githubState', $state);
		
		$params = array(
			'client_id'=> self::clientId,
			'scope'=> 'user',
			'state'=> $state
		);
		
		$url .= '?'.http_build_query($params);
		header('Location: '.$url);
	}
	
	public function requestAccessToken()
	{
		$f3 = Base::instance();
		$receivedState = $f3->get('GET.state');
		$code = $f3->get('GET.code');
		
		if ($f3->get('SESSION.githubState') == $receivedState && isset($code))
		{
			$params = array(
				'client_id' => self::clientId,
				'client_secret' => self::clientSecret,
				'state' => $state,
				'code' => $code
			);
			
			// request token
			$url = self::requestTokenUri.'?'.http_build_query($params);
			$result = $this->apiRequest($url);
			$token = json_decode($result->body)->access_token;
			$f3->set('SESSION.githubToken', $token);
			// request username
			$url = self::apiEntryPoint.self::apiUser."?access_token=$token" ;
			$user = $this->apiRequest($url);
			$f3->set('SESSION.githubName', json_decode($user->body)->name);
		}
	}
	
	public function getCommits($owner, $repo)
	{
		$url = self::apiEntryPoint;
		$url .= str_replace(':owner', $owner, self::apiRepos);
		$url = str_replace(':repo', $repo, $url);
		
		$result = new stdClass();
		$result->error = 0;
		$result->errorMessage = 'ok';
		$result->data = array();
		
		$page = 1;		// API Githab считает с 1
		while ($page < 11)	// ограничим макс 1к коммитов
		{
			$curPageUrl = str_replace(':page', $page++, $url);
			$responce = $this->apiRequest($curPageUrl);
			if ($responce->status != 200)
			{
				$result->error = 1;
				$result->errorMessage = "Error get data from GitHub for $owner / $repo";
				break;
			}
			
			$commits = json_decode($responce->body, true);
			$result->data = array_merge($result->data, $commits);
			if (count($commits) < 100) break;	// достигли последней страницы
		}
		
		return $result;
	}
	
	private function apiRequest($url)
	{
		$curl = curl_init($url);
		$headers[] = 'Accept: application/json';
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_USERAGENT, 'PHP');
		
		$responce = curl_exec($curl);
		$result = new stdClass();
		$result->body = $responce;
		$result->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return $result;
	}
	
}
