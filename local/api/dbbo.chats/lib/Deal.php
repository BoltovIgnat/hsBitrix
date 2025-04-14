<?php

namespace Dbbo\Chat;

use \Bitrix\Crm\Binding\DealContactTable;

class Deal {
    public static function getList($order = [], $filter = [], $arGroupBy = false, $arNavStartParams = false, $arSelectFields = ['*', 'UF_*']) {
        $result = [];

        if (!\CModule::IncludeModule("crm") || empty($filter)) {
            return $result;
        }

        $dbRes = \CCrmDeal::GetListEx(
            $order,
            $filter,
            $arGroupBy,
            $arNavStartParams,
            $arSelectFields
        );
        while($res = $dbRes->Fetch()) {
            $result[] = $res;
        }
        
        return $result;
    }
    
    public static function GetDeal($dealId) {
        if (!\CModule::IncludeModule("crm") || !$dealId) {
            return false;
        }

        $result = [];
        $dbRes = \CCrmDeal::GetListEx(
            [],
            [
                'ID' => intval($dealId),
                'CHECK_PERMISSIONS' => 'N'
            ],
            false,
            false,
            ['*', 'UF_*']
        );
        if($res = $dbRes->Fetch()) {
            $result = $res;
        }
        
        return $result;
    }
    
    public static function Add($fields) {
        if (
            !\CModule::IncludeModule("crm") || empty($fields)
        ) {
            return false;
        }
        $entity = new \CCrmDeal(false);
        $add = $entity->Add($fields, true, array());

        if($add) {
            $errors = [];
            
            \CCrmBizProcHelper::AutoStartWorkflows(
		\CCrmOwnerType::Deal,
		$add,
		\CCrmBizProcEventType::Create,
		$errors
            );
            
            \Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Deal, $add);

        }
        return $add;
    }
    
    public static function Update($deal_id, $fields) {
        if (
            !\CModule::IncludeModule("crm") || empty($fields)
        ) {
            return false;
        }
        
        $entity = new \CCrmDeal(false);
        return $entity->Update($deal_id, $fields, true, true, array('DISABLE_USER_FIELD_CHECK' => true));
    }
    
    /**
     * 
     * @param type $dealId
     * @return boolean
     */
    public static function GetContactsDeal($dealId) {
        if (
            !\CModule::IncludeModule("crm")
        ) {
            return false;
        }
        
        return DealContactTable::getDealBindings($dealId);
    }
    
    /**
     * 
     * @param type $sort
     * @param type $filter
     * @return boolean
     */
    public static function GetProductList($sort = [], $filter = []) {
        if (!\CModule::IncludeModule("crm") || !$filter) {
            return false;
        }
        
        $result = [];
        
        $rowsCount = \CCrmProductRow::GetList($sort, $filter, false, false, array());
        while($item = $rowsCount->Fetch()) {
            $result[] = $item;
        }
        
        return $result;
    }
}

