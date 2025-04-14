<?php
die();
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", 'Y');
define("NO_AGENT_STATISTIC", 'Y');
define("NO_AGENT_CHECK", true);
define("DisableEventsCheck", true);

include $_SERVER["DOCUMENT_ROOT"] . '/local/vendor/autoload.php';
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

\Hs\FixCompanies\CrmFacade::run(true);
