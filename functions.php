<?php
use \Rootdir\Model\User;
use \Rootdir\Model\Cart;

function formatNumber($number) {
    $valpice = ($number > 0 ? $number : 0);
    return number_format($valpice, 2, ",", ".");
}

function formatDate($date) {
    return date("d/m/Y", strtotime($date));
}

function checkLogin(bool $isadmin = true) :bool {
    return User::checkLogin($isadmin);
}

function getUserName() : ?string {
    $user = User::getFromSession();
    return $user->getdesperson();
}

function getCartNrqtd() {
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();
    return $totals["qtprod"];
}

function getCartVlprice() {
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();
    return formatNumber($totals["vlprice"]);
}