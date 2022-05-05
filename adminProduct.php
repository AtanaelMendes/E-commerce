<?php

use \Rootdir\PageAdminController;
use \Rootdir\Model\User;
use \Rootdir\Model\Product;

//adminProduct.php

// admin produto
$app->get('/gestao/produtos', function() {
	User::verifyLogin();
	$products = Product::listAll();
	$page = new PageAdminController();
	$page->setTpl("products", [
		"products"	=> $products
	]);
    exit;
});

// view Create produto
$app->get('/gestao/produtos/create', function() {
	User::verifyLogin();
	$page = new PageAdminController();
	$page->setTpl("products-create");
    exit;
});

// POST Create produto
$app->post('/gestao/produtos/create', function() {
	User::verifyLogin();
	$product = new Product();
	$product->setData($_POST);
	$product->save();
	header("Location: /gestao/produtos");
    exit;
});

// view edit produto
$app->get('/gestao/produtos/:idproduct', function($idproduct) {
	User::verifyLogin();
    $product = new Product();
	$product->get((int)$idproduct);
	$page = new PageAdminController();
	$page->setTpl("products-update", [
		"product"	=> $product->expose()
	]);
    exit;
});

// POST update produto
$app->post('/gestao/produtos/:idproduct', function($idproduct) {
	User::verifyLogin();
    $product = new Product();
	$product->get((int)$idproduct);
	$product->setData($_POST);
	$product->save();
	$product->addPhoto($_FILES["file"]);
    header("Location: /gestao/produtos");
    exit;
});

// Delete produto
$app->get('/gestao/produtos/:idproduct/delete', function(string $idproduct) {
	User::verifyLogin();
	$produto = new Product();
	$produto->get((int)$idproduct);
	$produto->delete();
	header("Location: /gestao/produtos");
	exit;
});
