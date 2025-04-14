<?
namespace Dbbo\Sbis;

use Bitrix\Main\Result,
	Bitrix\Main\Error,
	Bitrix\Main\Loader,
	Dbbo\Curl;

class Sbis {
	const IBLOCK_ID = 39;
	
	public function __construct() {
		$this->result = new Result();
		$this->fields = [];
		
        if(!Loader::IncludeModule('iblock')) {
			return $this->result->AddError(new Error('Не установлен модуль Информационные блоки', 'NOT_INSTALL_IBLOCK'));
		}
		
		if(!Loader::IncludeModule('crm')) {
			return $this->result->AddError(new Error('Не установлен модуль CRM', 'NOT_INSTALL_CRM'));
		}
    }
	
	public function GetItem($params) {
		$curl = new Curl();

		$post_data = [
			'inn' => $params['inn'],
			'token' => "4b69946809dce01c983aa03a58a317e5d78d3764"
		];
		
		$headers = [
			"Content-Type: application/json",
			"Accept: application/json",
			"Accept-Language: en"
		];

		$curl->SetOptions([
			CURLOPT_URL => "https://crm.highsystem.ru/contragents/",
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => json_encode($post_data),
			CURLOPT_HTTPHEADER => $headers
		]);
		
		$curl->Request();
		
		return $curl->result->getData();
	}
	
	public function SetCodeFields($fields) {
		$this->fields = $fields;
	}
	
	public function SetCompanyData($companyId, $params) {
		$arFieldsCompany = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("CRM_COMPANY");
		foreach($arFieldsCompany as $fieldCompany) {
			if($fieldCompany['FIELD_NAME'] == $this->fields['defend']) {
				$obEnum = new \CUserFieldEnum;
				$rsEnum = $obEnum->GetList(array(), array('USER_FIELD_ID' => $fieldCompany['ID']));
				while($arEnum = $rsEnum->Fetch()) {
					$enum['defend'][$arEnum['VALUE']] = $arEnum['ID'];
				}
			}
			if($fieldCompany['FIELD_NAME'] == $this->fields['complain']) {
				$obEnum = new \CUserFieldEnum;
				$rsEnum = $obEnum->GetList(array(), array('USER_FIELD_ID' => $fieldCompany['ID']));
				while($arEnum = $rsEnum->Fetch()) {
					$enum['complain'][$arEnum['VALUE']] = $arEnum['ID'];
				}
			}
			if($fieldCompany['FIELD_NAME'] == $this->fields['tender']) {
				$obEnum = new \CUserFieldEnum;
				$rsEnum = $obEnum->GetList(array(), array('USER_FIELD_ID' => $fieldCompany['ID']));
				while($arEnum = $rsEnum->Fetch()) {
					$enum['tender'][$arEnum['VALUE']] = $arEnum['ID'];
				}
			}
		}

		foreach($this->fields as $key => $value) {
			if($key == 'defend') {
				$params[$key] = ($params[$key] === true) ? $enum['defend']['Да'] : $enum['defend']['Нет'];
			} elseif($key == 'complain') {
				$params[$key] = ($params[$key] === true) ? $enum['complain']['Да'] : $enum['complain']['Нет'];
			} elseif($key == 'tender') {
				$params[$key] = ($params[$key] === true) ? $enum['tender']['Да'] : $enum['tender']['Нет'];
			} elseif($key == 'address') {
				$params[$key] = $params['data']->address->unrestricted_value ?: '';
			} elseif($key == 'email') {
				$params[$key] = $params['contacts'] ? implode(',', $params['contacts']) : '';
			} elseif($key == 'director') {
				$params[$key] = $params['data']->management ? $params['data']->management->post . ' ' . $params['data']->management->name : '';
			} elseif($key == 'inn') {
				$params[$key] = $params['data']->inn ?: '';
			} elseif($key == 'count_staff') {
                if (str_contains($params['quantityEmployees'],"до")) {
                    $pos = strpos($params['quantityEmployees'],"до");
                    $ns = substr($params['quantityEmployees'],5,-$pos);
                    $params[$key] = $ns;
                }
                else {
                    $params[$key] = $params['quantityEmployees'] ?: '';
                }

			} elseif($key == 'kpp') {
				$params[$key] = $params['data']->kpp ?: '';
			} elseif($key == 'owners') {
				$itemValue = [];
				foreach($params[$key] as $v) {
					$itemValue[] = $v->name . '-' . $v->capital;
				}
				$params[$key] = $itemValue;
			}
			$fields[$value] = $params[$key];

		}

        if (str_contains($params['revenue'],"млрд") !== false) {
            $rate = 1000000000;
        }
        else if (str_contains($params['revenue'],"млн") !== false) {
            $rate = 1000000;
        }
        else if (str_contains($params['revenue'],"трлн") !== false) {
            $rate = 1000000000000;
        }

        $profint = str_replace([" ","₽","млрд","млн","трлн"],"",$params['revenue']);

        if ($rate) {
            $rev = $profint * $rate;
        }

        $fields[CRM_SETTINGS['company']['profit_int']] = $rev;
        $fields[CRM_SETTINGS['company']['profit_money']] = number_format( $rev, 2, '.', '' );

		$entity = new \CCrmCompany(false);

		return $entity->Update($companyId, $fields);
	}

	public static function AddToAgent($params) {
		if(!Loader::IncludeModule('iblock')) {
			return $result->AddError(new Error('Не установлен модуль Информационные блоки', 'NOT_INSTALL_IBLOCK'));
		}

		if(!Loader::IncludeModule('crm')) {
			return $result->AddError(new Error('Не установлен модуль CRM', 'NOT_INSTALL_CRM'));
		}

		$result = new Result();
		$requisite = new \Bitrix\Crm\EntityRequisite();

		if(!$params['companyId']) {
			return $result->AddError(new Error('Не передан параметр companyId', 'NOT_COMPANY_ID'));
		}

		$current = \CIBlockElement::GetList([], [
			'IBLOCK_ID' => self::IBLOCK_ID,
			'PROPERTY_COMPANY_ID' => $params['companyId']
		])->GetNext();
		
		$el = new \CIBlockElement;

		if($current) {
			\CIBlockElement::SetPropertyValuesEx($current['ID'], false, array('CODE' => 0, 'DEAL_ID' => $params['dealId']));
		} else {
			$rs = $requisite->getList(array(
				"filter" => array(
					"ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
					"ENTITY_ID" => $params['companyId']
				)
			))->fetch();

			if(!$rs) {
				return $result->AddError(new Error('Нет реквизитов у компании', 'NOT_REQUISITE'));
			}

			$addElementFields = [
				'IBLOCK_ID' => self::IBLOCK_ID,
				'NAME' => 'Запрос на получение информации. ID компании - '. $params['companyId'],
				'PROPERTY_VALUES' => [
					'DATETIME' => new \Bitrix\Main\Type\DateTime(),
					'COMPANY_ID' => $params['companyId'],
					'CODE' => 0,
					'COUNT' => 1,
					'DEAL_ID' => $params['dealId'],
					'RESPONSE' => ''
				]
			];

			if(!$el->Add($addElementFields)) {
				return $result->AddError(new Error($el->LAST_ERROR, 'NOT_ADD'));
			}
		}
		
		return $result;
	}
}