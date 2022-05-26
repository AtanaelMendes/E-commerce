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
		"address" => $address->expose()
	]);

});

// login de usuário
$app->get("/login", function() {
	$page = new PageController();
	$page->setTpl("login", [
		"error" => User::getMsgError()
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

$app->get("/logout", function() {
	User::logout();
	header("Location: /login");
	exit;
});

// $app->post("", function() {

// });

// $app->get("", function() {

// });