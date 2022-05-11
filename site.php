<?php
use \Rootdir\PageController;
use \Rootdir\PageClearLogController;
use \Rootdir\Model\Category;
use Rootdir\Model\Product;

// site
$app->get('/', function() {
	$produtos = Product::listAll();

	$page = new PageController();
	$page->setTpl("index", [
		"products" => Product::checkList($produtos)
	]);
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
		"products" => Product::checkList($category->getProducts())
	]);
	exit;
});
