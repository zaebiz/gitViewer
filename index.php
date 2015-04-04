<?php
	session_start();
	
	$f3 = require(__DIR__.'/f3/base.php');
	$f3->set('AUTOLOAD', 'controllers/; models/');
	$f3->set('UI', 'views/');
	$f3->set('DEBUG', 3);
	
	$f3->route('GET / ', 'SiteController->actionIndex');
	$f3->route('GET /gitauth ', 'SiteController->actionGitAuth');
	$f3->route('GET /@owner/@repo', 'SiteController->actionGetCommits');
	$f3->route('GET /remove', 'SiteController->actionRemove');
	$f3->route('GET /clear', 'SiteController->clearAuth');
	
	$f3->route('POST /search', function($f3) {
		$repo = strtolower($f3->get('POST.repo'));
		$owner = strtolower($f3->get('POST.owner'));
		$f3->reroute("/$owner/$repo");
	});
	
	
	$f3->run();
	