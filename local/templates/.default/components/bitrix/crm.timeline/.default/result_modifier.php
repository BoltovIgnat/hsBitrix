<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$component = $this->__component;

$originalComponentFolder = "/bitrix/components/bitrix/crm.timeline/templates";
$originalComponentTemplateFolder = "/.default";
$originalComponentTemplateHtmlFile = "/template.php";
$originalComponentResultModifierFile = "/result_modifier.php";

$templateFolder = $this->GetFolder();

$file = $_SERVER["DOCUMENT_ROOT"] . $originalComponentFolder . $originalComponentTemplateFolder . $originalComponentTemplateHtmlFile;
$fileResultModifier = $_SERVER["DOCUMENT_ROOT"] . $originalComponentFolder . $originalComponentTemplateFolder . $originalComponentResultModifierFile;
$this->__folder = $originalComponentFolder . $originalComponentTemplateFolder;

$this->__hasCSS = true;
$this->__hasJS = true;

if (file_exists($fileResultModifier) ) {
    include $fileResultModifier; 
}

$asset = \Bitrix\Main\Page\Asset::getInstance();
$group = $USER->GetUserGroupArray();

if(!defined('ALLOW_MENU_TASK_GROUP_ID')) {
	define('ALLOW_MENU_TASK_GROUP_ID', 27);
}

if(!defined('ALLOW_MENU_ENTITY_GROUP_ID')) {
	define('ALLOW_MENU_ENTITY_GROUP_ID', 28);
}


if(!$USER->IsAdmin()) {

    $arResult["ENABLE_CALL"] = false;
   // $arResult["ENABLE_TODO"] = false; 
    $arResult["ENABLE_MEETING"] = false; 
    $arResult["ENABLE_VISIT"] = false; 
    $arResult["ENABLE_ZOOM"] = false; 
    $arResult["ENABLE_SMS"] = false; 
    $arResult["ENABLE_EMAIL"] = false; 
    //$arResult["ENABLE_REST"] = false; 
    $arResult["ENABLE_WAIT"] = false; 
    $arResult["ENABLE_DELIVERY"] = false;

    foreach ($arResult["ADDITIONAL_TABS"] as $k => $tab) {
        if ($tab['id'] == 'activity_rest_applist') {
            unset($arResult["ADDITIONAL_TABS"][$k]);
        }
    }
     
}

//prToFile($arResult);



if (file_exists($file) ) {
    include $file; 
}

