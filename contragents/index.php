<?php
// https://github.com/chrome-php/chrome
// установка chrome https://setiwik.ru/kak-ustanovit-google-chrome-na-debian-10-11/

require_once 'vendor/autoload.php';
//$inn = "3525392498";
$token = "a03a58a314b69946809d1c983a7e5d78d3764ce0";
$secret = "b28d0b44aa7173ac1412bb6234a14e104a015572";
$data = json_decode(file_get_contents('php://input'));
$app = new App\Parser\Parser($token, $secret, $data->token ?? null);
$app->showJsonInfoByInn($data->inn ?? "");
