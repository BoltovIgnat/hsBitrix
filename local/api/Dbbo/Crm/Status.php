<?
namespace Dbbo\Crm;

class Status {
	public static function GetList(array $order = [], array $filter = []) {
		$result = [];

        if (
            !\CModule::IncludeModule("crm")
        ) {
            return $result;
        }

        $dbStatus = \CCrmStatus::GetList(
			$order,
			$filter
		);
		while($ar = $dbStatus->Fetch()) {
			$result[] = $ar;
		}

        return $result;
    }
}