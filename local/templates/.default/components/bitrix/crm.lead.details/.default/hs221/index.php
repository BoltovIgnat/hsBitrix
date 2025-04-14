<?
use Dbbo\Debug\Dump;

$asset = \Bitrix\Main\Page\Asset::getInstance();

$asset->addJs("https://cdn.jsdelivr.net/npm/suggestions-jquery@21.12.0/dist/js/jquery.suggestions.min.js");
$asset->addJs($templateFolder . "/hs221/script.js?v=5", true);
$asset->addCss($templateFolder . "/hs221/style.css?v=1", true);
$asset->addCss("https://cdn.jsdelivr.net/npm/suggestions-jquery@21.12.0/dist/css/suggestions.min.css");

$params = [
	'fieldCodeInn' => [
		'code' => CRM_SETTINGS['lead']['companyInn'],
		'value' => $arResult['ENTITY_DATA'][CRM_SETTINGS['lead']['companyInn']],
	],
	'fieldCodeUridAddress' => [
		'code' => CRM_SETTINGS['lead']['jurAddressIP'],
		'value' => $arResult['ENTITY_DATA'][CRM_SETTINGS['lead']['jurAddressIP']]
	],
	'fieldCodeUridAddressAll' => CRM_SETTINGS['lead']['jurAddressIPjson'],
	'fieldCodeAddressDelivery' => CRM_SETTINGS['lead']['addressDelivery'],
	'fieldCodeAddressDeliveryJson' => CRM_SETTINGS['lead']['addressDeliveryJson'],
	'apikey' => '7504b12914f902634d73612b5f0be9ed93283d1c',
	'url' => $templateFolder . "/hs221/ajax.php",
	'id' => $arResult['ENTITY_DATA']['ID'],
	'status' => $arResult['ENTITY_DATA']['STATUS_ID']
];

$asset->addString('
<script>
	BX.ready(function () {
		try{
			new BX.HS221('. CUtil::PhpToJSObject($params) .');
		} catch(e){
			console.error("error HS211: " + e);
		}
	});
</script>');