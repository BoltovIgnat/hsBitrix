<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");


//$date = new \Bitrix\Main\Type\DateTime();

$query = Bitrix\Crm\DealTable::query()
->setSelect(["ID","TITLE","STAGE_ID"])
->setFilter(["STAGE_ID" => "NEW", "CATEGORY_ID" => 0])
/* ->addFilter("UF_CRM_1677608011",2302)
->whereIn("STAGE_ID",["WON","LOSE","3","4","5"]) */
->exec();
$elements = $query->fetchAll(); 

foreach ($elements as $element) { 
    $ids[] = $element;
}

+Kint::Dump($ids);

if ($_REQUEST["PROCESS1"] == "Y") {
    foreach ($ids as $id) {
        $wfId = CBPDocument::StartWorkflow(
            406,
             [ "crm", "CCrmDocumentDeal", "DEAL_".$id["ID"] ],
             [ "Launch_status" => "Взять в работу - Менеджер" ],
             $arErrorsTmp
         );    
    }
}

$query = Bitrix\Crm\DealTable::query()
->setSelect(["ID","TITLE","STAGE_ID"])
->setFilter(["STAGE_ID" => "PREPARATION", "CATEGORY_ID" => 0])
/* ->addFilter("UF_CRM_1677608011",2302)
->whereIn("STAGE_ID",["WON","LOSE","3","4","5"]) */
->exec();
$elements = $query->fetchAll(); 

foreach ($elements as $element) { 
    $ids2[] = $element;
}

+Kint::Dump($ids2);

if ($_REQUEST["PROCESS2"] == "Y") {
    foreach ($ids2 as $id) {
        $wfId = CBPDocument::StartWorkflow(
            406,
             [ "crm", "CCrmDocumentDeal", "DEAL_".$id["ID"] ],
             [ "Launch_status" => "Подбор оборудованния - Менеджер" ],
             $arErrorsTmp
         );    
    }
}

$query = Bitrix\Crm\DealTable::query()
->setSelect(["ID","TITLE","STAGE_ID"])
->setFilter(["STAGE_ID" => "1", "CATEGORY_ID" => 0])
/* ->addFilter("UF_CRM_1677608011",2302)
->whereIn("STAGE_ID",["WON","LOSE","3","4","5"]) */
->exec();
$elements = $query->fetchAll(); 

foreach ($elements as $element) { 
    $ids3[] = $element;
}

+Kint::Dump($ids3);

if ($_REQUEST["PROCESS3"] == "Y") {
    foreach ($ids3 as $id) {
        $wfId = CBPDocument::StartWorkflow(
            406,
             [ "crm", "CCrmDocumentDeal", "DEAL_".$id["ID"] ],
             [ "Launch_status" => "Согласовать с клиентом - Менеджер" ],
             $arErrorsTmp
         );    
    }
}


?>

<a href="?PROCESS1=Y">1. Запустить Взять в работу - Менеджер</a><br>
<a href="?PROCESS2=Y">2. Запустить Подбор оборудованния - Менеджер</a><br>
<a href="?PROCESS3=Y">3. Запустить Согласовать с клиентом - Менеджер</a><br>


<?

$query = Bitrix\Crm\DealTable::query()
->setSelect(["ID","TITLE","STAGE_ID"])
->setFilter(["STAGE_ID" => "PREPAYMENT_INVOICE", "CATEGORY_ID" => 0])
/* ->addFilter("UF_CRM_1677608011",2302)
->whereIn("STAGE_ID",["WON","LOSE","3","4","5"]) */
->exec();
$elements = $query->fetchAll(); 

foreach ($elements as $element) { 
    $ids4[] = $element;
}

+Kint::Dump($ids4);

if ($_REQUEST["PROCESS4"] == "Y") {
    foreach ($ids4 as $id) {
        $wfId = CBPDocument::StartWorkflow(
            406,
             [ "crm", "CCrmDocumentDeal", "DEAL_".$id["ID"] ],
             [ "Launch_status" => "Выставить счет - Менеджер" ],
             $arErrorsTmp
         );    
    }
}

$query = Bitrix\Crm\DealTable::query()
->setSelect(["ID","TITLE","STAGE_ID"])
->setFilter(["STAGE_ID" => "UC_W97ONA", "CATEGORY_ID" => 0])
/* ->addFilter("UF_CRM_1677608011",2302)
->whereIn("STAGE_ID",["WON","LOSE","3","4","5"]) */
->exec();
$elements = $query->fetchAll(); 

foreach ($elements as $element) { 
    $ids5[] = $element;
}

+Kint::Dump($ids5);

if ($_REQUEST["PROCESS5"] == "Y") {
    foreach ($ids5 as $id) {
        $wfId = CBPDocument::StartWorkflow(
            406,
             [ "crm", "CCrmDocumentDeal", "DEAL_".$id["ID"] ],
             [ "Launch_status" => "Дожим в оплату - Менеджер" ],
             $arErrorsTmp
         );    
    }
}

$query = Bitrix\Crm\DealTable::query()
->setSelect(["ID","TITLE","STAGE_ID"])
->setFilter(["STAGE_ID" => "UC_AMPHT1", "CATEGORY_ID" => 0])
/* ->addFilter("UF_CRM_1677608011",2302)
->whereIn("STAGE_ID",["WON","LOSE","3","4","5"]) */
->exec();
$elements = $query->fetchAll(); 

foreach ($elements as $element) { 
    $ids6[] = $element;
}

+Kint::Dump($ids6);

if ($_REQUEST["PROCESS6"] == "Y") {
    foreach ($ids6 as $id) {
        $wfId = CBPDocument::StartWorkflow(
            406,
             [ "crm", "CCrmDocumentDeal", "DEAL_".$id["ID"] ],
             [ "Launch_status" => "Контроль доставки - Менеджер" ],
             $arErrorsTmp
         );    
    }
}

$query = Bitrix\Crm\DealTable::query()
->setSelect(["ID","TITLE","STAGE_ID"])
->setFilter(["STAGE_ID" => "9", "CATEGORY_ID" => 0])
/* ->addFilter("UF_CRM_1677608011",2302)
->whereIn("STAGE_ID",["WON","LOSE","3","4","5"]) */
->exec();
$elements = $query->fetchAll(); 

foreach ($elements as $element) { 
    $ids7[] = $element;
}

+Kint::Dump($ids7);

if ($_REQUEST["PROCESS7"] == "Y") {
    foreach ($ids7 as $id) {
        $wfId = CBPDocument::StartWorkflow(
            406,
             [ "crm", "CCrmDocumentDeal", "DEAL_".$id["ID"] ],
             [ "Launch_status" => "Контроль ожидания - 1" ],
             $arErrorsTmp
         );    
    }
}

?>
<a href="?PROCESS4=Y">4. Запустить Выставить счет - Менеджер</a><br>
<a href="?PROCESS5=Y">5. Запустить Дожим в оплату - Менеджер</a><br>
<a href="?PROCESS6=Y">6. Запустить Контроль доставки - Менеджер</a><br>
<a href="?PROCESS7=Y">7. Запустить Контроль ожидания - 1</a><br>



