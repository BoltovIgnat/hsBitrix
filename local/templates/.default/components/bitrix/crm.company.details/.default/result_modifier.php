<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$component = $this->__component;

$originalComponentFolder = "/bitrix/components/bitrix/crm.company.details/templates";
$originalComponentTemplateFolder = "/.default";
$originalComponentTemplateHtmlFile = "/template.php";

$templateFolder = $this->GetFolder();

$file = $_SERVER["DOCUMENT_ROOT"] . $originalComponentFolder . $originalComponentTemplateFolder . $originalComponentTemplateHtmlFile;
$this->__folder = $originalComponentFolder . $originalComponentTemplateFolder;

$this->__hasCSS = true;
$this->__hasJS = true;


foreach ($arResult["TABS"] as $key => &$tab) {
    if ($tab["name"] == "Проекты" || $tab["name"] == "Регистрация проекта" || $tab["name"] == "Тесты" || $tab["name"] == "Support") {
        $uriObject = $tab["loader"]["serviceUrl"];
        $uriObject->setPath("/local/components/bitrix/crm.item.listnperm/lazyload.ajax.php");
        
    }
}

foreach ($arResult["TABS"] as $key => $tab) {
    if ($tab["name"] == "Холдинги") {
        unset($arResult["TABS"][$key]);
    }
}

$companyId = $arResult["ENTITY_ID"];

if (!empty($companyId)) {
    array_push($arResult['TABS'],
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
}


if (file_exists($file) ) {
    include $file; 
}

include_once 'remove_menu_task/index.php';

include_once 'remove_menu_entity/index.php';

include_once 'stop_change_assigned/index.php';

if (CSite::InGroup(CRM_SETTINGS["perms"]["grantfiredcontactsgroup"])) {
    include_once 'fired_button/index.php';
}
?>
<?if ($_REQUEST["tab_onecDocsTab"] == "Y"):?>
    <script>
        setTimeout(() => {
            BX.onCustomEvent('company_'+<?=$companyId;?>+'_details_click_tab_docs_1c');
        }, 500);
       
    </script>
<?endif;?>
    