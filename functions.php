<?php
use \Rootdir\Model\User;

function formatNumber(float $number) {
    return number_format($number, 2, ",", ".");
}

function checkLogin(bool $isadmin = true) :bool {
    return User::checkLogin($isadmin);
}

function getUserName() : ?string {
    $user = User::getFromSession();
    return $user->getdeslogin();
}