<?php

use Paysera\CommissionTask\CalculateFees;

define('DIR', __DIR__ . '/');
require DIR . 'vendor/autoload.php';
$calculateFees = new CalculateFees;
$calculateFees->handleInput('input.csv');