<?
define( 'NO_KEEP_STATISTIC', true );
define( 'NOT_CHECK_PERMISSIONS',true );

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('crm');


$res = \Bitrix\Crm\CompanyTable::query()
->setSelect(['ID', 'TITLE',"UF_CRM_1697786836","UF_CRM_1697715758"])
->addFilter('!UF_CRM_1697786836', "NULL")
//->setLimit('10')
->exec();
$companies = $res->getSelectedRowsCount();
echo '<pre>'; print_r($companies); echo '</pre>';
die();
foreach ($companies as $key => $company) {
    //$money = (floatval(str_replace("|RUB","",$company["UF_CRM_1697786836"])) * 1000);
    $arFields = ["UF_CRM_1697715758" => floatval($company["UF_CRM_1697786836"])];
/*    echo '<pre>'; print_r($company); echo '</pre>';
    die();*/
    (new \CCrmCompany(false))->Update($company["ID"], $arFields, true, true, []);
}

?>