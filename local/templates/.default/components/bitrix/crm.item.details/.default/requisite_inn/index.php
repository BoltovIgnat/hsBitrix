<?php
$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addJs($templateFolder . "/requisite_inn/script.js?v=1", true);
$asset->addCss($templateFolder . "/requisite_inn/style.css?v=1", true);
$group = $USER->GetUserGroupArray();

if(!defined('STOP_GROUP_ID')) {
    define('STOP_GROUP_ID', 26);
}
$arResult['ENTITY_DATA']['COMPANY_ID'] = $arResult["entityDetailsParams"]["EDITOR"]["ENTITY_DATA"]['COMPANY_ID'];

$query = \Bitrix\Crm\DealTable::query()
    ->setSelect(["ID","COMPANY_ID","STAGE_SEMANTIC_ID"])
    ->setFilter([ "COMPANY_ID" => $arResult['ENTITY_DATA']['COMPANY_ID'] ])
    ->exec();
$DealsCount = $query->getSelectedRowsCount();

$query = \Bitrix\Crm\DealTable::query()
    ->setSelect(["ID","COMPANY_ID","STAGE_SEMANTIC_ID"])
    ->setFilter([ "COMPANY_ID" => $arResult['ENTITY_DATA']['COMPANY_ID'], "STAGE_SEMANTIC_ID" => "S" ])
    ->exec();
$successDealsCount = $query->getSelectedRowsCount();

$query = \Bitrix\Crm\DealTable::query()
    ->setSelect(["ID","COMPANY_ID","CLOSED"])
    ->setFilter([ "COMPANY_ID" => $arResult['ENTITY_DATA']['COMPANY_ID'], "CLOSED" => "N" ])
    ->exec();
$activeDealsCount = $query->getSelectedRowsCount();

$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(174);
$res = $factory->getItems([
    'filter' => ['COMPANY_ID' => $arResult['ENTITY_DATA']['COMPANY_ID']],
    'select' => ['ID', 'COMPANY_ID'],
]);
$projectsAll = count($res);

$res = $factory->getItems([
    'filter' => ['COMPANY_ID' => $arResult['ENTITY_DATA']['COMPANY_ID'],"OPENED" => "Y"],
    'select' => ['ID', 'COMPANY_ID'],
]);
$projectsActive = count($res);

$res = $factory->getItems([
    'filter' => ['COMPANY_ID' => $arResult['ENTITY_DATA']['COMPANY_ID'],"STAGE_ID" => "DT174_10:SUCCESS"],
    'select' => ['ID', 'COMPANY_ID'],
]);
$projectsSuccess = count($res);

if($arResult['ENTITY_DATA']['COMPANY_ID'] && !in_array(STOP_GROUP_ID, $group)) {
    CJSCore::Init(array('jquery2'));
    $params = [
        'fields' => [
            'revenue' => CRM_SETTINGS['company']['revenue'],
            //'profit' => CRM_SETTINGS['company']['profit'],
            //'reliability' => CRM_SETTINGS['company']['reliability'],
            //'defend' => CRM_SETTINGS['company']['defend'],
//            'plus' => CRM_SETTINGS['company']['plus'],
//            'minus' => CRM_SETTINGS['company']['minus'],
            'link' => CRM_SETTINGS['company']['link'],
            //'complain' => CRM_SETTINGS['company']['complain'],
            //'tender' => CRM_SETTINGS['company']['tender'],
            'age' => CRM_SETTINGS['company']['age'],
            'owners' => CRM_SETTINGS['company']['owners'],
            'linked' => CRM_SETTINGS['company']['linked'],
//            'linkedAll' => CRM_SETTINGS['company']['linkedAll'],
            'count_staff' => CRM_SETTINGS['company']['count_staff'],
            //'address' => CRM_SETTINGS['company']['address'],
            //'phone' => CRM_SETTINGS['company']['phone'],
            //'email' => CRM_SETTINGS['company']['email'],
            //'inn' => CRM_SETTINGS['company']['inn'],
            //'kpp' => CRM_SETTINGS['company']['kpp'],
            //'director' => CRM_SETTINGS['company']['director'],
            'capital' => CRM_SETTINGS['company']['capital']
        ]
    ];
    $requisite = new \Bitrix\Crm\EntityRequisite();
    $rs = $requisite->getList(array(
        "filter" => array("ENTITY_ID" => $arResult['ENTITY_DATA']['COMPANY_ID'], "ENTITY_TYPE_ID" => CCrmOwnerType::Company)));
    if ($ar = $rs->Fetch()) {
        $params['RQ_INN'] = $ar['RQ_INN'];
    }

    $arFieldsCompany = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("CRM_COMPANY");
    foreach($arFieldsCompany as $fieldCompany) {
        if($fieldCompany['FIELD_NAME'] == $params['fields']['defend']) {
            $obEnum = new \CUserFieldEnum;
            $rsEnum = $obEnum->GetList(array(), array('USER_FIELD_ID' => $fieldCompany['ID']));
            while($arEnum = $rsEnum->Fetch()) {
                $enum['defend'][$arEnum['ID']] = $arEnum['VALUE'];
            }
        }
        if($fieldCompany['FIELD_NAME'] == $params['fields']['complain']) {
            $obEnum = new \CUserFieldEnum;
            $rsEnum = $obEnum->GetList(array(), array('USER_FIELD_ID' => $fieldCompany['ID']));
            while($arEnum = $rsEnum->Fetch()) {
                $enum['complain'][$arEnum['ID']] = $arEnum['VALUE'];
            }
        }
        if($fieldCompany['FIELD_NAME'] == $params['fields']['tender']) {
            $obEnum = new \CUserFieldEnum;
            $rsEnum = $obEnum->GetList(array(), array('USER_FIELD_ID' => $fieldCompany['ID']));
            while($arEnum = $rsEnum->Fetch()) {
                $enum['tender'][$arEnum['ID']] = $arEnum['VALUE'];
            }
        }
    }

    $company = \Bitrix\Crm\CompanyTable::getList([
        'filter' => [
            'ID' => $arResult['ENTITY_DATA']['COMPANY_ID']
        ],
        'select' => ['*', 'UF_*']
    ])->fetch();

    foreach($params['fields'] as $key => $code) {
        if($key == 'defend') {
            $params[$key] = $enum[$key][$company[$code]];
        } elseif($key == 'complain') {
            $params[$key] = $enum[$key][$company[$code]];
        } elseif($key == 'tender') {
            $params[$key] = $enum[$key][$company[$code]];
        } else {
            $params[$key] = $company[$code];
        }
    }

    $params['projectsAll'] = $projectsAll;
    $params['projectsActive'] = $projectsActive;
    $params['projectsSuccess'] = $projectsSuccess;

    $params['successDealsCount'] = $successDealsCount;
    $params['activeDealsCount'] = $activeDealsCount;
    $params['DealsCount'] = $DealsCount;

    $params['linkedAll'] = $company[CRM_SETTINGS['company']['linkedAll']];
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