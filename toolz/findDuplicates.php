<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('iblock');
$res = CIBlockElement::GetList(
	['ID' => 'ASC'],
	['IBLOCK_ID' => 49],
	false,
	false,
	['ID', 'PROPERTY_SDELKA', 'NAME']
);
while ($row = $res->Fetch())
{
    $rows[$row["PROPERTY_SDELKA_VALUE"]][] = $row["NAME"];
}

foreach ($rows as $row) {
    if ( count($row) > 1) {
        echo '<pre>'; print_r($row); echo '</pre>';
    }
}


?>