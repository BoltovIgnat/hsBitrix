<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
//$asset->addJs($templateFolder . "/remove_fields/script.js?v=2", true);
$group = $USER->GetUserGroupArray();

if(!defined('STOP_GROUP_ID')) {
	define('STOP_GROUP_ID', 26);
}

if(in_array(STOP_GROUP_ID, $group)) {
	$asset->addCss($templateFolder . "/remove_fields/style.css?v=1", true);
}