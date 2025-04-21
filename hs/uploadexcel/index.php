<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/local/vendor/autoload.php');

use Bitrix\Main\Context;
\Bitrix\Main\UI\Extension::load("ui.forms");
/*
echo '<pre>';
print_r();
echo '</pre>';
*/
$APPLICATION->includeComponent(
    'highsystem:uploadexcel',
    '',
    [
        ///'COMPETITOR_ID' => $_REQUEST['BY'] ?? '',
        'COMPETITOR_ID' => $_POST['COMPETITOR_ID'],
    ]
);
?>



