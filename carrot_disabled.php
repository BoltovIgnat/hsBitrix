<?php

use Dbbo\Chat\CarrotQuestConnector;
use Dbbo\Chat\ChatCrm;
use Dbbo\Chat\ChatFacade;

define("STOP_STATISTICS", true);
define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

include 'local/api/dbbo.chats/include.php';

$carrot = new ChatFacade(new CarrotQuestConnector(), new ChatCrm());
$carrot->doAction();
