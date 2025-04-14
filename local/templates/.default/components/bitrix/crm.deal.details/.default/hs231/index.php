<?
use Dbbo\Debug\Dump;

$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addJs($templateFolder . "/hs231/script.js?v=1", true);
$asset->addCss($templateFolder . "/hs231/style.css?v=1", true);

$params = [
	'workflowId' => 177,
	'url' => $templateFolder . "/hs231/ajax.php",
	'id' => $arResult['ENTITY_ID']
];

$asset->addString('
<script>
	BX.ready(function () {
		try{
			new BX.HS231('. CUtil::PhpToJSObject($params) .');
		} catch(e){
			console.error("error HS231: " + e);
		}
	});
</script>');