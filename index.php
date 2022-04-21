<?php

use \Slim\Slim;
use \Rootdir\PageController;
use \Rootdir\PageAdminController;

require_once("vendor/autoload.php");

$app = new Slim();

$app->config('debug', true);

// $app->get('/', function() {
    
// 	$page = new PageController;

// 	$page->setTpl("index");
	
// });

$app->get('/gestao-login', function() {
    
	$page = new PageAdminController;

	$page->setTpl("index");
	
});

$app->run();

