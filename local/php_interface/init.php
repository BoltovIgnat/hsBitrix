<?php
define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/ibclog.txt"); 
// настройки для разных полей на боевом и тестовом
include $_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/crm_setting.php';
//

include $_SERVER["DOCUMENT_ROOT"] . '/local/vendor/autoload.php';
include $_SERVER["DOCUMENT_ROOT"] . '/local/api/autoload.php';
include $_SERVER["DOCUMENT_ROOT"] . '/local/mailSync/mailSync.php';
include $_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/event/prolog.php';
include $_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/event/event.php';
include $_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/functions.php';
include $_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/functionsForBP.php';
include $_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/ForBP.php';
include $_SERVER["DOCUMENT_ROOT"] . '/local/api/Agent.php';
include $_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/custom_smart.php';
include $_SERVER["DOCUMENT_ROOT"] . '/local/classes/caseWhatsAppCaseUpdate.php';
include $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/helpers.php'; //Вспомогательные функции
include $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/lib/BxDataHelper.php'; //Вспомогательные функции
