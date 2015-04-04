<?php

class SiteController
{
	private $db;
	private $git;
	private $f3;
	private $makeRender = true;
	
	function beforeRoute()
	{
		$this->db = new Database();
		$this->git = new GitHub();
		$this->f3 = Base::instance();
	}
	
	function afterRoute()
	{
		
		if ($this->makeRender)
		{	
			// add last repos block & render
			$this->setGitName();
			$this->getLastRepos();
			echo Template::instance()->render('main.html');
		}
	}
	
	private function renderEmptyPage()
	{
		$this->f3->set('ErrorMessage', 'ok');
		$this->f3->set('commits', []);
		$this->f3->set('pageCount', 0);
		$this->f3->set('curPage', 0);		
	}
	
	function actionIndex()
	{
		$this->renderEmptyPage();
	}

	function actionGetCommits()
	{	
		$this->gitRequestPermissions();
		$this->f3->set('ErrorMessage', 'ok');
			
		$owner = strtolower($this->f3->get('PARAMS.owner'));
		$repo = strtolower($this->f3->get('PARAMS.repo'));
		$reload = (bool)$this->f3->get('GET.reload');
		$page = intval($this->f3->get('GET.page'));
		if ($page == 0) $page = 1;
		
		$repoId = $this->db->checkRepoExists($owner, $repo);
		if ($reload || $repoId == 0)		
			$repoId = $this->loadRepoFromGit($owner, $repo, $repoId);
		
		$commitCount = $this->db->getRepoCommitsCount($repoId);
		$commits = $this->db->getRepoCommits($repoId, $page-1);
		
		$this->f3->set('commits', $commits);
		$this->f3->set('pageCount', ceil($commitCount/Database::PAGE_SIZE));
		$this->f3->set('curPage', $page);
	}
	
	private function loadRepoFromGit($owner, $repo, $existingRepoId)
	{
		$responce = $this->git->getCommits($owner, $repo);
		$newRepoId = 0;

		if ($responce->error == 0 && isset($responce->data))
		{
			if ($existingRepoId > 0) 
				$this->db->removeRepo($existingRepoId);
			
			$newRepoId = $this->db->addNewCommits($owner, $repo, $responce->data);
		}
		else
			$this->f3->set('ErrorMessage', $responce->errorMessage);
		
		return $newRepoId;
	}

	function actionRemove()
	{
		$data = [];
		$result = 'success';
		
		try {
			$data = json_decode( urldecode($this->f3->get('GET.ids')) );
			if (is_array($data->ids))
				$res = $this->db->removeCommitsById($data->ids);
		} 
		catch (Exception $e) {
			$result = 'error';
		}
		
		$this->makeRender = false;
		echo json_encode(array(
			'result'=> $result
		));
	}
	
	private function getLastRepos()
	{
		$this->f3->set('lastRepos', $this->db->getLastRepos(8));
	}
	
	private function checkGitToken()
	{
		$token = $this->f3->get('SESSION.githubToken');
		return (!empty($token));
	}
	
	private function setGitName()
	{
		$this->f3->set('githubName', 'guest');
		$name = $this->f3->get('SESSION.githubName');
		
		if (isset($name))
			$this->f3->set('githubName', $this->f3->get('SESSION.githubName'));
	}
			
	function gitRequestPermissions()
	{
		if (!$this->checkGitToken())
			$this->git->requestPermissions();
	}

	function actionGitAuth()
	{
		if (!$this->checkGitToken())
			$this->git->requestAccessToken();
		
		$this->renderEmptyPage();
	}
	
	// test function for git auth reset
	function clearAuth()
	{
		$this->f3->set('SESSION.githubToken', '');
		$this->f3->set('SESSION.githubState', '');
		$this->f3->set('SESSION.githubName', '');
		$this->f3->reroute('/');
	}
	
}
