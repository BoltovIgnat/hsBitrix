<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");


//$date = new \Bitrix\Main\Type\DateTime();

$query = Bitrix\Crm\DealTable::query()
->setSelect(["ID","TITLE","STAGE_ID", "COMPANY_ID", "CONTACT_ID"])
//->setFilter(["ID" => 129432])
//->setLimit(1000)
->exec();
$elements = $query->fetchAll();

foreach ($elements as $element) {
    if (findCompany($element["COMPANY_ID"])) {
        $dealCompanyBadLink[] = $element;
    }

    if (findContact($element["CONTACT_ID"])) {
        $dealContactBadLink[] = $element;
    }

}
?>
<h1>Сделки Битые ссылки на компании</h1>
<?
+Kint::Dump($dealCompanyBadLink);
?>
<br>
<h1>Сделки Битые ссылки на контакты</h1>
<?
+Kint::Dump($dealContactBadLink);
?>

<?
$query = Bitrix\Crm\LeadTable::query()
->setSelect(["ID", "TITLE", "STATUS_ID", "COMPANY_ID", "CONTACT_ID"])
//->setFilter(["ID" => 129432])
//->setLimit(1000)
->exec();
$elements = $query->fetchAll();

foreach ($elements as $element) {
    if (findCompany($element["COMPANY_ID"])) {
        $leadCompanyBadLink[] = $element;
    }

    if (findContact($element["CONTACT_ID"])) {
        $leadContactBadLink[] = $element;
    }

}
?>
<h1>Лиды Битые ссылки на компании</h1>
<?
+Kint::Dump($leadCompanyBadLink);
?>
<br>
<h1>Лиды Битые ссылки на контакты</h1>
<?
+Kint::Dump($leadContactBadLink);
?>

<?
    $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(CRM_SMART["project"]);
    $items = $factory->getItems([
        'filter' => [""],
        'select' => ['COMPANY_ID','CONTACT_ID'],
    ]);

    foreach ($items as $item) {
        
        if (findCompany($item["COMPANY_ID"])) {
            $projectCompanyBadLink[] = $item;
        }
    
        if (findContact($item["CONTACT_ID"])) {
            $projectContactBadLink[] = $item;
        }
    }
?>
<h1>Проекты Битые ссылки на компании</h1>
<?
+Kint::Dump($projectCompanyBadLink);
?>
<br>
<h1>Проекты Битые ссылки на контакты</h1>
<?
+Kint::Dump($projectContactBadLink);
?>

<?
function findContact($id) {

    $query = Bitrix\Crm\ContactTable::query()
    ->setSelect(["ID"])
    ->setFilter(["ID" => $id])
    ->exec();
    $contact = $query->fetch();

    if (empty($contact["ID"])) {
        return $id;
    }
    else {
        return false;
    }

}

function findCompany($id) {
    
    $query = Bitrix\Crm\CompanyTable::query()
    ->setSelect(["ID"])
    ->setFilter(["ID" => $id])
    ->exec();
    $company = $query->fetch();

    if (empty($company["ID"])) {
        return $id;
    }
    else {
        return false;
    }

}
