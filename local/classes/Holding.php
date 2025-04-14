<?php
namespace Hs;

class Holding {

    public static function onAfterRelationAdd(\Bitrix\Main\Entity\Event $event)
    {
        $eventFields = $event->getParameter("fields");
                    
        //Если к холдингу привязали компанию привязываем все связанные с компанией сущности к холдингу
        if ($eventFields["DST_ENTITY_TYPE_ID"] == \CCrmOwnerType::Company && $eventFields["SRC_ENTITY_TYPE_ID"] == CRM_SMART["holding"]) {

            //Привяжем все сделки компании к холдингу
            $dbResult = \CCrmDeal::GetListEx(
                [],
                [ 'CHECK_PERMISSIONS' => 'N', 'COMPANY_ID' => $eventFields["DST_ENTITY_ID"] ],
                false,
                false,
                ["ID"],
                []
            );
            while($arDeal = $dbResult->GetNext())
            {
                $fields = ["PARENT_ID_".CRM_SMART["holding"] => $eventFields["SRC_ENTITY_ID"]];
                (new \CCrmDeal(false))->update($arDeal["ID"],$fields);
            }

            //Привяжем все контакты компании к холдингу
            $query = \Bitrix\Crm\Binding\ContactCompanyTable::query()
            ->addSelect("CONTACT_ID")
            ->setFilter(["COMPANY_ID" => $eventFields["DST_ENTITY_ID"] ])
            ->exec();
            $contactsRes = $query->fetchAll();
            
            foreach ($contactsRes as $contact) {
                $fields = ["PARENT_ID_".CRM_SMART["holding"] => $eventFields["SRC_ENTITY_ID"]];
                (new \CCrmContact(false))->update($contact["CONTACT_ID"],$fields);
            }

            //Привяжем все проекты компании к холдингу
            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(CRM_SMART["project"]);
            $items = $factory->getItems([
                'filter' => ['COMPANY_ID' => $eventFields["DST_ENTITY_ID"] ],
                'select' => ['ID'],
            ]);

            foreach ($items as $item) {
                $item->set("PARENT_ID_".CRM_SMART["holding"], $eventFields["SRC_ENTITY_ID"]);
                $item->save();            
            }
        }
    }

    public static function onAfterRelationDelete(\Bitrix\Main\Entity\Event $event)
    { 
        $eventFields = $event->getParameter("id");
        
        //Если компанию отвязали от холдинга отвязываем все связанные с компанией сущности от холдинга
            if ($eventFields["DST_ENTITY_TYPE_ID"] == \CCrmOwnerType::Company && $eventFields["SRC_ENTITY_TYPE_ID"] == CRM_SMART["holding"]) {

            //отвяжем контакты привязанные к холдингу если у контактов нет других связей с холдингом
            $query = \Bitrix\Crm\Binding\ContactCompanyTable::query()
            ->addSelect("CONTACT_ID")
            ->setFilter(["COMPANY_ID" => $eventFields["DST_ENTITY_ID"] ])
            ->exec();
            $contactsRes = $query->fetchAll();
            
            foreach ($contactsRes as $contact) {
                $query = \Bitrix\Crm\Binding\ContactCompanyTable::query()
                ->addSelect("COMPANY_ID")
                ->setFilter(["CONTACT_ID" => $contact["CONTACT_ID"] ])
                ->exec();
                $companyRes = $query->fetchAll();

                $exist = false;
                foreach ($companyRes as $company) {
                    $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
                    ->addSelect("DST_ENTITY_ID")
                    ->setFilter([
                        "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                        "SRC_ENTITY_ID" => $eventFields["SRC_ENTITY_ID"],
                        "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
                        "DST_ENTITY_ID" => $company["COMPANY_ID"]
                    ])
                    ->exec();
                    $exist = $query->fetchAll();
                }

                if (!empty($exist)) {
                    continue;
                }

                $fields = ["PARENT_ID_".CRM_SMART["holding"] => 0];
                (new \CCrmContact(false))->update($contact["CONTACT_ID"],$fields);
            }
            
            //Отвяжем все сделки компании от холдинга
            $dbResult = \CCrmDeal::GetListEx(
                [],
                [ 'CHECK_PERMISSIONS' => 'N', 'COMPANY_ID' => $eventFields["DST_ENTITY_ID"] ],
                false,
                false,
                ["ID"],
                []
            );
            while($arDeal = $dbResult->GetNext())
            {
                $fields = ["PARENT_ID_".CRM_SMART["holding"] => 0];
                (new \CCrmDeal(false))->update($arDeal["ID"],$fields);
            }

            //Отвяжем все проекты компании от холдинга
            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(CRM_SMART["project"]);
            $items = $factory->getItems([
                'filter' => ['COMPANY_ID' => $eventFields["DST_ENTITY_ID"] ],
                'select' => ['ID'],
            ]);

            foreach ($items as $item) {
                $item->set("PARENT_ID_".CRM_SMART["holding"], 0);
                $item->save();
            }         
        }
    }

    public static function onContactAddCompanyLink(&$fields)
    {
        //Контакт привязали к компании, если компания привязанна к холдингу то привязать и контакт
        $IDS = \Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($fields["ID"]);

        foreach ($IDS as $compID) {
            $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
            ->addSelect("SRC_ENTITY_ID")
            ->setFilter([
                "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
                "DST_ENTITY_ID" => $compID
            ])
            ->exec();
            $exist = $query->fetchAll();
        }
        
        if (!empty($exist)) {
            $arfields = ["PARENT_ID_".CRM_SMART["holding"] => $exist[0]["SRC_ENTITY_ID"]];
            (new \CCrmContact(false))->update($fields["ID"],$arfields);
        }

    }

    public static function onContactCompanyLink(&$fields)
    {
        //Контакт привязали к компании, если компания привязанна к холдингу то привязать и контакт
        foreach ($fields["COMPANY_IDS"] as $compID) {
            $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
            ->addSelect("SRC_ENTITY_ID")
            ->setFilter([
                "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
                "DST_ENTITY_ID" => $compID
            ])
            ->exec();
            $exist = $query->fetchAll();
        }
        
        if (!empty($exist)) {
             $fields["PARENT_ID_".CRM_SMART["holding"]] = $exist[0]["SRC_ENTITY_ID"];
            
        }
        
    }

    public static function onDealAddCompanyLink(&$fields)
    {
        if (!empty($fields["COMPANY_ID"])) {
            //Сделку привязали к компании, если компания привязанна к холдингу то привязать и сделку
            $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
            ->addSelect("SRC_ENTITY_ID")
            ->setFilter([
                "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
                "DST_ENTITY_ID" => $fields["COMPANY_ID"]
            ])
            ->exec();
            $exist = $query->fetchAll();
            
            if (!empty($exist)) {
                $fields["PARENT_ID_".CRM_SMART["holding"]] = $exist[0]["SRC_ENTITY_ID"];
            }
        }
    }
    
    public static function onDealCompanyLink(&$fields)
    {
        if (array_key_exists('COMPANY_ID', $fields)) {
            if (!empty($fields["COMPANY_ID"])) {
                //Сделку привязали к компании, если компания привязанна к холдингу то привязать и сделку
                $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
                ->addSelect("SRC_ENTITY_ID")
                ->setFilter([
                    "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                    "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
                    "DST_ENTITY_ID" => $fields["COMPANY_ID"]
                ])
                ->exec();
                $exist = $query->fetchAll();
                
                if (!empty($exist)) {
                    $fields["PARENT_ID_".CRM_SMART["holding"]] = $exist[0]["SRC_ENTITY_ID"];
                    //(new \CCrmDeal(false))->update($fields["ID"],$arfields);
                }
            }

            //Если сделку отвязали от компании, отвяжем от холдинга
            if (empty($fields["COMPANY_ID"])) {
                $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
                ->addSelect("SRC_ENTITY_ID")
                ->setFilter([
                    "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                    "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Deal,
                    "DST_ENTITY_ID" => $fields["ID"]
                ])
                ->exec();
                $exist = $query->fetchAll();
                if ($exist) {
                    $fields["PARENT_ID_".CRM_SMART["holding"]] = 0;
                // (new \CCrmDeal(false))->update($fields["ID"],$arfields);
                }
            }
        }
    }

}
?>