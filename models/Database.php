<?php

class Database
{
	const PAGE_SIZE = 10;
	private $db;
	
	function __construct()
	{
		$this->db=new DB\SQL(
			'mysql:host=localhost;port=3306;dbname=github',
			'root',
			''
		);
	}
	
	private function addRepo($owner, $repo)
	{
		$repoMapper = new DB\SQL\Mapper($this->db, 'repos');
		$repoMapper->name = $repo;
		$repoMapper->owner = $owner;
		$result = $repoMapper->insert();
		
		return (isset($result->id)) ? $result->id : 0;
	}
	
	private function addCommits($repoId, $commits)
	{
		$commitMapper = new DB\SQL\Mapper($this->db, 'commits');
		foreach ($commits as $newCommit)
		{
			$commitMapper->repo_id = $repoId;
			$commitMapper->sha = $newCommit['sha'];
			//$commitMapper->date = $newCommit['commit']['author']['date'];
			$commitMapper->data = json_encode($newCommit);
			$result = $commitMapper->save();
			$commitMapper->reset();
		}
	}
	
	function addNewCommits($owner, $repo, $commits)
	{
		$repoId = $this->addRepo($owner, $repo);
		
		if ($repoId > 0) 
			$this->addCommits($repoId, $commits);
		
		return $repoId;
	}
	
	function removeCommitsById($ids)
	{
		$in = implode(',', $ids);
		$this->db->exec("DELETE FROM commits WHERE id IN ($in)");
	}
	
	function removeCommitsByRepo($repoId)
	{
		$this->db->exec("DELETE FROM commits WHERE repo_id = $repoId");
	}
	
	function removeRepo($id)
	{
		$this->removeCommitsByRepo($id);
		$repo = $this->db->exec("DELETE FROM repos WHERE id = $id");
	}
	
	function checkRepoExists($owner, $name)
	{
		$repo = $this->db->exec("SELECT id FROM repos where name LIKE :name AND owner LIKE :owner", array(
			'name'=> $name,
			'owner'=> $owner,
		));
		
		return (count($repo) > 0) ? $repo[0]['id'] : 0;
	}

	function getLastRepos($count)
	{
		return $this->db->exec("SELECT * FROM repos ORDER BY id DESC LIMIT $count");
	}
	
	function getRepoCommitsCount($repoId)
	{
		$res = $this->db->exec("SELECT COUNT(*) as count FROM commits WHERE repo_id=$repoId");
		return intval($res[0]['count']);
	}
	
	function getRepoCommits($repoId, $page)
	{
		$commitMapper = new DB\SQL\Mapper($this->db, 'commits');
		$data = $commitMapper->paginate($page, self::PAGE_SIZE, array('repo_id = ?', $repoId), array('order'=>'id ASC'));
        
		$commits = array();
		foreach ($data['subset'] as $commit)
			$commits[] = $this->prepareToBindCommit($commit);

		return $commits;
	}
	
	function prepareToBindCommit($commit)
	{
		$item = json_decode($commit->data);
		$item->id = $commit->id;
		
		$shortDate = strtotime($item->commit->author->date);
		$item->date = date( 'd-m-Y', $shortDate );
		
		if (!isset($item->author))
		{
			$author = new stdClass();
			$author->login = 'unknown';
			$author->html_url = 'javascript:void(0)';
			$author->avatar_url = 'https://avatars.githubusercontent.com/u/11500901?v=3&s=72';
			$item->author = $author;
		}
		
		return $item;
	}
	
}

