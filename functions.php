<?php
use \Rootdir\Model\User;

function formatNumber($number) {
    $valpice = ($number > 0 ? $number : 0);
    return number_format($valpice, 2, ",", ".");
}

function checkLogin(bool $isadmin = true) :bool {
    return User::checkLogin($isadmin);
}

function getUserName() : ?string {
    $user = User::getFromSession();
    return $user->getdesperson();
}