<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$group = $USER->GetUserGroupArray();

if(!defined('ALLOW_MENU_ENTITY_GROUP_ID')) {
	define('ALLOW_MENU_ENTITY_GROUP_ID', 28);
}

if(!in_array(ALLOW_MENU_ENTITY_GROUP_ID, $group) && !$USER->IsAdmin()) {
	CJSCore::Init(array('jquery2'));
	$asset->addJs($templateFolder . "/remove_menu_entity/script.js?v=2", true);
	$asset->addCss($templateFolder . "/remove_menu_entity/style.css?v=2", true);

	$asset->addString('
	<script>
		BX.ready(function () {
			try{
				new BX.RemoveMenuEntity();
                console.log("RemoveMenuEntity");
			} catch(e){
				console.error("error RemoveMenuEntity: " + e);
			}
		});
	</script>');
}