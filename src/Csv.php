<?php

namespace Paysera\CommissionTask;

class Csv {
    // read csv file and return array
    public function read_csv($fileName) {
        $rows = [];

        $file = file($fileName);
        foreach($file as $row) {
            $rows[] = explode(',', $row);
        }

        return $rows;
    }

    // write into csv file given $row
    public function write_csv($fileName, $rows) {
        $file = fopen($fileName, 'w');

        foreach($rows as $row) {
            $val = explode(",", $row);
            fputcsv($file, $val);
        }

        fclose($file);
    }
}