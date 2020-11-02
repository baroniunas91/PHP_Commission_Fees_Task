<?php

namespace Paysera\CommissionTask;

use Paysera\CommissionTask\Csv;

class CalculateFees {

    private $commissionFeeCashIn = 0.03;
    private $maxCommissionFeeCashIn = 5;
    private $commissionFeeCashOut = 0.3;
    private $minCommissionFeeCashOut = 0.5;
    private $currencyUSD = 1.1497;
    private $currencyJPY = 129.53;


    public function handleInput($file) {
        $input = new Csv;
        $data = $input->read_csv($file);
        $this->calculate($data);
    }

    public function calculate($data) {
        foreach($data as $value) {
            if("natural" == $value[2]) {
                $this->handleNaturalPerson($value, $data);
            } else if ("legal" == $value[2]) {
                $this->handleLegalPerson($value);
            } else {
                die('User type is not described, user ID: ' . $value[1]);
            }
        }
    }

    public function handleNaturalPerson($userData, $allUsersData) {
        if("cash_in" == $userData[3]) {
            $this->handleCashIn($userData);
        } else if ("cash_out" == $userData[3]) {
            $this->handleCashOutNatural($userData, $allUsersData);
        } else {
            die('Operation type is not described, user ID: ' . $value[1]);
        }
    }

    public function handleLegalPerson($userData) {
        if("cash_in" == $userData[3]) {
            $this->handleCashIn($userData);
        } else if ("cash_out" == $userData[3]) {
            $this->handleCashOutLegal($userData);
        } else {
            die('Operation type is not described, user ID: ' . $value[1]);
        }
    }

    public function handleCashIn($userData) {
        $calculatedCommissionFee = ($userData[4] * $this->commissionFeeCashIn) / 100;

        $maxCommissionFee = $this->convertCurrencies($this->maxCommissionFeeCashIn, $userData[5]);
        
        if($calculatedCommissionFee < $maxCommissionFee) {
            $result = $calculatedCommissionFee;
            $result = number_format(ceil($result * 100) / 100, 2);
        } else {
            $result = number_format(5, 2);
        }
        // print_r($result . ' ');
    }

    public function handleCashOutNatural($userData, $allUsersData) {
        foreach($allUsersData as $user) {
            if($userData[1] == $user[1]) {
                $date = $user[0];
                //Remove encoding symbols from string begin
                if(substr($date,0,3) == pack("CCC",0xef,0xbb,0xbf)) {
                    $date = substr($date, 3);
                };
                // var_dump($date);
                $timestamp = strtotime($date);
                $newDate = date("Y-M-j", $timestamp);
                echo '<pre>';
                var_dump($newDate);
            }
        }
    }

    public function handleCashOutLegal($userData) {
        $calculatedCommissionFee = ($userData[4] * $this->commissionFeeCashOut) / 100;
        $minCommissionFee = $this->convertCurrencies($this->minCommissionFeeCashOut, $userData[5]);

        if($calculatedCommissionFee > $minCommissionFee) {
            $result = $calculatedCommissionFee;
            $result = number_format(ceil($result * 100) / 100, 2);
        } else {
            $result = number_format(0.5, 2);
        }
        // print_r($result . ' ');
    }

    public function convertCurrencies($money, $currency) {
        if('USD' == $currency) {
            return $money * $this->currencyUSD;
        } else if ('JPY' == $currency) {
            return $money * $currencyJPY;
        } else {
            return $money;
        }
    }
}