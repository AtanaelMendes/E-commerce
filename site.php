<?php
use \Rootdir\PageController;
use \Rootdir\PageClearLogController;
use \Rootdir\Model\Category;

// site
$app->get('/', function() {
	$page = new PageController();
	$page->setTpl("index");
	exit;
});

// CLEARLOG
$app->get('/clearlog', function() {
	$page = new PageClearLogController([
		"header" => false,
		"footer" => false
	]);
	$page->setTpl("index");
	exit;
});

// site categoria
$app->get('/categorias/:idcategory', function($idcategory) {
	$category = new Category();
	$category->get((int)$idcategory);
	$page = new PageController();
	$page->setTpl("category", [
		"category" => $category->expose(),
		"products" => []
	]);
	exit;
});