<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$component = $this->__component;

$originalComponentFolder = "/bitrix/components/bitrix/crm.contact.details/templates";
$originalComponentTemplateFolder = "/.default";
$originalComponentTemplateHtmlFile = "/template.php";

$templateFolder = $this->GetFolder();

$file = $_SERVER["DOCUMENT_ROOT"] . $originalComponentFolder . $originalComponentTemplateFolder . $originalComponentTemplateHtmlFile;
$this->__folder = $originalComponentFolder . $originalComponentTemplateFolder;

$this->__hasCSS = true;
$this->__hasJS = true;

if (file_exists($file) ) {
    include $file; 
}

include_once 'remove_menu_task/index.php';

include_once 'remove_menu_entity/index.php';

include_once 'stop_change_assigned/index.php';

include_once 'phone_info/index.php';
