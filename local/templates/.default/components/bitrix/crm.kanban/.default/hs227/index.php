<?
$group = $USER->GetUserGroupArray();

if(!defined('STOP_GROUP_ID_HS_227')) {
	define('STOP_GROUP_ID_HS_227', 43);
}

if(!in_array(STOP_GROUP_ID_HS_227, $group) && !$USER->IsAdmin()) {
	$asset = \Bitrix\Main\Page\Asset::getInstance();
	$asset->addJs($templateFolder . "/hs227/script.js?v=2", true);
	$asset->addCss($templateFolder . "/hs227/style.css?v=1", true);

	CJSCore::Init(array('jquery2'));

	$params = [
        'deal_stage_id' => 7,
		'lead_stage_id' => 2
	];
	
	$asset->addString('
	<script>
		BX.ready(function () {
			try{
				new BX.HS227('. CUtil::PhpToJSObject($params).');
			} catch(e){
				console.error("error BX.HS227: " + e);
			}
		});
	</script>');
}