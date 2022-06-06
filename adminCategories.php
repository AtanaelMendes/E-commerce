<?php
use \Rootdir\Model\Category;
use \Rootdir\PageAdminController;
use \Rootdir\Model\User;
use \Rootdir\Model\Product;

// tela lista categoria
$app->get('/gestao/categorias', function() {
	User::verifyLogin();
	$search = $_GET["search"] ?? "";
	$pg = (!empty($_GET["page"]) ? (int)$_GET["page"] : 1);
	$pagination = Category::getPaginationAdmin($pg, 3, $search);

	$page = new PageAdminController();
	$page->setTpl("categories", [
		"categories" => $pagination["data"],
		"search" => $search,
		"pages" => $pagination["pages"]
	]);
	exit;
});

//tela cadastro categoria
$app->get('/gestao/categorias/create', function() {
	User::verifyLogin();
	$page = new PageAdminController();
	$page->setTpl("categories-create");
	exit;
});

//POST tela cadastro categoria
$app->post('/gestao/categorias/create', function() {
	User::verifyLogin();
	$category = new Category();
	$category->setData($_POST);
	$category->save();
	header("Location: /gestao/categorias");
	exit;
});

//POST tela cadastro categoria
$app->get('/gestao/categorias/:idcategory/delete', function(string $idcategory) {
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);
	$category->delete();
	header("Location: /gestao/categorias");
	exit;
});

//tela atualiza categoria
$app->get('/gestao/categorias/:idcategory', function(string $idcategory) {
	User::verifyLogin();
	$page = new PageAdminController();
	$category = new Category();
	$category->get((int)$idcategory);
	$page->setTpl("categories-update", [
		"category" => $category->expose()
	]);
	exit;
});

//POST tela atualiza categoria
$app->post('/gestao/categorias/:idcategory', function(string $idcategory) {
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);
	$category->setData($_POST);
	$category->save($_POST);
	header("Location: /gestao/categorias");
	exit;
});

// produto X categoria
$app->get('/gestao/categorias/:idcategory/produto', function(string $idcategory) {
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);
    $page = new PageAdminController();
	$page->setTpl("categories-products", [
        "category" => $category->expose(),
        "productsRelated" => $category->getProducts(),
        "productsNotRelated" => $category->getProducts(false)
    ]);
	exit;
});

// produto add na categoria
$app->get('/gestao/categorias/:idcategory/produto/:idproduct/add', function(string $idcategory, string $idproduct) {
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);
	$produto = new Product();
	$produto->get((int)$idproduct);
	$category->addProduct($produto);
	header("Location: /gestao/categorias/$idcategory/produto");
	exit;
});

// produto remove da categoria
$app->get('/gestao/categorias/:idcategory/produto/:idproduct/remove', function(string $idcategory, string $idproduct) {
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);
	$produto = new Product();
	$produto->get((int)$idproduct);
	$category->removeProduct($produto);
	header("Location: /gestao/categorias/$idcategory/produto");
	exit;
});