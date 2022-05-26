<?php
use \Rootdir\PageController;
use \Rootdir\Model\Category;
use Rootdir\Model\Product;
use \Rootdir\Model\Cart;
use \Rootdir\Model\User;
use \Rootdir\Model\Address;

// site
$app->get('/', function() {
	$produtos = Product::listAll();

	$page = new PageController();
	$page->setTpl("index", [
		"products" => Product::checkList($produtos)
	]);
	exit;
});

// site categoria
$app->get('/categorias/:idcategory', function($idcategory) {
	$category = new Category();
	$category->get((int)$idcategory);
	$pagination = $category->getPagination((isset($_GET['page']) ? $_GET['page'] : 1));
	$page = new PageController();
	$page->setTpl("category", [
		"category" => $category->expose(),
		"products" => $pagination["data"],
		"pages" => $pagination["pages"]
	]);
	exit;
});

// detalhes produto
$app->get('/produto/:desurl', function($desurl) {
	$produto = new Product();
	$produto->getFromURL($desurl);
	$page = new PageController();
	$page->setTpl("product-detail", [
		"product" => $produto->expose(),
		"categories" => $produto->getCategories()
	]);
	exit;
});

// tela carrinho
$app->get('/cart', function(){
	$cart = Cart::getFromSession();
	$page = new PageController();
	$page->setTpl("cart", [
		"cart" => $cart->expose(),
		"products" => $cart->getProducts()
	]);
});

// adicionar produto do carrinho
$app->get("/cart/:idproduct/add", function($idproduct){
	$product = new Product();
	$product->get((int)$idproduct);
	$cart = Cart::getFromSession();
	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;
	for ($i = 0; $i < $qtd; $i++) {
		$cart->addProduct($product);
	}
	header("Location: /cart");
	exit;
});

// remover um produto do carrinho
$app->get('/cart/:idproduct/minus', function($idproduct) {
	$produto = new Product();
	$produto->get((int)$idproduct);
	$cart = Cart::getFromSession();
	$cart->removeProd($produto);
	header("Location: /cart");
	exit;
});

// remover todos os produtos do mesmo tipo do carrinho
$app->get('/cart/:idproduct/remove', function($idproduct) {
	$produto = new Product();
	$produto->get((int)$idproduct);
	$cart = Cart::getFromSession();
	$cart->removeProd($produto, true);
	header("Location: /cart");
	exit;
});

// Calculo de frete
$app->post('/cart/freight', function() {
	$cart = Cart::getFromSession();
	$cart->setFreight($_POST["zipcode"]);
	header("Location: /cart");
	exit;
});

// finalizar compra
$app->get("/checkout", function() {
	User::verifyLogin(false);
	$cart = Cart::getFromSession();
	$address = new Address();
	$page = new PageController();
	$page->setTpl("checkout", [
		"cart" => $cart->expose(),
		"address" => $address->expose(),
		"error" => ""
	]);

});

// login de usuário
$app->get("/login", function() {
	$page = new PageController();
	$page->setTpl("login", [
		"error" => User::getMsgError(),
		"errorRegister" => User::getMsgErrorRegister(),
		"registerValues" => (isset($_SESSION["registerValues"]) ? $_SESSION["registerValues"] : [
			"name" => "",
			"email" => "",
			"phone" => ""
		])
	]);

});

// rota de login
$app->post("/login", function() {
	try {
		User::login($_POST["login"], $_POST["password"]);
	} catch (\Exception $e) {
		User::setMsgError($e->getMessage());
	}
	header("Location: /checkout");
	exit;
});

// logout
$app->get("/logout", function() {
	User::logout();
	header("Location: /login");
	exit;
});

// cadastro usuário comum
$app->post("/register", function() {
	$_SESSION["registerValues"] = $_POST;
	if (empty($_POST["name"])  || strlen($_POST["name"]) < 3) {
		User::setMsgErrorRegister("Nome é obrigatorio e mínimo de 3 letras");
		header("Location: /login");
		exit;
	}
	$emailRegex = "/^[a-z0-9.]+@[a-z0-9]+\.[a-z]+\.([a-z])+?$/i";
	$isemail = preg_match($emailRegex, $_POST["email"]);
	if (empty($_POST["email"])  || !$isemail) {
		User::setMsgErrorRegister("Informe um E-mail válido");
		header("Location: /login");
		exit;
	}
	if (User::checkLoginExist($_POST["email"])) {
		User::setMsgErrorRegister("Este E-mail já está em uso");
		header("Location: /login");
		exit;
	}
	if (empty($_POST["password"])  || strlen($_POST["password"]) < 6) {
		User::setMsgErrorRegister("Senha é obrigatorio, mínimo de 6 caracteres");
		header("Location: /login");
		exit;
	}
	$user = new User();
	$user->setData([
		"inadmin" => 0,
		"deslogin" => $_POST["email"],
		"desperson" => utf8_decode($_POST["name"]),
		"desemail" => $_POST["email"],
		"despassword" => $_POST["password"],
		"nrphone" => $_POST["phone"]
	]);

	$user->save();

	User::login($_POST["email"], $_POST["password"]);
	header("Location: /checkout");
	exit;
});

// $app->post("", function() {

// });

// $app->get("", function() {

// });