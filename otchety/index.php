<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Отчёт по документам");

$APPLICATION->IncludeComponent(
    "highsystem:documentreport",
    ".default",
    [
        "IBLOCK_ID" => 59 // Или ваш инфоблок с документами
        // + любые другие параметры
    ]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>