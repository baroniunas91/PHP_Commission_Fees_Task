<?php

use Task\CommissionTask\CalculateFees;

define('DIR', __DIR__ . '/');
require DIR . 'vendor/autoload.php';
$calculateFees = new CalculateFees;
// $argv[1] = input.csv
// parameter which you give to php script.php (input.csv) -> parameter
$calculateFees->handleInput($argv[1]);