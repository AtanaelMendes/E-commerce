<?php

use \Rootdir\PageAdminController;
use \Rootdir\Model\User;
use \Rootdir\Model\Order;
use \Rootdir\Model\OrderStatus;

// tela deletar pedido
$app->get("/gestao/orders/:idorder/delete", function($idorder) {
    User::verifyLogin();
    $order = new Order();
    $order->get((int)$idorder);
    $order->delete();
    header("Location: /gestao/orders");
    exit;
});

// tela status do pedido
$app->get("/gestao/orders/:idorder/status", function($idorder) {
    User::verifyLogin();
    $order = new Order();
    $order->get((int)$idorder);
    $page = new PageAdminController();
    
    $page->setTpl("order-status", [
        "order" => $order->expose(),
        "status" => OrderStatus::listAll(),
        "msgSuccess" => Order::getSuccess(),
        "msgError" => Order::getMsgError()
    ]);
});

// POST atualizar status
$app->post("/gestao/orders/:idorder/status", function($idorder) {
    User::verifyLogin();
    if (empty($_POST["idstatus"]) || !(int)$_POST["idstatus"] > 0) {
        Order::setMsgError("Informe o status atual");
        header("Location: /gestao/orders/$idorder/status");
        exit;
    }

    $order = new Order();
    $order->get((int)$idorder);
    $order->setidstatus((int)$_POST["idstatus"]);
    $order->save();
    Order::setSuccess("Status atualizado");
    header("Location: /gestao/orders/$idorder/status");
    exit;
});

// gestao de pedido
$app->get("/gestao/orders/:idorder", function($idorder) {
    User::verifyLogin();
    $page = new PageAdminController();
    $order = new Order();
    $cart = $order->getCart();
    $order->get((int)$idorder);
    $page->setTpl("order", [
        "order" => $order->expose(),
        "cart" => $cart->expose(),
        "products" => $cart->getProducts()
    ]);
});

// lista gestao de pedidos
$app->get("/gestao/orders", function() {
    User::verifyLogin();
    $page = new PageAdminController();
    $page->setTpl("orders", [
        "orders" => Order::listAll()
    ]);
});