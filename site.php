<?php
use \Rootdir\PageController;
use \Rootdir\PageClearLogController;
use \Rootdir\Model\Category;
use Rootdir\Model\Product;
use \Rootdir\Model\Cart;

// site
$app->get('/', function() {
	$produtos = Product::listAll();

	$page = new PageController();
	$page->setTpl("index", [
		"products" => Product::checkList($produtos)
	]);
	exit;
});

// CLEARLOG
$app->get('/clearlog', function() {
	$page = new PageClearLogController([
		"header" => false,
		"footer" => false
	]);
	$page->setTpl("index");
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