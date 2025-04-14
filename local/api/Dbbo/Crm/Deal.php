<?
namespace Dbbo\Crm;

class Deal {
	public static function Add($fields, $search = false, $params = []) {
        if (
            !\CModule::IncludeModule("crm") || empty($fields)
        ) {
            return false;
        }
        
        $entity = new \CCrmDeal(false); 
        $leadId = $entity->add($fields, $search, $params);
        
        \CCrmBizProcHelper::AutoStartWorkflows(
            \CCrmOwnerType::Deal,
            $leadId,
            \CCrmBizProcEventType::Create,
            $arErrors
        );
        
        return $leadId;
    }

	public static function Update($id, $fields, $compare = true, $search = false, $params = []) {
        if (
            !\CModule::IncludeModule("crm") || empty($fields) || empty($id)
        ) {
            return false;
        }
        
        $entity = new \CCrmDeal(false);
        $update = $entity->Update($id, $fields, $compare, $search, $params);

        
        return $update;
    }

	public static function GetDeal($dealId) {
        if (
            !\CModule::IncludeModule("crm") || !$dealId
        ) {
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

	public static function getList($order = [], $filter = [], $arGroupBy = false, $arNavStartParams = false, $arSelectFields = ['*', 'UF_*']) {
        if (
            !\CModule::IncludeModule("crm") || empty($filter)
        ) {
            return false;
        }
        $result = [];
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
}