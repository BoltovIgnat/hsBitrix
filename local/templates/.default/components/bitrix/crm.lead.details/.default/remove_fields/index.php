<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$group = $USER->GetUserGroupArray();

if(!defined('STOP_GROUP_ID')) {
	define('STOP_GROUP_ID', 26);
}

if(in_array(STOP_GROUP_ID, $group)) {
	$asset->addJs($templateFolder . "/remove_fields/script.js?v=1", true);
	$asset->addCss($templateFolder . "/remove_fields/style.css?v=1", true);
	CJSCore::Init(array('jquery2'));
	$params = [
		'FIELDS' => [
			'PHONE',
			'EMAIL',
			'COMMENTS',
			 'UF_CRM_1639485462275', // не найдено
			'UF_CRM_61B8907E1C0A5', // не найдено
			CRM_SETTINGS['lead']['addressDelivery'],
            'UF_CRM_1667992925971', // не найдено
            'UF_CRM_1640606874923', // не найдено
            CRM_SETTINGS['lead']['brand'],
            CRM_SETTINGS['deal']['brand'], // ??
			'UF_CRM_1668161666507', // не найдено
			'UF_CRM_6372B026EB9D6', // не найдено
			'UF_CRM_636E20E73FB88', // не найдено
            CRM_SETTINGS['lead']['companyInn'],
            CRM_SETTINGS['lead']['companyKpp'],
			'SOURCE_ID',
			'UF_CRM_1671042440' // не найдено
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