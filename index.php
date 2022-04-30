<?php
session_start();

use \Slim\Slim;
use \Rootdir\PageController;
use \Rootdir\PageAdminController;
use \Rootdir\Model\User;

require_once("vendor/autoload.php");

$app = new Slim();

$app->config('debug', true);

// gestao
$app->get('/gestao', function() {
	User::verifyLogin();
	$page = new PageAdminController();
	$page->setTpl("index");
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
	error_log(print_r("ERROR  ".basename(__FILE__)." linha-> ".__LINE__, true));
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

// site
$app->get('/', function() {
	$page = new PageController();
	$page->setTpl("index");
	exit;
});

// site
// $app->get('/clearlog', function() {
	// return
	// $page = new PageController(
	// 	[
	// 		"header" => false,
	// 		"footer" => false
	// 	],
	// 	"/views/clearlog/mytools/"
	// );
	// $page->setTpl("clearLog3");
// });

$app->run();

