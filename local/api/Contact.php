<?php

namespace Dbbo\Jivo\Api;

class Contact {
    /**
     * 
     * @param type $order
     * @param type $filter
     * @param type $arSelectFields
     * @param type $nPageTop
     * @return boolean
     */
    public static function getList($order = [], $filter = [], $arSelectFields = [], $nPageTop = false) {
        if (
            !\CModule::IncludeModule("crm") || empty($filter)
        ) {
            return false;
        }
        $result = [];

        $dbRes = \CCrmContact::GetList(
            $order,
            $filter,
            $arSelectFields,
            $nPageTop
        );
        while($res = $dbRes->Fetch()) {
            $result[] = $res;
        }
        
        return $result;
    }
    
    /**
     * 
     * @param type $fields
     * @return boolean
     */
    public static function Add($fields) {
        if (
            !\CModule::IncludeModule("crm") || empty($fields)
        ) {
            return false;
        }
        
        $entity = new \CCrmContact(false);
        
        return $entity->Add($fields, true, array('DISABLE_USER_FIELD_CHECK' => true));
    }
    
    /**
     * 
     * @param type $id
     * @param type $fields
     * @param type $bCompare
     * @param type $bUpdateSearch
     * @param type $arOptions
     * @return boolean
     */
    public static function Update($id, $fields, $bCompare = true, $bUpdateSearch = true, $arOptions = []) {
        if (
            !\CModule::IncludeModule("crm") || empty($fields)
        ) {
            return false;
        }
        
        $entity = new \CCrmContact(false);
        
        return $entity->Update($id, $fields, $bCompare, $bUpdateSearch, $arOptions);
    }
    
    public static function Search($order = [], $filter = [], $arGroupBy = false, $arNavStartParams = false, $arSelectFields = [], $arOptions = []) {
        if (
            !\CModule::IncludeModule("crm") || empty($filter)
        ) {
            return false;
        }
        $result = [];
        
        $items = \CCrmFieldMulti::GetListEx($order, $filter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
        
        while($item = $items->Fetch()) {
            $result[] = $item;
        }
        
        return $result;
    }
}