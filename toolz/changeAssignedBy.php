<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

//$fp = fopen($_SERVER["DOCUMENT_ROOT"] . '/ponomarevCompany.csv', 'a');
//fputcsv($fp, mb_convert_encoding(["ID Компании", "Название компании","Ответственный", "ID Контакта","ФИО","Ответственный за контакт"], 'windows-1251', 'utf-8'), ";"); 
/* 
$rsUsers = CUser::GetList(($by="ID"), ($order="desc"), ["ACTIVE" => "Y","!UF_DEPARTMENT" => "NULL"],["SELECT" =>["UF_DEPARTMENT"],"FIELDS" =>["ID","NAME","LAST_NAME"]]);
while($userRes = $rsUsers->GetNext()) {
    $users[$userRes["ID"]] = $userRes["LAST_NAME"] . " " . $userRes["NAME"];
}


$query = \Bitrix\Crm\CompanyTable::query()
->setSelect(["ID","TITLE","ASSIGNED_BY_ID"])
->setFilter(["ASSIGNED_BY_ID" => 48])
//->setLimit(1000)
->exec();
$elements = $query->fetchAll(); 

foreach ($elements as &$element) {
   // $element["ASSIGNED_BY_ID"] = $users[$element["ASSIGNED_BY_ID"]];
  //  fputcsv($fp, mb_convert_encoding([$element["ID"],$element["TITLE"],$element["ASSIGNED_BY_ID"], "","",""], 'windows-1251', 'utf-8'), ";"); 
    $query = \Bitrix\Crm\ContactTable::query()
        ->setSelect(["ID"])
        ->setFilter(["COMPANY_ID" => $element["ID"]])
        ->exec();
        $contacts = $query->fetchAll();

        foreach ($contacts as $contact) {
            //$contact["ASSIGNED_BY_ID"] = $users[$contact["ASSIGNED_BY_ID"]];
           // $element["CONTACTS"][] = $contact;    
            //fputcsv($fp, mb_convert_encoding(["","","", $contact["ID"],$contact["FULL_NAME"],$contact["ASSIGNED_BY_ID"]], 'windows-1251', 'utf-8'), ";"); 
            \Bitrix\Crm\ContactTable::update($contact["ID"],["ASSIGNED_BY_ID" => 406]);
            //perms
            $perms = \Bitrix\Crm\EntityPermsTable::query()
            ->setSelect(["*"])
            ->setFilter(["ENTITY" => "CONTACT","ENTITY_ID" => $contact["ID"]])
            ->exec();
            $existPerms = $perms->fetchAll(); 
        
            foreach ($existPerms as $perms) {
                \Bitrix\Crm\EntityPermsTable::delete($perms["ID"]);
            }

            \Bitrix\Crm\EntityPermsTable::add(["ENTITY" => "CONTACT","ENTITY_ID" => $contact["ID"],"ATTR" => "U406"]);
            \Bitrix\Crm\EntityPermsTable::add(["ENTITY" => "CONTACT","ENTITY_ID" => $contact["ID"],"ATTR" => "IU406"]);
            \Bitrix\Crm\EntityPermsTable::add(["ENTITY" => "CONTACT","ENTITY_ID" => $contact["ID"],"ATTR" => "D1"]);
        }
    
    \Bitrix\Crm\CompanyTable::update($element["ID"],["ASSIGNED_BY_ID" => 406]);
    //perms
    $perms = \Bitrix\Crm\EntityPermsTable::query()
    ->setSelect(["*"])
    ->setFilter(["ENTITY" => "COMPANY","ENTITY_ID" => $element["ID"]])
    ->exec();
    $existPerms = $perms->fetchAll(); 

    foreach ($existPerms as $perms) {
        \Bitrix\Crm\EntityPermsTable::delete($perms["ID"]);
    }
    
    \Bitrix\Crm\EntityPermsTable::add(["ENTITY" => "COMPANY","ENTITY_ID" => $element["ID"],"ATTR" => "U406"]);
    \Bitrix\Crm\EntityPermsTable::add(["ENTITY" => "COMPANY","ENTITY_ID" => $element["ID"],"ATTR" => "IU406"]);
    \Bitrix\Crm\EntityPermsTable::add(["ENTITY" => "COMPANY","ENTITY_ID" => $element["ID"],"ATTR" => "D1"]);

} */

//+Kint::Dump($elements);
//fclose($fp);

$query = \Bitrix\Crm\ContactTable::query()
->setSelect(["ID"])
->setFilter(["ASSIGNED_BY_ID" => 48])
->exec();
$contacts = $query->fetchAll();

foreach ($contacts as $contact) {
    
    \Bitrix\Crm\ContactTable::update($contact["ID"],["ASSIGNED_BY_ID" => 406]);

    $perms = \Bitrix\Crm\EntityPermsTable::query()
    ->setSelect(["*"])
    ->setFilter(["ENTITY" => "CONTACT","ENTITY_ID" => $contact["ID"]])
    ->exec();
    $existPerms = $perms->fetchAll(); 

    foreach ($existPerms as $perms) {
        \Bitrix\Crm\EntityPermsTable::delete($perms["ID"]);
    }
    
    \Bitrix\Crm\EntityPermsTable::add(["ENTITY" => "CONTACT","ENTITY_ID" => $contact["ID"],"ATTR" => "U406"]);
    \Bitrix\Crm\EntityPermsTable::add(["ENTITY" => "CONTACT","ENTITY_ID" => $contact["ID"],"ATTR" => "IU406"]);
    \Bitrix\Crm\EntityPermsTable::add(["ENTITY" => "CONTACT","ENTITY_ID" => $contact["ID"],"ATTR" => "D1"]);
}
?>





