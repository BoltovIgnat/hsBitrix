<?php
$_SERVER["DOCUMENT_ROOT"] = "/home/bitrix/ext_www/tesths.dbbo.ru"; // или можно еще так: realpath(dirname(__FILE__)."/../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

while(ob_get_level()) {
    ob_get_clean();
}


\Bitrix\Main\Loader::includeModule('crm');
$res = \Bitrix\Crm\DealTable::query()
    ->where('STAGE_ID', 'WON')
    ->countTotal(true)
    ->exec();
$rowsCount = $res->getCount();

$i = 0;
while ($i<=$rowsCount) {
    start($i);
    $i = ($i + 1000);
}
//start();
function start($offset = 0)
{
    \Bitrix\Main\Loader::includeModule('crm');
    $res = \Bitrix\Crm\DealTable::query()
        ->setSelect(['ID', 'TITLE', 'COMPANY_ID', 'CONTACT_ID'])
        ->where('STAGE_ID', 'WON')
        //->where('ID', 4)
        ->setOrder(['DATE_CREATE' => 'DESC'])
        ->setLimit(1000)
        ->setOffset($offset)
        ->exec();

    $rows = $res->fetchAll();

    foreach ($rows as &$deal) {
        if (empty($deal["COMPANY_ID"])) {
            $res = \Bitrix\Crm\ContactTable::query()
                ->setSelect(['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'])
                ->addSelect("CONT", "CONTACTS_")
                ->where('ID', $deal["CONTACT_ID"])
               // ->where('CONTACTS_ENTITY_ID', "CONTACT")
                ->registerRuntimeField(
                    null,
                    new \Bitrix\Main\Entity\ReferenceField(
                        'CONT',
                        \Bitrix\Crm\FieldMultiTable::getEntity(),
                        [
                            '=this.ID' => 'ref.ELEMENT_ID',
                        ]
                    )
                )
                ->exec();
            $contacts = $res->fetchAll();
            $contactField = [];
            foreach ($contacts as $contact) {
                if ($contact["CONTACTS_TYPE_ID"] == 'LINK' || $contact["CONTACTS_TYPE_ID"] == 'IM') {
                    continue;
                }
                if ($contact["CONTACTS_TYPE_ID"] == "PHONE") {
                    $contactField["PHONE"] = $contact["CONTACTS_VALUE"];
                }
                if ($contact["CONTACTS_TYPE_ID"] == "EMAIL") {
                    $contactField["EMAIL"] = $contact["CONTACTS_VALUE"];
                }
                $contactField["ID"] = $contact["ID"];
                $contactField["COMPANY_ID"] = $contact["COMPANY_ID"];
                $contactField["NAME"] = $contact["NAME"];
                $contactField["LAST_NAME"] = $contact["LAST_NAME"];
                $contactField["SECOND_NAME"] = $contact["SECOND_NAME"];
                $deal["CONTACTS"][] = $contactField;
            }
        }
        else {
            $res = \Bitrix\Crm\Binding\ContactCompanyTable::query()
                ->setSelect(['COMPANY_ID', 'CONTACT_ID'])
                ->where('COMPANY_ID', $deal["COMPANY_ID"])
                ->exec();
            $contacts = $res->fetchAll();
            $contactsIds = [];
            foreach ($contacts as $contact) {
                $contactsIds[] = $contact["CONTACT_ID"];
            }
            //+Kint::Dump($contactsIds);
            if (!empty($contactsIds)) {
                $res = \Bitrix\Crm\ContactTable::query()
                    ->setSelect(['ID', 'COMPANY_ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'])
                    ->addSelect("CONT", "CONTACTS_")
                    ->whereIn('ID', $contactsIds)
                    ->where('CONTACTS_ENTITY_ID', "CONTACT")
                    ->registerRuntimeField(
                        null,
                        new \Bitrix\Main\Entity\ReferenceField(
                            'CONT',
                            \Bitrix\Crm\FieldMultiTable::getEntity(),
                            [
                                '=this.ID' => 'ref.ELEMENT_ID',
                            ]
                        )
                    )
                    ->exec();
                $contacts = $res->fetchAll();
                $contactField = [];
                //+Kint::Dump($contacts);
                foreach ($contacts as $contact) {
                    if ($contact["CONTACTS_TYPE_ID"] == 'LINK' || $contact["CONTACTS_TYPE_ID"] == 'IM') {
                        continue;
                    }
                    if ($contact["CONTACTS_TYPE_ID"] == "PHONE") {
                        $contactField["PHONE"] = $contact["CONTACTS_VALUE"];
                    }
                    if ($contact["CONTACTS_TYPE_ID"] == "EMAIL") {
                        $contactField["EMAIL"] = $contact["CONTACTS_VALUE"];
                    }
                    $contactField["ID"] = $contact["ID"];
                    $contactField["COMPANY_ID"] = $contact["COMPANY_ID"];
                    $contactField["NAME"] = $contact["NAME"];
                    $contactField["LAST_NAME"] = $contact["LAST_NAME"];
                    $contactField["SECOND_NAME"] = $contact["SECOND_NAME"];
                    $deal["CONTACTS"][] = $contactField;
                }
            }
            else {
                $res = \Bitrix\Crm\ContactTable::query()
                    ->setSelect(['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'])
                    ->addSelect("CONT", "CONTACTS_")
                    ->where('ID', $deal["CONTACT_ID"])
                    // ->where('CONTACTS_ENTITY_ID', "CONTACT")
                    ->registerRuntimeField(
                        null,
                        new \Bitrix\Main\Entity\ReferenceField(
                            'CONT',
                            \Bitrix\Crm\FieldMultiTable::getEntity(),
                            [
                                '=this.ID' => 'ref.ELEMENT_ID',
                            ]
                        )
                    )
                    ->exec();
                $contacts = $res->fetchAll();
                $contactField = [];
                foreach ($contacts as $contact) {
                    if ($contact["CONTACTS_TYPE_ID"] == 'LINK' || $contact["CONTACTS_TYPE_ID"] == 'IM') {
                        continue;
                    }
                    if ($contact["CONTACTS_TYPE_ID"] == "PHONE") {
                        $contactField["PHONE"] = $contact["CONTACTS_VALUE"];
                    }
                    if ($contact["CONTACTS_TYPE_ID"] == "EMAIL") {
                        $contactField["EMAIL"] = $contact["CONTACTS_VALUE"];
                    }
                    $contactField["ID"] = $contact["ID"];
                    $contactField["COMPANY_ID"] = $contact["COMPANY_ID"];
                    $contactField["NAME"] = $contact["NAME"];
                    $contactField["LAST_NAME"] = $contact["LAST_NAME"];
                    $contactField["SECOND_NAME"] = $contact["SECOND_NAME"];
                    $deal["CONTACTS"][] = $contactField;
                }
            }
        }
    }

    if ($offset > 0 ) {
        $fp = fopen($_SERVER["DOCUMENT_ROOT"] . '/dealsContacts.csv', 'a');
    }
    else {
        $fp = fopen($_SERVER["DOCUMENT_ROOT"] . '/dealsContacts.csv', 'w');
    }

    fputcsv($fp, mb_convert_encoding(["ID Сделки","ID Компании", "ID Контакта","Телефон", "Почта"], 'windows-1251', 'utf-8'), ";");
    foreach ($rows as $newDeal) {
        foreach ($newDeal["CONTACTS"] as $contact) {
            $fields = [
                'DEAL_ID' => mb_convert_encoding($newDeal["ID"], 'windows-1251', 'utf-8'),
                'COMPANY_ID' => mb_convert_encoding($contact["COMPANY_ID"], 'windows-1251', 'utf-8'),
                'CONTACT_ID' => mb_convert_encoding($contact["ID"], 'windows-1251', 'utf-8'),
/*                'LAST_NAME' => mb_convert_encoding($contact["LAST_NAME"], 'windows-1251', 'utf-8'),
                'NAME' => mb_convert_encoding($contact["NAME"], 'windows-1251', 'utf-8'),
                'SECOND_NAME' => mb_convert_encoding($contact["SECOND_NAME"], 'windows-1251', 'utf-8'),*/
                'PHONE' => mb_convert_encoding($contact["PHONE"], 'windows-1251', 'utf-8'),
                'EMAIL' => mb_convert_encoding($contact["EMAIL"], 'windows-1251', 'utf-8'),
            ];
            fputcsv($fp, $fields, ";");
        }
    }
    //+Kint::Dump($rows);
}
fclose($fp);
?>
<a href="/dealsContacts.csv">Скачать список</a>
