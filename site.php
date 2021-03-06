<?php
use \Rootdir\PageController;
use \Rootdir\Model\Category;
use Rootdir\Model\Product;
use \Rootdir\Model\Cart;
use \Rootdir\Model\Order;
use \Rootdir\Model\OrderStatus;
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
	$cart = Cart::getFromSession();
	$cart->getCalculateTotal();
	$order = new Order();
	$order->setData([
		"idcart" => $cart->getidcart(),
		"idaddress" => $address->getidaddress(),
		"iduser" => $user->getiduser(),
		"idstatus" => OrderStatus::EM_ABERTO,
		"vltotal" => $cart->getvltotal()
	]);
	$order->save();
	header("Location: /order/".$order->getidorder());
	exit;
});

// login de usu??rio
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

// cadastro usu??rio comum
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

// recupera????o email enviada
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

// adicionar pagamamento
$app->get("/order/:idorder", function($idorder) {
	User::verifyLogin(false);
	$order = new Order();
	$order->get((int)$idorder);
	$page = new PageController();
	$page->setTpl("payment", [
		"order" => $order->expose()
	]);
});

$app->get("/boleto/:idorder", function($idorder) {
	User::verifyLogin(false);
	$order = new Order();
	$order->get((int)$idorder);

	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006";
	$valor_cobrado = $order->getvltotal(); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	// $valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: M??ximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emiss??o do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com v??rgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress()." ".$order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity()." - ".$order->getdesstate()." - ".$order->getdescountry()." - ".$order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa banc??ria - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% ap??s o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber at?? 10 dias ap??s o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de d??vidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";

	// ---------------------- DADOS FIXOS DE CONFIGURA????O DO SEU BOLETO --------------- //
	// DADOS DA SUA CONTA - ITA??
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITA??
	$dadosboleto["carteira"] = "175";  // C??digo da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Le??o, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "S??o Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

	// N??O ALTERAR!
	$dir = DIRECTORY_SEPARATOR;
	$path = $_SERVER["DOCUMENT_ROOT"].$dir."resources".$dir."boletophp".$dir."include".$dir;
	require_once($path."funcoes_itau.php");
	require_once($path."layout_itau.php");
});

// tela meus pedidos
$app->get("/profile/orders", function() {
	User::verifyLogin(false);
	$user = User::getFromSession();
	$page = new PageController();
	$page->setTpl("profile-orders", [
		"orders" => $user->getOrders()
	]);
});

// tela detallhes do pedido
$app->get("/profile/orders/:idorder", function($idorder) {
	User::verifyLogin(false);
	$order = new Order();
	$order->get((int)$idorder);
	$cart = new Cart();
	$cart->get((int)$order->getidcart());
	$cart->getCalculateTotal();
	$page = new PageController();
	$page->setTpl("profile-orders-detail", [
		"order" => $order->expose(),
		"cart" => $cart->expose(),
		"products" => $cart->getProducts()
	]);
});

// tela alterar senha
$app->get("/profile/change-password", function() {
	User::verifyLogin(false);
	$page = new PageController();
	$page->setTpl("profile-change-password", [
		"changePassError" => User::getMsgError(),
		"changePassSuccess" => User::getSuccess()
	]);
});

// POST alterar senha
$app->post("/profile/change-password", function() {
	User::verifyLogin(false);
	if (empty($_POST["current_pass"]) && strlen($_POST["current_pass"]) < 6) {
		User::setMsgError("Digite a senha atual");
		header("Location: /profile/change-password");
		exit;
	}
	if (empty($_POST["new_pass"]) && strlen($_POST["new_pass"]) < 6) {
		User::setMsgError("Digite a nova senha");
		header("Location: /profile/change-password");
		exit;
	}
	if (empty($_POST["new_pass_confirm"]) && strlen($_POST["new_pass_confirm"]) < 6) {
		User::setMsgError("Confirme a nova senha");
		header("Location: /profile/change-password");
		exit;
	}
	if (empty($_POST["new_pass"]) === $_POST["current_pass"]) {
		User::setMsgError("N??o pode utilizar a mesma senha");
		header("Location: /profile/change-password");
		exit;
	}
	$user = User::getFromSession();
	if (!password_verify($_POST["current_pass"], $user->getdespassword())) {
		User::setMsgError("A senha ?? inv??lida");
		header("Location: /profile/change-password");
		exit;
	}
	$user->sedespassword($_POST["new_pass"]);
	$user->update();
	User::setSuccess("Senha alterada com sucesso");
	header("Location: /profile/change-password");
	exit;
});

// $app->post("", function() {

// });

// $app->get("", function() {

// });
