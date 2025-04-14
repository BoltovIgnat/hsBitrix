<?
$asset = \Bitrix\Main\Page\Asset::getInstance();

CJSCore::Init(array('jquery2'));
$asset->addJs($templateFolder . "/control_require_fields/script.js?v=1", true);
$asset->addCss($templateFolder . "/control_require_fields/style.css?v=1", true);

$asset->addString('
<script>
	BX.ready(function () {
		try{
			new BX.ControlRequireFields();
		} catch(e){
			console.error("error ControlRequireFields: " + e);
		}
	});
</script>');