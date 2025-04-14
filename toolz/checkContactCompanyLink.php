<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

//$date = new \Bitrix\Main\Type\DateTime();

$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(CRM_SMART["reanimation"]);
$items = $factory->getItems([
    'filter' => ["STAGE_ID" => REANIMATIONOPENEDSTATUSES],
    'select' => ['COMPANY_ID','CONTACT_ID'],
]);

foreach ($items as $item) {
    if (!empty($item["COMPANY_ID"]) && !empty($item["CONTACT_ID"])) {
/*         $res = checkCompanyContactLink($item["COMPANY_ID"],$item["CONTACT_ID"],$item["ID"]);
        if ($res) {
            $projectCompanyWithoutContactLink[] = $res;
        } */
        
       $res = checkContactCompanyLink($item["CONTACT_ID"], $item["COMPANY_ID"],$item["ID"]);
       if ($res) {
            $reanimationsContactWithoutCompanyLink[] = $res;
       }
    }
}

if ($_REQUEST["PROCCESS_REANIMATION"] == "Y") {
    foreach ($reanimationsContactWithoutCompanyLink as $pr) {
       // connectCompanyToContact($pr["COMPANY_ID"],$pr["CONTACT_ID"]);
        connectContactToCompany($pr["CONTACT_ID"],$pr["COMPANY_ID"]);
    }

/*     foreach ($projectContactWithoutCompanyLink as $prc) {
        connectCompanyToContact($prc["COMPANY_ID"],$prc["CONTACT_ID"]);
        connectContactToCompany($prc["CONTACT_ID"],$prc["COMPANY_ID"]);
    } */
}
?>

<a href="?PROCCESS_REANIMATION=Y">Обработать реанимации</a>
<?/*
<h1>Проекты Компании без привязки к контакту</h1>
<?
+Kint::Dump($projectCompanyWithoutContactLink);
*/?>
<h1>Реанимации Контакты без привязки к компании</h1>
<?
+Kint::Dump($projectContactWithoutCompanyLink);
?>

<?
$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(CRM_SMART["project"]);
$items = $factory->getItems([
    'filter' => ["STAGE_ID" => PROJECTOPENEDSTATUSES],
    'select' => ['COMPANY_ID','CONTACT_ID'],
]);

foreach ($items as $item) {
    if (!empty($item["COMPANY_ID"]) && !empty($item["CONTACT_ID"])) {
/*         $res = checkCompanyContactLink($item["COMPANY_ID"],$item["CONTACT_ID"],$item["ID"]);
        if ($res) {
            $projectCompanyWithoutContactLink[] = $res;
        } */
        
       $res = checkContactCompanyLink($item["CONTACT_ID"], $item["COMPANY_ID"],$item["ID"]);
       if ($res) {
            $projectContactWithoutCompanyLink[] = $res;
       }
    }
}

if ($_REQUEST["PROCCESS_PROJECT"] == "Y") {
    foreach ($projectCompanyWithoutContactLink as $pr) {
       // connectCompanyToContact($pr["COMPANY_ID"],$pr["CONTACT_ID"]);
        connectContactToCompany($pr["CONTACT_ID"],$pr["COMPANY_ID"]);
    }

/*     foreach ($projectContactWithoutCompanyLink as $prc) {
        connectCompanyToContact($prc["COMPANY_ID"],$prc["CONTACT_ID"]);
        connectContactToCompany($prc["CONTACT_ID"],$prc["COMPANY_ID"]);
    } */
}

?>

<a href="?PROCCESS_PROJECT=Y">Обработать проекты</a>
<?/*
<h1>Проекты Компании без привязки к контакту</h1>
<?
+Kint::Dump($projectCompanyWithoutContactLink);
*/?>
<h1>Проекты Контакты без привязки к компании</h1>
<?
+Kint::Dump($projectContactWithoutCompanyLink);
?>



<?
$query = Bitrix\Crm\LeadTable::query()
->setSelect(["ID","TITLE","STATUS_ID", "COMPANY_ID", "CONTACT_ID"])
->setFilter(["STATUS_ID" => LEADOPENEDSTATUSES])
//->setLimit(1000)
->exec();
$elements = $query->fetchAll();

foreach ($elements as $element) {
    if (!empty($element["COMPANY_ID"]) && !empty($element["CONTACT_ID"])) {
/*         $res = checkCompanyContactLink($element["COMPANY_ID"],$element["CONTACT_ID"],$element["ID"]);
        if ($res) {
            $leadCompanyWithoutContactLink[] = $res;
        } */
        
       $res = checkContactCompanyLink($element["CONTACT_ID"], $element["COMPANY_ID"],$element["ID"]);
       if ($res) {
            $leadContactWithoutCompanyLink[] = $res;
       }
    }

}

if ($_REQUEST["PROCCESS_LEADS"] == "Y") {
/*     foreach ($leadCompanyWithoutContactLink as $pr) {
        connectCompanyToContact($pr["COMPANY_ID"],$pr["CONTACT_ID"]);
        connectContactToCompany($pr["CONTACT_ID"],$pr["COMPANY_ID"]);
    } */
    foreach ($leadContactWithoutCompanyLink as $prc) {
       // connectCompanyToContact($prc["COMPANY_ID"],$prc["CONTACT_ID"]);
        connectContactToCompany($prc["CONTACT_ID"],$prc["COMPANY_ID"]);
    }
}

?>

<a href="?PROCCESS_LEADS=Y">Обработать лиды</a>

<?/*
<h1>Лиды Компании без привязки к контакту</h1>
<?
+Kint::Dump($leadCompanyWithoutContactLink);
*/?>
<h1>Лиды Контакты без привязки к компании</h1>
<?
+Kint::Dump($leadContactWithoutCompanyLink);
?>

<?
$query = Bitrix\Crm\DealTable::query()
->setSelect(["ID","TITLE","STAGE_ID", "COMPANY_ID", "CONTACT_ID"])
->setFilter(["STAGE_ID" => DEALOPENEDSTAGES])
//->setLimit(1000)
->exec();
$elements = $query->fetchAll();

foreach ($elements as $element) {
    if (!empty($element["COMPANY_ID"]) && !empty($element["CONTACT_ID"])) {
/*         $res = checkCompanyContactLink($element["COMPANY_ID"],$element["CONTACT_ID"],$element["ID"]);
        if ($res) {
            $dealsCompanyWithoutContactLink[] = $res;
        } */
        
       $res = checkContactCompanyLink($element["CONTACT_ID"], $element["COMPANY_ID"],$element["ID"]);
       if ($res) {
            $dealsContactWithoutCompanyLink[] = $res;
       }
    }

}

if ($_REQUEST["PROCCESS_DEALS"] == "Y") {
/*     foreach ($dealsCompanyWithoutContactLink as $pr) {
        connectCompanyToContact($pr["COMPANY_ID"],$pr["CONTACT_ID"]);
        connectContactToCompany($pr["CONTACT_ID"],$pr["COMPANY_ID"]);
    } */
    foreach ($dealsContactWithoutCompanyLink as $prc) {
      // connectCompanyToContact($prc["COMPANY_ID"],$prc["CONTACT_ID"]);
        connectContactToCompany($prc["CONTACT_ID"],$prc["COMPANY_ID"]);
    }
}

/* $companyWithoutContactLinkRes = fillCompanyData($companyWithoutContactLink);
$contactWithoutCompanyLinkRes = fillContactData($contactWithoutCompanyLink); */

?>

<a href="?PROCCESS_DEALS=Y">Обработать сделки</a>

<?/*
<h1>Сделки Компании без привязки к контакту</h1>
<?
+Kint::Dump($dealsCompanyWithoutContactLink);
*/?>

<h1>Сделки Контакты без привязки к компании</h1>
<?
+Kint::Dump($dealsContactWithoutCompanyLink);
?>
<?
function checkCompanyContactLink($companyID,$contactID,$dealID) {

    $query = \Bitrix\Crm\Binding\ContactCompanyTable::query()
    ->addSelect("CONTACT_ID")
    ->setFilter(["COMPANY_ID" => $companyID ])
    ->exec();
    $contactsRes = $query->fetchAll();  

    foreach ($contactsRes as $contact) {
        if ($contact["CONTACT_ID"] == $contactID) {
            $hasContact = true;
        }
    }

    if ($hasContact != true) {
        $result = ["COMPANY_ID" => $companyID, "CONTACT_ID" => $contactID, "DEAL_ID" => $dealID ];
        return $result;
    }
}

function checkContactCompanyLink($contactID,$companyID,$dealID) {
    $query = \Bitrix\Crm\Binding\ContactCompanyTable::query()
    ->addSelect("COMPANY_ID")
    ->setFilter(["CONTACT_ID" => $contactID ])
    ->exec();
    $companyRes = $query->fetchAll();  

    foreach ($companyRes as $company) {
        if ($company["COMPANY_ID"] == $companyID) {
            $hasCompany = true;
        }
    }

    if ($hasCompany != true) {
        $result = ["COMPANY_ID" => $companyID, "CONTACT_ID" => $contactID, "DEAL_ID" => $dealID ];
        return $result;
    }
}

function connectCompanyToContact($companyID,$contactID) {
    $query = \Bitrix\Crm\Binding\ContactCompanyTable::add(["CONTACT_ID" => $contactID, "COMPANY_ID" => $companyID,"SORT" => 10, "ROLE_ID" => 0, "IS_PRIMARY" => "N"]);
}

function connectContactToCompany($contactID,$companyID) {
    $query = \Bitrix\Crm\Binding\ContactCompanyTable::add(["CONTACT_ID" => $contactID, "COMPANY_ID" => $companyID,"SORT" => 10, "ROLE_ID" => 0, "IS_PRIMARY" => "N"]);
}

function fillCompanyData(&$companyWithoutContactLink) {
    $query = \Bitrix\Crm\CompanyTable::query()
        ->setSelect(["ID","TITLE"])
        ->setFilter(["ID" => $companyWithoutContactLink])
        //->setLimit(1000)
        ->exec();
    $elements = $query->fetchAll();
    
    foreach ($elements as $company) {
        $companyWithoutContactLinkRes[$company["ID"]] = $company;
    }

    return $companyWithoutContactLinkRes;
}

function fillContactData($contactWithoutCompanyLink) {
    $query = \Bitrix\Crm\ContactTable::query()
        ->setSelect(["ID","FULL_NAME"])
        ->setFilter(["ID" => $contactWithoutCompanyLink])
        //->setLimit(1000)
        ->exec();
    $elements = $query->fetchAll();
    foreach ($elements as $contact) {
        $contactWithoutCompanyLinkRes[$contact["ID"]] = $contact;
    }
    return $contactWithoutCompanyLinkRes;
}
?>