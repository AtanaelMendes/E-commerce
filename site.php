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
		"products" => $cart->getProducts(),
		"error" => $cart->getMsgError()
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
	$address = new Address();
	$cart = Cart::getFromSession();
	$page = new PageController();
	if (!empty($_GET["zipcode"])) {
		$_GET["zipcode"] = $cart->getdeszipcode();
	}
	if (!empty($_GET["zipcode"])) {
		$address->loadFromCEP($_GET["zipcode"]);
		$cart->setdeszipcode($_GET["zipcode"]);
		$cart->save();
		$cart->getCalculateTotal();
	}
	if (!$address->getdesaddress()) {$address->setdesaddress("");}
	if (!$address->getdescomplement()) {$address->setdescomplement("");}
	if (!$address->getdesdistrict()) {$address->setdesdistrict("");}
	if (!$address->getdescity()) {$address->setdescity("");}
	if (!$address->getdesstate()) {$address->setdesstate("");}
	if (!$address->getdescountry()) {$address->setdescountry("");}
	if (!$address->getdeszipcode()) {$address->setdeszipcode("");}
	$page->setTpl("checkout", [
		"cart" => $cart->expose(),
		"address" => $address->expose(),
		"error" => Address::getMsgError(),
		"products" => $cart->getProducts()
	]);
});

$app->post("/checkout", function() {
	User::verifyLogin(false);
	Address::verifyAddressRequest($_POST);
	$user = User::getFromSession();
	$address = new Address();
	$_POST["deszipcode"] = $_POST["zipcode"];
	$_POST["idperson"] = $user->getidperson();
	$address->setData($_POST);
	$address->save();
	header("Location: /order");
	exit;
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
	User::verifyRequestCad($_POST);
	$user = new User();
	$user->setData([
		"inadmin" => 0,
		"deslogin" => $_POST["email"],
		"desperson" => $_POST["name"],
		"desemail" => $_POST["email"],
		"despassword" => $_POST["password"],
		"nrphone" => $_POST["phone"]
	]);

	$user->save();

	$_SESSION["registerValues"] = [
		"name" => "",
		"email" => "",
		"phone" => ""
	];

	User::login($_POST["email"], $_POST["password"]);
	header("Location: /checkout");
	exit;
});

// tela esqueceu a senha
$app->get('/forgot', function() {
	$page = new PageController();
	$page->setTpl("forgot");
	exit;
});

// POST tela esqueceu a senha
$app->post('/forgot', function() {
	User::getForgot($_POST["email"], false);
	header("Location: /forgot/sent");
	exit;
});

// recuperação email enviada
$app->get('/forgot/sent', function() {
	$page = new PageController();
	$page->setTpl("forgot-sent");
	exit;
});

// tela de rset de senha
$app->get('/forgot/reset', function() {
	$user = USer::validForgotDecrypt($_POST["code"]);
	$page = new PageController();
	$page->setTpl("forgot-reset");
	exit;
});

// POST tela de rset de senha
$app->get('/forgot/reset', function() {
	$forgot = USer::validForgotDecrypt($_POST["code"]);
	User::setFogotUsed($forgot["idrecovery"]);
	$user = new User();
	$user->get((int)$forgot("iduser"));
	$user->setPassword($_POST["password"]);
	$page = new PageController();
	$page->setTpl("forgot-reset-success");
	exit;
});

// meu perfil
$app->get("/profile", function() {
	User::verifyLogin(false);
	$user = User::getFromSession();
	$page = new PageController();
	$page->setTpl("profile",[
		"user" => $user->expose(),
		"profileError" => User::getMsgError(),
		"profileMsg" => User::getSuccess()
	]);
});

// POST edicao perfil
$app->post("/profile", function() {
	$user = User::getFromSession();
	User::verifyLogin(false);
	User::verifyRequestEditProfile($_POST, $user);
	$_POST["inadmin"] = $user->getinadmin();
	$_POST["despassword"] = $user->getdespassword();
	$_POST["deslogin"] = $_POST["desemail"];
	$user->setData($_POST);
	$user->update();
	User::setSuccess("Dados alterados com sucesso!");
	header("Location: /profile");
	exit;
});

// $app->post("", function() {

// });

// $app->get("", function() {

// });