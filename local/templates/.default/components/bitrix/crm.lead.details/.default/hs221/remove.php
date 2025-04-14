<?
use Dbbo\Debug\Dump;

if('0' === $arResult['ENTITY_DATA'][CRM_SETTINGS['lead']['jurAddressIP']]['VALUE']) {
	$arResult['ENTITY_DATA'][CRM_SETTINGS['lead']['jurAddressIP']]['VALUE'] = '';
	$arResult['ENTITY_DATA'][CRM_SETTINGS['lead']['jurAddressIP']]['IS_EMPTY'] = true;
}