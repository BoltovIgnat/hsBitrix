<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

/** @var CAllMain $APPLICATION */
$APPLICATION->IncludeComponent(
	"bitrix:crm.exclusion",
	".default",
	['SEF_FOLDER' => '/extranet/crm/configs/exclusion/',]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");