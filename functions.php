<?php

function formatNumber(float $number) {
    return number_format($number, 2, ",", ".");
}