<?php

namespace Dbbo\Chat;

class Lead {

    public static function GetList($sort, $filter = []): array
    {
        $result = [];

        if (!\CModule::IncludeModule("crm") || empty($filter)) {
            return $result;
        }

        $dbRes = \CCrmLead::GetListEx(
            $sort,
            $filter
        );
        while($res = $dbRes->Fetch()) {
            $result[] = $res;
        }

        return $result;
    }

    public static function SearchLeadByPhone($phone): array
    {
        $result = [];
        if (!\CModule::IncludeModule("crm") || !$phone) {
            return $result;
        }

        $r = \CCrmFieldMulti::GetList(array(), array("%VALUE" => \NormalizePhone($phone)));
        while($a = $r->Fetch()) {
            if($a['ENTITY_ID'] == 'LEAD') {
                $result[] = $a['ELEMENT_ID'];
            }
        }
        
        return $result;
    }
    
    public static function SearchLeadByEmail($email): array
    {
        $result = [];
        if (!\CModule::IncludeModule("crm") || !$email) {
            return $result;
        }

        $r = \CCrmFieldMulti::GetList(array(), array("%VALUE" => $email));
        while($a = $r->Fetch()) {
            if($a['ENTITY_ID'] == 'LEAD') {
                $result[] = $a['ELEMENT_ID'];
            }
        }
        
        return $result;
    }
    
    public static function Add($fields, $search = false, $params = []) {
        if (!\CModule::IncludeModule("crm") || empty($fields)) {
            return false;
        }
        
        $entity = new \CCrmLead(false); 
        return $entity->add($fields, $search, $params);
    }
    
    public static function Update($id, $fields, $compare = true, $search = false, $params = []) {

        if (!\CModule::IncludeModule("crm") || empty($fields) || 0 >= $id) {
            return false;
        }
        
        $entity = new \CCrmLead(false);
        $update = $entity->Update($id, $fields, $compare, $search, $params);
        
        if($update) {
            \CCrmBizProcHelper::AutoStartWorkflows(
                \CCrmOwnerType::Lead,
                $id,
                \CCrmBizProcEventType::Edit,
                $arErrors
            );
        }
        
        return $update;
    }
}
