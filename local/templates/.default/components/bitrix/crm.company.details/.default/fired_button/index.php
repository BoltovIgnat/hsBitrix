<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$group = $USER->GetUserGroupArray();

CJSCore::Init(array('jquery2'));
$asset->addJs($templateFolder . "/fired_button/script.js?v=1", true);
$asset->addCss($templateFolder . "/fired_button/style.css?v=1", true);

$asset->addString('
<script>
	BX.ready(function () {
		try{
			new BX.FiredButtons('.$arResult["ENTITY_ID"].','.$USER->GetID().');
		} catch(e){
			console.error("error FiredButtons: " + e);
		}
	});
</script>');
