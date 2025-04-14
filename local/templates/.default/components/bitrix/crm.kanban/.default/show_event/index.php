<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addJs($templateFolder . "/show_event/script.js?v=2", true);
$asset->addCss($templateFolder . "/show_event/style.css?v=1", true);

if(!$arResult['ITEMS']['items']) {
	return;
}

CJSCore::Init(array('jquery2'));

$asset->addString('
<script>
	BX.ready(function () {
		try{
			new BX.ShowEvent('. CUtil::PhpToJSObject(['type' => $arParams['ENTITY_TYPE']]).');
		} catch(e){
			console.error("error BX.ShowEvent: " + e);
		}
	});
</script>');