<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$date = new \Bitrix\Main\Type\DateTime();

$query = Bitrix\Crm\DealTable::query()
->setSelect(["ID","TITLE","DATE_MODIFY"])
//->setFilter(["UF_CRM_1699861771" => "N", "UF_CRM_1699861771" => false])
->setFilter([">=DATE_MODIFY" =>"15.08.2023","<=DATE_MODIFY" =>"07.11.2023", "UF_CRM_1699861771" => "N", "UF_CRM_1699861771" => false])
->whereIn("STAGE_ID",["WON","LOSE","3","4","5"])
->setOrder(["DATE_MODIFY" => "ASC"])
->setLimit(100)
->exec();
$elements = $query->fetchAll(); 

foreach ($elements as $element) { 
    $element["DATE_MODIFY"] = $element["DATE_MODIFY"]->format("Y-m-d\TH:i:s");
    $ids[] = $element;
}

+Kint::Dump($ids);

if ($_REQUEST["PROCESS"] == "Y") {
    foreach ($ids as $id) {
        $wfId = CBPDocument::StartWorkflow(
            374,
             [ "crm", "CCrmDocumentDeal", "DEAL_".$id["ID"] ],
             [ "TargetUser" => "user_128" ],
             $arErrorsTmp
         );   
    }
    LocalRedirect('/toolz/startBp374.php');
}
?>
<a href="?PROCESS=Y">Все ок запустить БП ID=374</a>



