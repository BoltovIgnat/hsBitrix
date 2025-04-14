<?
namespace Dbbo\Crm;

use Dbbo\Crm\Fields;
use Bitrix\Main\PhoneNumber\Format;
use Bitrix\Main\PhoneNumber\Parser;
use Dbbo\Debug\Dump;

class Contact {
	public static function GetList(array $order = [], array $filter = [], array $arSelectFields = [], bool $nPageTop = false) {
		$result = [];

        if (
            !\CModule::IncludeModule("crm") || empty($filter)
        ) {
            return $result;
        }

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

	public static function GetContact($contactId) {
		$result = [];

        if (
            !\CModule::IncludeModule("crm")
        ) {
            return $result;
        }

		$filter = [
			'ID' => $contactId
		];

        $dbRes = \CCrmContact::GetList(
            [],
            $filter,
            [],
            false
        );
        
        return $dbRes->Fetch();
    }

	public static function getContactCompanyIDs(int $contactId) {
		$result = [];

        if (
            !\CModule::IncludeModule("crm")
        ) {
            return $result;
        }

		return \Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($contactId);
    }

	public static function ContactFindByPhone(array $params) {
		$contact = [];
		
		foreach($params as $param) {
			$param = \NormalizePhone($param);

			$fields = Fields::GetList([], [
				'ENTITY_ID' => 'CONTACT',
				'TYPE_ID' => 'PHONE',
				'CHECK_PERMISSION' => 'N',
				'%VALUE' => $param
			]);

			if(!$fields) {
				$fields = Fields::GetList([], [
					'ENTITY_ID' => 'CONTACT',
					'TYPE_ID' => 'PHONE',
					'CHECK_PERMISSION' => 'N',
					'%VALUE' => substr_replace($param, '8', 0, 1)
				]);
			}

			if($fields) {
				foreach($fields as $item) {
					$contact[] = $item['ELEMENT_ID'];
				}
			}
		}
		return $contact;
	}

	public static function ContactFindByEmail(array $params) {
		$contact = [];
		
		foreach($params as $param) {
			$fields = Fields::GetList([], [
				'ENTITY_ID' => 'CONTACT',
				'TYPE_ID' => 'EMAIL',
				'CHECK_PERMISSION' => 'N',
				'%VALUE' => $param
			]);

			if($fields) {
				foreach($fields as $item) {
					$contact[] = $item['ELEMENT_ID'];
				}
			}
		}
		return $contact;
	}

    public static function Update(int $contactId, array $params) {
        if (!\CModule::IncludeModule("crm")) {
            return false;
        }

        $entity = new \CCrmContact(false);
        return $entity->Update(
            $contactId,
            $params,
            true,
            array('DISABLE_USER_FIELD_CHECK' => true)
        );
    }

    public static function Add(array $fields): mixed
    {
        if (!\CModule::IncludeModule("crm")) {
            return false;
        }

        $entity = new \CCrmContact(false);

        return $entity->Add(
            $fields,
            true,
            array('DISABLE_USER_FIELD_CHECK' => true)
        );
    }

	public static function CheckEmailPhone(int $contactId, array $phoneSearch, array $emailSearch) {
		if($phoneSearch) {
			$updatePhone = [];
			foreach($phoneSearch as $item) {
				$fieldsPhone = self::ContactFindByPhone([$item]);
				if(!$fieldsPhone || !in_array($contactId, $fieldsPhone)) {
					$updatePhone[] = \NormalizePhone($item);
				}
			}
		}

		if($emailSearch) {
			$updateEmail = [];
			foreach($emailSearch as $item) {
				$fieldsEmail = self::ContactFindByEmail([$item]);
				if(!$fieldsEmail || !in_array($contactId, $fieldsEmail)) {
					$updateEmail[] = $item;
				}
			}
		}

		$updateContact = [];

		if($updatePhone) {
			foreach($updatePhone as $key => $item) {
				$parsedPhone = Parser::getInstance()->parse($item);
				$type = $parsedPhone->getNumberType() == 'fixedLine' ? 'WORK' : 'MOBILE';
				$updateContact['FM']['PHONE']['n'.$key] = [
					'VALUE_TYPE' => $type,
					'VALUE' => $parsedPhone->format(Format::E164)
				];
			}
		}

		if($updateEmail) {
			foreach($updateEmail as $key => $item) {
				$updateContact['FM']['EMAIL']['n'.$key] = [
					'VALUE_TYPE' => 'WORK',
					'VALUE' => $item
				];
			}
		}
		Dump::DumpToFile($updateContact, '$updateContact');
		if($updateContact) {
			self::Update($contactId, $updateContact);
		}
	}
}