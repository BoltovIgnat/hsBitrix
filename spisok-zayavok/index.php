<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

/** @var CMain $APPLICATION */

$APPLICATION->SetTitle("Реестр запросов в Support");

$APPLICATION->IncludeComponent(
    'highsystem:request_support_register',
    '',
    []
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
