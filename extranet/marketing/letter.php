<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent("bitrix:sender.letter", ".default", array(
	'SEF_FOLDER' => '/extranet/marketing/letter/',
	'PATH_TO_SEGMENT_ADD' => '/extranet/marketing/segment/edit/0/',
	'PATH_TO_SEGMENT_EDIT' => '/extranet/marketing/segment/edit/#id#/',
));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");