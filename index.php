<?php
session_start();

use \Slim\Slim;

require_once("vendor/autoload.php");

$app = new Slim();

$app->config('debug', true);

require_once("site.php");
require_once("admin.php");
require_once("adminLogin.php");
require_once("adminProduct.php");

$app->run();

