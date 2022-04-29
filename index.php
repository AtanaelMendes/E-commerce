<?php

use \Slim\Slim;
use \Rootdir\PageController;
use \Rootdir\PageAdminController;

require_once("vendor/autoload.php");

$app = new Slim();

$app->config('debug', true);

$app->get('/gestao', function() {
    
	$page = new PageAdminController;

	$page->setTpl("index");
	
});

$app->get('/', function() {
    
	$page = new PageController;

 	$page->setTpl("index");
	
});

$app->run();

