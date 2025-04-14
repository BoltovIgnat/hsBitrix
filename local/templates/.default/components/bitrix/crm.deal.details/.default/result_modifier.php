<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$component = $this->__component;

$originalComponentFolder = "/bitrix/components/bitrix/crm.deal.details/templates";
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
include_once 'remove_menu_task/index.php';
include_once 'remove_menu_entity/index.php';
include_once 'stop_change_assigned/index.php';
include_once 'custom_contact_field/index.php';
include_once 'requisite_inn/index.php';
include_once 'hide_product_tab/index.php'; /*скрыть вкладку Товары*/
include_once 'reload_page/index.php';
include_once 'check_field_inn/index.php';
include_once 'control_require_fields/index.php';
//include_once 'hs225/index.php'; //HS-225 После изменения типа компании обновлять сделку
include_once 'remove_link_1c/index.php'; //HS-211 Нужно сделать так, чтобы ссылка в таймлайне не была активна/кликабельна
include_once 'hs221/index.php'; //HS-221 ЮР адрес ИП
include_once 'hs260/index.php'; //Нужно на сделках скрыть пользователя шестеренку
include_once 'hs231/index.php'; //Нужно на против компании добавить крести в форме лида и сделки для инициализации запуска БП
include_once 'remove_email/index.php';  // Убираем кнопку письмо
