<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$group = $USER->GetUserGroupArray();

if(!defined('ALLOW_MENU_TASK_GROUP_ID')) {
	define('ALLOW_MENU_TASK_GROUP_ID', 27);
}

if(!in_array(ALLOW_MENU_TASK_GROUP_ID, $group)) {
	CJSCore::Init(array('jquery2'));
	$asset->addJs($templateFolder . "/remove_menu_task/script.js?v=1", true);
	$asset->addCss($templateFolder . "/remove_menu_task/style.css?v=1", true);

	$asset->addString('
	<script>
		BX.ready(function () {
			try{
				new BX.RemoveMenuTask();
			} catch(e){
				console.error("error RemoveMenuTask: " + e);
			}
		});
	</script>');
}