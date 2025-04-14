<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addJs($templateFolder . "/requisite_inn/script.js?v=1", true);
$asset->addCss($templateFolder . "/requisite_inn/style.css?v=1", true);
$group = $USER->GetUserGroupArray();

if(!defined('STOP_GROUP_ID')) {
	define('STOP_GROUP_ID', 26);
} 

if($arResult['ENTITY_DATA']['COMPANY_ID'] && !in_array(STOP_GROUP_ID, $group)) {
	CJSCore::Init(array('jquery2'));
	$params = [];
	$requisite = new \Bitrix\Crm\EntityRequisite();
	$rs = $requisite->getList(array(
		"filter" => array("ENTITY_ID" => $arResult['ENTITY_DATA']['COMPANY_ID'], "ENTITY_TYPE_ID" => CCrmOwnerType::Company)));
	if ($ar = $rs->Fetch()) {
		$params['RQ_INN'] = $ar['RQ_INN'];
	}

	$company = \Bitrix\Crm\CompanyTable::getList([
		'filter' => [
			'ID' => $arResult['ENTITY_DATA']['COMPANY_ID']
		],
		'select' => ['*', 'UF_*']
	])->fetch();

	$params['type'] = $company['COMPANY_TYPE'];
	$params['class'] = [
		'CUSTOMER' => 'customer',
		'SUPPLIER' => 'supplier',
		'COMPETITOR' => 'competitor',
		'PARTNER' => 'partner',
		'OTHER' => 'other'
	];

	$asset->addString('
	<script>
		BX.ready(function () {
			try{
				new BX.CompanyReq('. CUtil::PhpToJSObject($params) .');
			} catch(e){
				console.error("error CompanyReq: " + e);
			}
		});
	</script>');
}