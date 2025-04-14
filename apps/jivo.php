
<?php
define("NO_KEEP_STATISTIC", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$fp = fopen($_SERVER["DOCUMENT_ROOT"].'/jivoQuery.log', 'a+');
fwrite($fp, file_get_contents('php://input').PHP_EOL);
fclose($fp); 

die();
?>