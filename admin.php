<?php
use \Rootdir\PageAdminController;
use \Rootdir\Model\User;
use \Rootdir\Model\Category;

// gestao
$app->get('/gestao', function() {
	User::verifyLogin();
	$page = new PageAdminController();
	$page->setTpl("index");
	exit;
});

//login
$app->get('/gestao/login', function() {
	$page = new PageAdminController([
		"header" => false,
		"footer" => false
	]);
	$page->setTpl("login");
});

//login
$app->post('/gestao/login', function() {
	User::login($_POST["login"], $_POST["password"]);
	header("Location: /gestao");
	exit();
});

//logout
$app->get('/gestao/logout', function() {
	User::logout();
	header("Location: /gestao/login");
	exit();
});

// consulta user
$app->get('/gestao/users', function() {
	User::verifyLogin();
	$users = User::listAll();
	$page = new PageAdminController();
	$page->setTpl("users", [
		"users"	=> $users
	]);
});

// list create user
$app->get('/gestao/users/create', function() {
	User::verifyLogin();
	$page = new PageAdminController();
	$page->setTpl("users-create");
});

// create user
$app->post('/gestao/users/create', function() {
	User::verifyLogin();
	$user = new User();
	$_POST["inadmin"] = (isset($_POST["inadmin"]) ? 1 : 0);
	$user->setData($_POST);
	$user->save();
	header("Location: /gestao/users");
	exit;
});

// delete user
$app->get('/gestao/users/:iduser/delete', function(string $iduser) {
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$user->delete();
	header("Location: /gestao/users");
	exit;
});

// pega usuario para tela update user
$app->get('/gestao/users/:iduser', function(string $iduser) {
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$page = new PageAdminController();
	$page->setTpl("users-update", [
		"user" => $user->expose()
	]);
});

// realiza update do usuario
$app->post('/gestao/users/:iduser', function(string $iduser) {
	User::verifyLogin();
	$user = new User();
	$_POST["inadmin"] = (isset($_POST["inadmin"]) ? 1 : 0);
	$user->get((int)$iduser);
	$user->setData($_POST);
	$user->update();
	header("Location: /gestao/users");
	exit;
});

// tela lista categoria
$app->get('/gestao/categorias', function() {
	User::verifyLogin();
	$category = Category::listAll();
	$page = new PageAdminController();
	$page->setTpl("categories", [
		"categories" => $category
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