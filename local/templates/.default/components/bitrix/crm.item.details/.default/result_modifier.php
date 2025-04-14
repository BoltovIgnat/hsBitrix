<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$component = $this->__component;

$originalComponentFolder = "/bitrix/components/bitrix/crm.item.details/templates";
$originalComponentTemplateFolder = "/.default";
$originalComponentTemplateHtmlFile = "/template.php";

$templateFolder = $this->GetFolder();

$file = $_SERVER["DOCUMENT_ROOT"] . $originalComponentFolder . $originalComponentTemplateFolder . $originalComponentTemplateHtmlFile;
$this->__folder = $originalComponentFolder . $originalComponentTemplateFolder;

$this->__hasCSS = true;
$this->__hasJS = true;

if (CSite::InDir('/crm/type/'.CRM_SMART["reanimation"].'/') && $arResult['entityDetailsParams']['TABS']) {
    $companyId = $arResult["entityDetailsParams"]["EDITOR"]["ENTITY_DATA"]['COMPANY_ID'];
    if (empty($companyId) && CSite::InGroup(CRM_SETTINGS["perms"]["openDocs1sReanimation"])) {
        $companyId = $arResult["entityDetailsParams"]["EDITOR"]["ENTITY_DATA"][CRM_SETTINGS["reanimation"]["companyID"]]["VALUE"];
    }
    if (!empty($companyId)) {
        array_push($arResult['entityDetailsParams']['TABS'],
            [
                'id' => 'tab_docs_1c',
                'name' => "Закупки по 1с",
                'loader' => [
                    'serviceUrl' =>
                        '/ajax/Company/getCustomDocsForDeal/?'.bitrix_sessid_get(),
                    'componentData' => [
                        'template' => ["companyID" => $companyId],
                        'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
                        ],'crm.item.listnperm')
                    ]
                ]
            ]
        );
    
        array_push($arResult['entityDetailsParams']['TABS'],
            [
                'id' => 'tab_deal',
                'name' => "Сделки",
                'loader' => [
                    'serviceUrl' => '/local/components/bitrix/crm.deal.listnperm/lazyload.ajax.php?&site'
                        . SITE_ID
                        . '&'
                        . bitrix_sessid_get(),
                    'componentData' => [
                        'template' => '',
                        'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
                            'DEAL_COUNT' => '20',
                            'PATH_TO_DEAL_SHOW' => "",
                            'PATH_TO_DEAL_EDIT' => "",
                            'INTERNAL_FILTER' => ['COMPANY_ID' => $companyId],
                            'INTERNAL_CONTEXT' => ['COMPANY_ID' => $companyId],
                            'GRID_ID_SUFFIX' => 'COMPANY_DETAILS',
                            'TAB_ID' => 'tab_deal',
                            'NAME_TEMPLATE' => "",
                            'ENABLE_TOOLBAR' => true,
                            'PRESERVE_HISTORY' => true,
                            'ADD_EVENT_NAME' => 'CrmCreateDealFromCompany'
                        ], 'crm.deal.listnperm')
                    ]
                ]
            ]
        );
    
        array_push($arResult['entityDetailsParams']['TABS'],
            [
                'id' => 'tab_relation_dynamic_174',
                'name' => "Проекты",
                'loader' => [
                    'serviceUrl' =>
                    '/local/components/bitrix/crm.item.listnperm/lazyload.ajax.php?entityTypeId=174&parentEntityTypeId=4&parentEntityId='.$companyId.'&'.bitrix_sessid_get().'&site='.SITE_ID,
                    'componentData' => [
                        'template' => '',
                        'signedParameters' => \CCrmInstantEditorHelper::signComponentParams(['123'],'crm.item.listnperm')
                    ]
                ]
            ]
        );
    }
/*    $relationManager = \Bitrix\Crm\Service\Container::getInstance()->getRelationManager();
    $relations = $relationManager->getRelationTabsForDynamicChildren(
        \CCrmOwnerType::Company,
        $arResult["entityDetailsParams"]["EDITOR"]["ENTITY_DATA"]['COMPANY_ID'],
        ($arResult["entityDetailsParams"]["EDITOR"]["ENTITY_DATA"]['COMPANY_ID'] === 0)
    );
    foreach ($relations as $relation) {
        if ($relation["name"] == "Проекты") {
            $addRelation = $relation;
        }
    }*/

}

if (CSite::InDir('/crm/type/'.CRM_SMART["holding"].'/') && $arResult['entityDetailsParams']['TABS']) {
    array_push($arResult['entityDetailsParams']['TABS'],
        [
            'id' => 'tab_relation_contactnperm',
            'name' => "Контакты",
            'loader' => [
                'serviceUrl' =>
                '/local/components/bitrix/crm.contact.listnperm/lazyload.ajax.php?entityTypeId=3&parentEntityTypeId='.CRM_SMART["holding"].'&parentEntityId='.$arResult["entityDetailsParams"]["ENTITY_ID"].'&'.bitrix_sessid_get().'&site='.SITE_ID,
                'componentData' => [
                    'template' => '',
                    'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
                        "ENABLE_TOOLBAR" => 0,
                        "PRESERVE_HISTORY" => 1,
                        "PARENT_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                        "PARENT_ENTITY_ID" => $arResult["entityDetailsParams"]["ENTITY_ID"],
                        "INTERNAL_FILTER" => [ "PARENT_ID_".CRM_SMART["holding"] => $arResult["entityDetailsParams"]["ENTITY_ID"] ]
                    ],'crm.contact.listnperm')
                ]
            ]
        ]
    );
    $_SESSION["holdingID"] = $arResult["entityDetailsParams"]["ENTITY_ID"];
    array_push($arResult['entityDetailsParams']['TABS'],
        [
            'id' => 'tab_relation_companynperm',
            'name' => "Компании",
            'loader' => [
                'serviceUrl' =>
                '/local/components/bitrix/crm.company.listnperm/lazyload.ajax.php?entityTypeId=4&parentEntityTypeId='.CRM_SMART["holding"].'&parentEntityId='.$arResult["entityDetailsParams"]["ENTITY_ID"].'&'.bitrix_sessid_get().'&site='.SITE_ID,
                'componentData' => [
                    'template' => '',
                    'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
                        "COMPANY_ID" => $arResult["entityDetailsParams"]["EDITOR"]["ENTITY_DATA"]['COMPANY_ID'],
                        "ENABLE_TOOLBAR" => 0,
                        "PRESERVE_HISTORY" => 1,
                        "PARENT_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                        "PARENT_ENTITY_ID" => $arResult["entityDetailsParams"]["ENTITY_ID"],
                        "INTERNAL_FILTER" => [ "PARENT_ID_".CRM_SMART["holding"] => $arResult["entityDetailsParams"]["ENTITY_ID"] ]
                    ],'crm.company.listnperm')
                ]
            ]
        ]
    );
    array_push($arResult['entityDetailsParams']['TABS'],
        [
            'id' => 'tab_deal',
            'name' => "Сделки",
            'loader' => [
                'serviceUrl' => '/local/components/bitrix/crm.deal.listnperm/lazyload.ajax.php?&site'
                    . SITE_ID
                    . '&'
                    . bitrix_sessid_get(),
                'componentData' => [
                    'template' => '',
                    'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
                        'DEAL_COUNT' => '20',
                        "GRID_ID_SUFFIX" => "PARENT_DYNAMIC_187_DETAILS",
                        "TAB_ID" => "tab_relation_deal",
                        "ENABLE_TOOLBAR" => (CSite::InGroup([CRM_SETTINGS["perms"]["enableToolbarUserGroup"]])) ?? 1,
                        "PRESERVE_HISTORY" => 1,
                        "PARENT_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                        "PARENT_ENTITY_ID" => $arResult["entityDetailsParams"]["ENTITY_ID"],
                        "INTERNAL_FILTER" => [ "PARENT_ID_".CRM_SMART["holding"] => $arResult["entityDetailsParams"]["ENTITY_ID"] ]
                    ], 'crm.deal.listnperm')
                ]
            ]
        ]
    );
    array_push($arResult['entityDetailsParams']['TABS'],
        [
            'id' => 'tab_relation_dynamic_174_nperm',
            'name' => "Проекты",
            'loader' => [
                'serviceUrl' =>
                    '/local/components/bitrix/crm.item.listnperm/lazyload.ajax.php?entityTypeId=174&parentEntityTypeId='.CRM_SMART["holding"].'&parentEntityId='.$arResult["entityDetailsParams"]["ENTITY_ID"].'&'.bitrix_sessid_get().'&site='.SITE_ID,
                'componentData' => [
                    'template' => '',
                    'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
                        "ENABLE_TOOLBAR" => 0,
                        "PRESERVE_HISTORY" => 1,
                        "PARENT_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                        "PARENT_ENTITY_ID" => $arResult["entityDetailsParams"]["ENTITY_ID"],
                        "INTERNAL_FILTER" => [ "PARENT_ID_".CRM_SMART["holding"] => $arResult["entityDetailsParams"]["ENTITY_ID"] ]
                    ],'crm.item.listnperm')
                ]
            ]
        ]
    );

    $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
    ->addSelect("DST_ENTITY_ID")
    ->setFilter([
        "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
        "SRC_ENTITY_ID" => $arResult["entityDetailsParams"]["ENTITY_ID"],
        "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
    ])
    ->exec();
    $exist = $query->fetchAll();
    foreach ($exist as $ex) {
        $cmpids[] = $ex["DST_ENTITY_ID"];
    }
    if (is_array($cmpids)) {
        $cmpidimplode = implode(",",$cmpids);
    }

    array_push($arResult['entityDetailsParams']['TABS'],
        [
            'id' => 'tab_docs_1c',
            'name' => "Закупки клиентов",
            'loader' => [
                'serviceUrl' =>
                    '/ajax/Company/getCustomDocsForDeal/?'.bitrix_sessid_get(),
                'componentData' => [
                    'template' => ["companyID" => $cmpidimplode],
                    'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
                    ],'crm.item.listnperm')
                ]
            ]
        ]
    );

    array_push($arResult['entityDetailsParams']['TABS'],
    [
        'id' => 'tab_relation_dynamic_161_nperm',
        'name' => "Регистрация проекта",
        'loader' => [
            'serviceUrl' =>
                '/local/components/bitrix/crm.item.listnperm/lazyload.ajax.php?entityTypeId=161&parentEntityTypeId='.CRM_SMART["holding"].'&parentEntityId='.$arResult["entityDetailsParams"]["ENTITY_ID"].'&'.bitrix_sessid_get().'&site='.SITE_ID,
            'componentData' => [
                'template' => ["companyID" => $cmpidimplode],
                'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
                    "ENABLE_TOOLBAR" => 0,
                    "PRESERVE_HISTORY" => 1,
                    "PARENT_ENTITY_TYPE_ID" => "",
                    "PARENT_ENTITY_ID" => "",
                    "INTERNAL_FILTER" => [ "COMPANY_ID"=> $companyId ]
                ],'crm.item.listnperm')
            ]
        ]
    ]
);
    
/*     $_SESSION["holdingID"] = $arResult["entityDetailsParams"]["ENTITY_ID"];
    array_push($arResult['entityDetailsParams']['TABS'],
        [
            'id' => 'link_company_holding',
            'name' => "Привязать компанию",
            'loader' => [
                'serviceUrl' =>
                '/local/components/highsystem/companytoholding/lazyload.ajax.php?'.bitrix_sessid_get(),
                'componentData' => [
                    'template' => [],
                ]
            ]
        ]
    ); */
    
}
else {
   unset($_SESSION["holdingID"]); 
}

if (file_exists($file) ) {
    include $file; 
}

include_once 'requisite_inn/index.php';
include_once 'reload_page/index.php';

if (CSite::InDir('/page/proekty/proekty/type/174/')) {
    include_once 'ctoreg_button/index.php';
}
?>

<?if ($arResult['entityDetailsParams']["EDITOR"]["ENTITY_DATA"]["STAGE_ID"] == CRM_STATUS["reanimation"]["towork"] && !CSite::InGroup(CRM_SETTINGS["perms"]["openDocs1sReanimation"])):?>
    <script>
        $('.crm-entity-stream-container-list').hide();
    </script>
<?endif;?>
