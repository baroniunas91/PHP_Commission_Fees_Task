<?php

namespace Task\CommissionTask;

use Task\CommissionTask\Csv;

class CalculateFees {
    // cash in props
    private $commissionFeeCashIn = 0.03;
    private $maxCommissionFeeCashIn = 5;
    // cash out props
    private $commissionFeeCashOut = 0.3;
    private $minCommissionFeeCashOut = 0.5;
    // available currencies
    private $currencyUSD = 1.1497;
    private $currencyJPY = 129.53;
    // discount rules
    private $discountTimes = 3;
    private $freeOfChargeAmount = 1000;
    // array where commission fees results will be store
    private $commissionFeesArray = [];

    // create new object and start calculating data
    public function handleInput($file) {
        $input = new Csv;
        $data = $input->read_csv($file);
        $this->calculate($data);
        $this->showCommissionFees();
        $input->write_csv('commissionFees.csv', $this->commissionFeesArray);
    }

    public function calculate($data) {
        foreach($data as $value) {
            if("natural" == $value[2]) {
                $this->handleNaturalPerson($value, $data);
            } else if ("legal" == $value[2]) {
                $this->handleLegalPerson($value);
            } else {
                die('User type is not correctly described, user ID: ' . $value[1]);
            }
        }
    }
    // Identify user type
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

    // Handle cash in operation, cash in method is the same for both user types
    public function handleCashIn($userData) {
        // calculate commission fee
        $calculatedCommissionFee = ($userData[4] * $this->commissionFeeCashIn) / 100;

        // convert maxCommissionFee -> 5Eur to descriped currencies (if it's Eur, method return the same value -> 5Eur)
        $maxCommissionFee = $this->convertCurrencies($this->maxCommissionFeeCashIn, $userData[5]);
        
        // Checking if calculatedCommissionFee is not more than maxCommissionFee
        if($calculatedCommissionFee < $maxCommissionFee) {
            // if not more, it's rounded to smallest currency item to upper bound (for example: 0.023Eur to 0.03Eur)
            $result = $calculatedCommissionFee;
            $result = number_format(ceil($result * 100) / 100, 2, '.', '');
        } else {
            // if more, result commission fee will be 5.00Eur
            $result = number_format(5, 2, '.', '');
        }
        $this->commissionFeesArray[] = $result;
    }

    // Handle cash out with natural user type
    public function handleCashOutNatural($userData, $allUsersData) {
        // Create an array and put in that array only current users operations -> cash out
        $userOperations = [];
        foreach($allUsersData as $user) {
            if($userData[1] == $user[1] && 'natural' == $user[2] && 'cash_out' == $user[3]) {
                $userOperations[] = $user;
            }
        }

        // remove encoding symbols from $users dates, I need to to it to get timestamp and use strtotime function
        $currentDate = $this->removeEncodingSymbols($userData[0]);
        // Get timestamp 1 week before current operation date
        $timestampBeforeOneWeek = strtotime("$currentDate -1 Week");
        // Current operation date timestamp
        $currentDateTimestamp = strtotime($currentDate);

        $countTimes = 0;

        // Get all user operations dates timestamps and if this timestamp are between current operation 
        // date and current operation date before 1 week, I count times
        foreach($userOperations as $operation) {
            $operationTimestamp = strtotime($this->removeEncodingSymbols($operation[0]));
            if ($operationTimestamp < $currentDateTimestamp && $operationTimestamp > $timestampBeforeOneWeek) {
                $countTimes++;
            }     
        }

        // If counttimes is less than discountTimes(in this case 3 times), the user didn't exceed 
        // 3 cashout operation per week rule, so he will get discount
        if($this->discountTimes > $countTimes) {
            // convert free of charge amount(1000Eur) to described currency
            $freeOfCharge = $this->convertCurrencies($this->freeOfChargeAmount, $userData[5]);

            // If cash out operation amount is not more than $freeOfCharge(1000Eur) commission fee is 0,00Eur
            if($userData[4] <= $freeOfCharge) {
                $result = number_format(0, 2, '.', '');
                
                $this->commissionFeesArray[] = $result;
            } else {
                // if cash out operation amount is bigger than $freeOfCharge(1000Eur),
                // commission is calculated only from exceeded amount
                $amountToFee = $userData[4] - $freeOfCharge;

                // calculate commission fee
                $calculatedCommissionFee = ($amountToFee * $this->commissionFeeCashOut) / 100;
                $result = number_format(ceil($calculatedCommissionFee * 100) / 100, 2, '.', '');
                $this->commissionFeesArray[] = $result;
            }

        } else {
            // If counttimes is more than discountTimes(in this case 3 times)
            // Calculating fees from all amount
            $amountToFee = $userData[4];
            $calculatedCommissionFee = ($amountToFee * $this->commissionFeeCashOut) / 100;
            $result = number_format(ceil($calculatedCommissionFee * 100) / 100, 2, '.', '');
            $this->commissionFeesArray[] = $result;
        }
    }

    // Handle cash out with legal user type
    public function handleCashOutLegal($userData) {
        // calculate commission fee
        $calculatedCommissionFee = ($userData[4] * $this->commissionFeeCashOut) / 100;

        // convert minCommissionFee -> 0.5Eur to descriped currencies (if it's Eur, method return the same value -> 0.5Eur)
        $minCommissionFee = $this->convertCurrencies($this->minCommissionFeeCashOut, $userData[5]);

        // Checking if calculatedCommissionFee is more than minCommissionFee
        if($calculatedCommissionFee > $minCommissionFee) {
            // if more, it's rounded to smallest currency item to upper bound (for example: 0.523Eur to 0.53Eur)
            $result = $calculatedCommissionFee;
            $result = number_format(ceil($result * 100) / 100, 2, '.', '');
        } else {
             // if not more, result of commission fee will be 0.50Eur
            $result = number_format($minCommissionFee, 2, '.', '');
        }
        $this->commissionFeesArray[] = $result;
    }

    // Method -> converting money to described currencies in input.csv file
    public function convertCurrencies($money, $currency) {
        // trim operation is using to remove blank spaces from string
        $currency = trim($currency);
        if('USD' == $currency) {
            return $money * $this->currencyUSD;
        } else if ('JPY' == $currency) {
            return $money * $this->currencyJPY;
        } else {
            return $money;
        }
    }
    // Method to get timestamp and remove encoding symbols
    public function removeEncodingSymbols($date) {
        //Remove encoding symbols from string begin
        if(substr($date,0,3) == pack("CCC",0xef,0xbb,0xbf)) {
            $date = substr($date, 3);
        };
        return $date;
    }

    public function showCommissionFees() {
        foreach($this->commissionFeesArray as $value) {
            echo $value;
            echo "\r\n";
        }
    }
}