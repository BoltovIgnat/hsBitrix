<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$group = $USER->GetUserGroupArray();

if(!defined('STOP_GROUP_ID')) {
	define('STOP_GROUP_ID', 26);
}

if(in_array(STOP_GROUP_ID, $group)) {
	CJSCore::Init(array('jquery2'));
	$asset->addJs($templateFolder . "/remove_fields/script.js?v=2", true);
	$asset->addCss($templateFolder . "/remove_fields/style.css?v=1", true);
	$params = [
		'FIELDS' => [
			'PHONE',
			'COMMENTS',
			'UF_CRM_61B8907E1C0A5', // не найдено
            CRM_SETTINGS['deal']['addressDelivery'],
            CRM_SETTINGS['deal']['brand'],
            CRM_SETTINGS['deal']['companyInn'],
			'UF_CRM_636E20E73FB88', // не найдено
			'UF_CRM_6372B026EB9D6', // не найдено
			'EMAIL',
            CRM_SETTINGS['deal']['companyKpp'],
			'OBSERVER',
			'UF_CRM_1581416705', // не найдено
			'UF_CRM_1648417458138', // не найдено
			'UF_CRM_1639645454', // не найдено
			'UF_CRM_1667466360305', // не найдено
			'UF_CRM_1667466377056' // не найдено
		]
	];

	$asset->addString('
	<script>
		BX.ready(function () {
			try{
				new BX.RemoveFields('. CUtil::PhpToJSObject($params) .');
			} catch(e){
				console.error("error RemoveFields: " + e);
			}
		});
	</script>');
}