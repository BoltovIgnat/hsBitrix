<?
use Dbbo\Debug\Dump;

$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addJs($templateFolder . "/hs225/script.js?v=1a", true);
$asset->addCss($templateFolder . "/hs225/style.css?v=1", true);

$params = [
	'fieldCodeTypeCompany' => [
		'code' => 'UF_CRM_6417603D7286F',
		'value' => $arResult['ENTITY_DATA']['UF_CRM_6417603D7286F']
	],
];

$asset->addString('
<script>
	BX.ready(function () {
		try{
			new BX.HS225('. CUtil::PhpToJSObject($params) .');
		} catch(e){
			console.error("error HS225: " + e);
		}
	});
</script>');