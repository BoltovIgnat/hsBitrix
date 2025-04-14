<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addJs($templateFolder . "/check_field_inn/script.js?v=1", true);
$asset->addCss($templateFolder . "/check_field_inn/style.css?v=1", true);

$fieldCode = CRM_SETTINGS['lead']['companyInn'];

if ($_REQUEST["dontHideFields"] != "Y") {
	$asset->addString('
	<script>
		BX.ready(function () {
			try{
				new BX.CheckFieldInn("'. $fieldCode .'",'.$arResult["ENTITY_ID"].');
			} catch(e){
				console.error("error CheckFieldInn: " + e);
			}
		});
	</script>');

	CModule::IncludeModule("pull");	
	$res = CPullWatch::Add(604, 'PULL_TEST');
}