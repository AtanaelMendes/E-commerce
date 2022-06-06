<?php
use \Rootdir\PageAdminController;
use \Rootdir\Model\User;


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
	$page->setTpl("login", [
		"error" => User::getMsgError(),
	]);
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
	$page = new PageAdminController();
	$search = $_GET["search"] ?? "";
	$pg = (!empty($_GET["page"]) ? (int)$_GET["page"] : 1);
	$pagination = User::getPagination($pg, 3, $search);

	$page->setTpl("users", [
		"users"	=> $pagination["data"],
		"search" => $search,
		"pages" => $pagination["pages"]
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

