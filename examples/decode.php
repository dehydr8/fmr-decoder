<?php

require_once(__DIR__ . "/../vendor/autoload.php");

$bin = file_get_contents(__DIR__ . "/../samples/sample.iso");
$d = new dehydr8\FMR\Decoder($bin);
echo json_encode($d->decode(), JSON_PRETTY_PRINT);

?>