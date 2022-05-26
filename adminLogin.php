<?php
use \Rootdir\PageAdminController;
use \Rootdir\Model\User;

// adminLogin.php

// tela esqueceu a senha
$app->get('/gestao/forgot', function() {
	$page = new PageAdminController([
		"header" => false,
		"footer" => false
	]);
	$page->setTpl("forgot");
	exit;
});

// POST tela esqueceu a senha
$app->post('/gestao/forgot', function() {
	User::getForgot($_POST["email"]);
	header("Location: /gestao/forgot/sent");
	exit;
});

// recuperação email enviada
$app->get('/gestao/forgot/sent', function() {
	$page = new PageAdminController([
		"header" => false,
		"footer" => false
	]);
	$page->setTpl("forgot-sent");
	exit;
});

// tela de rset de senha
$app->get('/gestao/forgot/reset', function() {
	$user = USer::validForgotDecrypt($_POST["code"]);
	$page = new PageAdminController([
		"header" => false,
		"footer" => false
	]);
	$page->setTpl("forgot-reset", [
		"name" => $user["desperson"],
		"code" => $_GET["code"]
	]);
	exit;
});

// POST tela de rset de senha
$app->get('/gestao/forgot/reset', function() {
	$forgot = USer::validForgotDecrypt($_POST["code"]);
	User::setFogotUsed($forgot["idrecovery"]);
	$user = new User();
	$user->get((int)$forgot("iduser"));
	$user->setPassword($_POST["password"]);
	$page = new PageAdminController([
		"header" => false,
		"footer" => false
	]);
	$page->setTpl("forgot-reset-success");
	exit;
});