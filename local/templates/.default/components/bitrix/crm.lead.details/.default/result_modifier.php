<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$component = $this->__component;

$originalComponentFolder = "/bitrix/components/bitrix/crm.lead.details/templates";
$originalComponentTemplateFolder = "/.default";
$originalComponentTemplateHtmlFile = "/template.php";

$templateFolder = $this->GetFolder();

$file = $_SERVER["DOCUMENT_ROOT"] . $originalComponentFolder . $originalComponentTemplateFolder . $originalComponentTemplateHtmlFile;
$this->__folder = $originalComponentFolder . $originalComponentTemplateFolder;

$this->__hasCSS = true;
$this->__hasJS = true;

include_once 'hs221/remove.php'; // Очистка 0 в юр.адресе ИП

if (file_exists($file) ) {
    include $file; 
}

include_once 'remove_fields/index.php';

//include_once 'remove_menu_entity/index.php'; //Можно удалить данную доработку, вся логика в 'remove_menu_task/index.php'
include_once 'stop_change_assigned/index.php';
include_once 'requisite_inn/index.php';
include_once 'hs231/index.php'; //Нужно на против компании добавить крести в форме лида и сделки для инициализации запуска БП
include_once 'control_require_fields/index.php'; // Обработка обязательных полей при завершении
include_once 'remove_email/index.php'; // Убираем кнопку письмо
include_once 'reload_page/index.php'; // Перезагрузка страницы в финальных стадиях
include_once 'hs221/index.php'; //HS-221 ЮР адрес ИП
include_once 'check_field_inn/index.php';

//Добавлено 2025.01.21
include_once 'hs_modifi_case_view/index.php'; //Скрывает дела не используемы в лидах с помощью CSS




//Удалить доработки в будущем
//include_once 'remove_menu_task/index.php'; //Убираем пункты меню в делах