<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$group = $USER->GetUserGroupArray();

if(!defined('ALLOW_CHANGE_ASSIGNED_GROUP_ID')) {
	define('ALLOW_CHANGE_ASSIGNED_GROUP_ID', 29);
}

if(!in_array(ALLOW_CHANGE_ASSIGNED_GROUP_ID, $group) && !$USER->IsAdmin()) {
	CJSCore::Init(array('jquery2'));
	$asset->addJs($templateFolder . "/stop_change_assigned/script.js?v=1", true);
	$asset->addCss($templateFolder . "/stop_change_assigned/style.css?v=1", true);

	$asset->addString('
	<script>
		BX.ready(function () {
			try{
				new BX.StopChangeAssigned();
			} catch(e){
				console.error("error StopChangeAssigned: " + e);
			}
		});
	</script>');
}