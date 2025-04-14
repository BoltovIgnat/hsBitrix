<?
namespace Dbbo\Crm;

class UserFields {
	public static function GetEnum(string $type, string $name) {
		$result = [];
		$arFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields($type);
		foreach($arFields as $field) {
			if($field['FIELD_NAME'] == $name) {
				$obEnum = new \CUserFieldEnum;
				$rsEnum = $obEnum->GetList(array(), array('USER_FIELD_ID' => $field['ID']));
				while($arEnum = $rsEnum->Fetch()) {
					$result[$arEnum['ID']] = $arEnum['VALUE'];
				}
				break;
			}
		}

        return $result;
    }
}