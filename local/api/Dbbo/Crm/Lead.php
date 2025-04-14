<?
namespace Dbbo\Crm;

class Lead {
	public static function Add($fields, $search = false, $params = []) {
        if (
            !\CModule::IncludeModule("crm") || empty($fields)
        ) {
            return false;
        }
        
        $entity = new \CCrmLead(false); 
        $leadId = $entity->add($fields, $search, $params);

        \CCrmBizProcHelper::AutoStartWorkflows(
            \CCrmOwnerType::Lead,
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
        
        $entity = new \CCrmLead(false);
        $update = $entity->Update($id, $fields, $compare, $search, $params);
        
        return $update;
    }

	public static function GetList($sort, $filter = [], $group = false, $nav = false, $select = [], $options = []) {
        $res = [];
        
        if (
            \CModule::IncludeModule("crm") && !empty($filter)
        ) {
            $result = [];
            
            $dbRes = \CCrmLead::GetListEx(
                $sort,
                $filter,
                $group,
				$nav,
				$select,
				$options
            );
            while($res = $dbRes->Fetch()) {
                $result[] = $res;
            }
        }
        
        return $result;
    }

	public static function GetItem(int $leadId): array {
        if (
            \CModule::IncludeModule("crm")
        ) {
            $result = [];

			$filter = [
				'CHECK_PERMISSION' => 'N',
				'ID' => $leadId
			];
            
            $dbRes = \CCrmLead::GetListEx(
                [],
                $filter,
                false,
				false,
				['*', 'UF_*'],
				[]
            );
			$res = $dbRes->Fetch();

			if($res) {
				$result = $res;
			}
        }

        return $result;
    }

	public static function LeadFindByPhone(array $params) {
		$lead = [];

		foreach($params as $param) {
			$param = \NormalizePhone($param);

			$leadPhone = Fields::GetList([], [
				'ENTITY_ID' => 'LEAD',
				'TYPE_ID' => 'PHONE',
				'CHECK_PERMISSION' => 'N',
				'%VALUE' => $param
			]);
			if($leadPhone) {
				$lead = array_unique(array_merge($lead, $leadPhone));
			}

			$leadPhone = Fields::GetList([], [
				'ENTITY_ID' => 'LEAD',
				'TYPE_ID' => 'PHONE',
				'CHECK_PERMISSION' => 'N',
				'%VALUE' => substr_replace($phone, '8', 0, 1)
			]);
			if($leadPhone) {
				$lead = array_unique(array_merge($lead, $leadPhone));
			}

			if($lead) {
				$leadIds = [];
				foreach($lead as $item) {
					$leadIds[] = $item['ELEMENT_ID'];
				}
				$lead = self::GetActiveLeads($leadIds);
			}
		}
		return $lead;
	}

    public static function GetActiveLeads(array $leadIds): array
    {
        $lead = [];
        $db = self::GetList([], [
            'CHECK_PERMISSION' => 'N',
            'ID' => $leadIds,
            'STATUS_SEMANTIC_ID' => 'P'
        ], false, false, [
            'ID',
            'ASSIGNED_BY_LAST_NAME',
            'ASSIGNED_BY_NAME'
        ]);
        foreach($db as $ar) {
            $lead[$ar['ID']] = $ar;
        }

        return $lead;
    }
}