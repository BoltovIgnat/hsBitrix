<?
namespace Dbbo\Crm;

class Fields {
	public static function GetList(array $order = [], array $filter = [], $arGroupBy = false, $arNavStartParams = false) {
		$result = [];

        if (
            !\CModule::IncludeModule("crm") || empty($filter)
        ) {
            return $result;
        }

        $r = \CCrmFieldMulti::GetListEx($order, $filter, $arGroupBy, $arNavStartParams);
        while($a = $r->Fetch()) {
            $result[] = $a;
        }

        return $result;
    }
}