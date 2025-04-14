<?php

namespace Dbbo\Jivo\Api;

class Lead {
    public static function GetLead($id) {
        if (
            !\CModule::IncludeModule("crm")
        ) {
            throw new \Exception("error modules including");
        }
        $dbRes = \CCrmLead::GetList(
            [
                'ID' => 'ASC'
            ],
            [
                'CHECK_PERMISSIONS' => 'N',
                'ID' => intval($id)
            ],
            []
        );
        $res = $dbRes->Fetch();
        return $res;
    }
    
    public static function GetList($sort, $filter = []) {
        $res = [];
        
        if (
            \CModule::IncludeModule("crm") && !empty($filter)
        ) {
            $result = [];
            
            $dbRes = \CCrmLead::GetListEx(
                $sort,
                $filter
            );
            while($res = $dbRes->Fetch()) {
                $result[] = $res;
            }
        }
        
        return $result;
    }
    
    public static function GetPhoneLead($id, $lead = []) {
        $result = '';
        if (
            \CModule::IncludeModule("crm")
        ) {
            $r = \CCrmFieldMulti::GetList(array(), array("ELEMENT_ID" => intval($id)));
            $a = $r->Fetch();
            if($a) {
                $result = $a['VALUE'];
            } else {
                if(!empty($lead['CONTACT_ID'])) {
                    $r_contact = \CCrmFieldMulti::GetList(array(), array("ELEMENT_ID" => $lead['CONTACT_ID']));
                    $a_contact = $r_contact->Fetch();
                    $result = $a_contact['VALUE'];
                }
            }
        }
        
        return $result;
    }
    
    public static function SearchLeadByPhone($phone) {
        if (
            !\CModule::IncludeModule("crm") || empty($phone)
        ) {
            return false;
        }
        $result = [];
        $r = \CCrmFieldMulti::GetList(array(), array("%VALUE" => \NormalizePhone($phone)));
        while($a = $r->Fetch()) {
            if($a['ENTITY_ID'] == 'LEAD') {
                $result[] = $a['ELEMENT_ID'];
            }
        }
        
        return $result;
    }
    
    public static function SearchLeadByEmail($email) {
        if (
            !\CModule::IncludeModule("crm") || empty($email)
        ) {
            return false;
        }
        $result = [];
        $r = \CCrmFieldMulti::GetList(array(), array("%VALUE" => $email));
        while($a = $r->Fetch()) {
            if($a['ENTITY_ID'] == 'LEAD') {
                $result[] = $a['ELEMENT_ID'];
            }
        }
        
        return $result;
    }
    
    public static function Add($fields, $search = false, $params = []) {
        if (
            !\CModule::IncludeModule("crm") || empty($fields)
        ) {
            return false;
        }
        
        $entity = new \CCrmLead(false); 
        return $entity->add($fields, $search, $params);
    }
    
    public static function Update($id, $fields, $compare = true, $search = false, $params = []) {
        if (
            !\CModule::IncludeModule("crm") || empty($fields) || empty($id)
        ) {
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
