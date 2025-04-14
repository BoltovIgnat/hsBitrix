<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$component = $this->__component;

$originalComponentFolder = "/bitrix/components/bitrix/crm.lead.list/templates";
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

if (file_exists($file) ) {
    include $file; 
}

include_once 'remove_fields/index.php';