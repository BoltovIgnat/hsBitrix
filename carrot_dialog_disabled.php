<?php

use Dbbo\Chat\CarrotQuestConnector;
use Dbbo\Chat\ChatCrm;
use Dbbo\Chat\ChatFacade;

// Выполняем только при запуске из консоли
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) !== 'cli') {
    die();
}

define("STOP_STATISTICS", true);
define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

$_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

include ($_SERVER["DOCUMENT_ROOT"] . '/local/api/dbbo.chats/include.php');

$carrot = new ChatFacade(new CarrotQuestConnector(), new ChatCrm());
$carrot->getDialogs();
