<?
use Dbbo\Debug\Dump;

$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addJs("https://cdn.jsdelivr.net/npm/suggestions-jquery@21.12.0/dist/js/jquery.suggestions.min.js");
$asset->addJs($templateFolder . "/hs221/script.js?v=3", true);
$asset->addCss($templateFolder . "/hs221/style.css?v=1", true);
$asset->addCss("https://cdn.jsdelivr.net/npm/suggestions-jquery@21.12.0/dist/css/suggestions.min.css");

$params = [
	'fieldCodeInn' => [
		'code' => CRM_SETTINGS['deal']['companyInn'],
		'value' => $arResult['ENTITY_DATA'][CRM_SETTINGS['deal']['companyInn']]['VALUE'] ?: ''
	],
	'fieldCodeUridAddress' => [
		'code' => CRM_SETTINGS['deal']['jurAddressIP'],
		'value' => $arResult['ENTITY_DATA'][CRM_SETTINGS['deal']['jurAddressIP']]
	],
	'fieldCodeUridAddressAll' => CRM_SETTINGS['deal']['jurAddressIPjson'],
	'fieldCodeAddressDelivery' => CRM_SETTINGS['deal']['addressDelivery'],
	'fieldCodeAddressDeliveryJson' => CRM_SETTINGS['deal']['addressDeliveryJson'],
	'apikey' => '7504b12914f902634d73612b5f0be9ed93283d1c',
	'url' => $templateFolder . "/hs221/ajax.php",
	'id' => $arResult['ENTITY_DATA']['ID']
];

$asset->addString('
<script>
	BX.ready(function () {
		try{
			new BX.HS221('. CUtil::PhpToJSObject($params) .');
		} catch(e){
			console.error("error HS221: " + e);
		}
	});
</script>');