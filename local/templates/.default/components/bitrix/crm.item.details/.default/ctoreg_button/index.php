<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$group = $USER->GetUserGroupArray();

CJSCore::Init(array('jquery2'));
$asset->addJs($templateFolder . "/ctoreg_button/script.js?v=1", true);
$asset->addCss($templateFolder . "/ctoreg_button/style.css?v=1", true);

$asset->addString('
<script>
	BX.ready(function () {
		try{
			new BX.CToRegButton('.$arResult["entityDetailsParams"]["ENTITY_ID"].','.$USER->GetID().');
		} catch(e){
			console.error("error FiredButtons: " + e);
		}
	});
</script>');
