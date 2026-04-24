<?php

include './bootstrap.php';

use Amp\File;
use Flyokai\AmpCsvReader\CsvReader;

$file = File\openFile(__DIR__ . '/example.csv', 'r');

$csvReader = new CsvReader($file, 500);

foreach ($csvReader as $row) {
    var_dump($row);
}