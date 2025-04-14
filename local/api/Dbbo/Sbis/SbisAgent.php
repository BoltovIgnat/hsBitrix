<?
namespace Dbbo\Sbis;

use Bitrix\Main\Loader;

class SbisAgent {
	const IBLOCK_ID = 39;
	
	public static function Run($page, $limit) {
		$sbis = new \Dbbo\Sbis\Sbis();
		$requisite = new \Bitrix\Crm\EntityRequisite();
		$el = new \CIBlockElement;

		$count = $requisite->getList([
			'filter' => [
				"ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
				"!RQ_INN" => false
			],
			'count_total' => true
		])->getCount();

		$rs = $requisite->getList(array(
			"order" => [
				"ID" => "ASC"
			],
			"filter" => array(
				"ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
				"!RQ_INN" => false
			),
			'limit' => $limit,
			'offset' => $page > 1 ? $page * $limit : 0
		));
		while($ar = $rs->Fetch()) {
			$sbis->SetCodeFields([
				'revenue' => CRM_SETTINGS['company']['revenue'],
				'profit' => CRM_SETTINGS['company']['profit'],
				'reliability' => CRM_SETTINGS['company']['reliability'],
				'defend' => CRM_SETTINGS['company']['defend'],
				'plus' => CRM_SETTINGS['company']['plus'],
				'minus' => CRM_SETTINGS['company']['minus'],
				'link' => CRM_SETTINGS['company']['link'],
				'complain' => CRM_SETTINGS['company']['complain'],
				'tender' => CRM_SETTINGS['company']['tender'],
				'age' => CRM_SETTINGS['company']['age'],
				'owners' => CRM_SETTINGS['company']['owners'],
				'linked' => CRM_SETTINGS['company']['linked'],
				'linkedAll' => CRM_SETTINGS['company']['linkedAll'],
				'count_staff' => CRM_SETTINGS['company']['count_staff'],
				'address' => CRM_SETTINGS['company']['address'],
				'phone' => CRM_SETTINGS['company']['phone'],
				'email' => CRM_SETTINGS['company']['email'],
				'inn' => CRM_SETTINGS['company']['inn'],
				'kpp' => CRM_SETTINGS['company']['kpp'],
				'director' => CRM_SETTINGS['company']['director'],
				'capital' => CRM_SETTINGS['company']['capital'],
                'profit_int' => CRM_SETTINGS['company']['profit_int'],
                'profit_money' => CRM_SETTINGS['company']['profit_money'],
			]);
			$result = $sbis->GetItem([
				'inn' => $ar['RQ_INN']
			]);

			$date = new \Bitrix\Main\Type\DateTime();

			$addElementFields = [
				'IBLOCK_ID' => self::IBLOCK_ID,
				'NAME' => 'Запрос на получение информации. ID компании - '. $ar['ENTITY_ID'],
				'PROPERTY_VALUES' => [
					'DATETIME' => $date,
					'COMPANY_ID' => $ar['ENTITY_ID'],
					'CODE' => $result["RESULT"]->success ? 1 : 2,
					'COUNT' => 1,
					'RESPONSE' => serialize($result["RESULT"]->result->sbis)
				]
			];

			$el->Add($addElementFields);

			if($result["RESULT"]->success) {
				$update = $sbis->SetCompanyData($ar['ENTITY_ID'], (array) $result["RESULT"]->result->sbis);
			}
			sleep(5);
		}

		if($page * $limit >= $count) {
			return "\Dbbo\Sbis\SbisAgent::Run(1, $limit);";
		} else {
			$page = $page + 1;
			return "\Dbbo\Sbis\SbisAgent::Run($page, $limit);";
		}
	}
	
	public static function Check($limit, $error = '') {		
		$sbis = new \Dbbo\Sbis\Sbis();

		if(!$sbis->result->isSuccess()) {
			$error = implode(', ', $sbis->result->getErrorMessages());
			return "\Dbbo\Sbis\SbisAgent::Check($limit, $error);";
		}
		
		$requisite = new \Bitrix\Crm\EntityRequisite();
		$el = new \CIBlockElement;
		
		$db = \CIBlockElement::GetList(["ID" => "DESC"], [
			'IBLOCK_ID' => self::IBLOCK_ID,
			array(
				'LOGIC' => 'OR',
				array(
					'=PROPERTY_CODE' => '0',
				),
				array(
					'=PROPERTY_CODE' => 2
				)
			),
            '!PROPERTY_CODE' => 3,
			'<PROPERTY_COUNT' => 6
			], false, array(
				'nTopCount' => $limit
			),
			array(
				'ID',
				'IBLOCK_ID',
				'PROPERTY_COMPANY_ID',
				'PROPERTY_CODE',
				'PROPERTY_COUNT'
			)
		);
		while($res = $db->GetNext()) {
			$rs = $requisite->getList(array(
				"order" => [
					"ID" => "ASC"
				],
				"filter" => array(
					"ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
					"ENTITY_ID" => $res['PROPERTY_COMPANY_ID_VALUE']
				)
			));
            $reqCount = $rs->getSelectedRowsCount();
            if ($reqCount > 0 ) {
                while ($ar = $rs->fetch()) {
                    $sbis->SetCodeFields([
                        'revenue' => CRM_SETTINGS['company']['revenue'],
                        'profit' => CRM_SETTINGS['company']['profit'],
                        'reliability' => CRM_SETTINGS['company']['reliability'],
                        'defend' => CRM_SETTINGS['company']['defend'],
                        'plus' => CRM_SETTINGS['company']['plus'],
                        'minus' => CRM_SETTINGS['company']['minus'],
                        'link' => CRM_SETTINGS['company']['link'],
                        'complain' => CRM_SETTINGS['company']['complain'],
                        'tender' => CRM_SETTINGS['company']['tender'],
                        'age' => CRM_SETTINGS['company']['age'],
                        'owners' => CRM_SETTINGS['company']['owners'],
                        'linked' => CRM_SETTINGS['company']['linked'],
                        'linkedAll' => CRM_SETTINGS['company']['linkedAll'],
                        'count_staff' => CRM_SETTINGS['company']['count_staff'],
                        'address' => CRM_SETTINGS['company']['address'],
                        'phone' => CRM_SETTINGS['company']['phone'],
                        'email' => CRM_SETTINGS['company']['email'],
                        'inn' => CRM_SETTINGS['company']['inn'],
                        'kpp' => CRM_SETTINGS['company']['kpp'],
                        'director' => CRM_SETTINGS['company']['director'],
                        'capital' => CRM_SETTINGS['company']['capital'],
                        'profit_int' => CRM_SETTINGS['company']['profit_int'],
                        'profit_money' => CRM_SETTINGS['company']['profit_money'],
                    ]);
                    $result = $sbis->GetItem([
                        'inn' => $ar['RQ_INN']
                    ]);

                    $date = new \Bitrix\Main\Type\DateTime();
                    $count = ($result["RESULT"]->success) ? $res['PROPERTY_COUNT_VALUE'] : ($res['PROPERTY_COUNT_VALUE'] + 1);

                    $arFields = [
                        'CODE' => $result["RESULT"]->success ? 1 : 2,
                        'COUNT' => $count,
                        'DATETIME' => $date,
                        'RESPONSE' => serialize($result["RESULT"]->result->sbis)
                    ];

                    \CIBlockElement::SetPropertyValuesEx($res['ID'], false, $arFields);

                    if ($result["RESULT"]->success) {
                        $update = $sbis->SetCompanyData($ar['ENTITY_ID'], (array)$result["RESULT"]->result->sbis);
                    }

                    break;
                }
            }
            else {
                $date = new \Bitrix\Main\Type\DateTime();
                $arFields = [
                    'CODE' => 3,
                    'DATETIME' => $date,
                ];

                \CIBlockElement::SetPropertyValuesEx($res['ID'], false, $arFields);
            }
			sleep(2);
		}
		
		return "\Dbbo\Sbis\SbisAgent::Check($limit);";
	}
}