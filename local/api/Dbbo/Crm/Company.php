<?
namespace Dbbo\Crm;

class Company {
	public static function CompanyFindByEmail(array $params) {
		$company = [];
		$req = new \Bitrix\Crm\EntityRequisite();
		
		foreach($params as $param) {
			$domen = trim( explode('@', $param)[1], '>' );

			if(in_array($domen, self::SkipDomen())) {
				continue;
			}

			$items = \CCrmFieldMulti::GetListEx([
					'ELEMENT_ID' => 'DESC'
				], [
				'ENTITY_ID' => 'COMPANY',
				'TYPE_ID' => 'EMAIL',
				'%VALUE' => '@'.$domen
			]);
			while($item = $items->Fetch()) {
				$company[] = $item['ELEMENT_ID'];
			}
		}
		return $company;
	}

	public static function FindByRequisite(string $inn, string $kpp) {
		$company = [];
		$req = new \Bitrix\Crm\EntityRequisite();

		$filter = [];

		if($inn) {
			$filter['RQ_INN'] = $inn;
		}

		if($kpp) {
			$filter['RQ_KPP'] = $kpp;
		}

		if($filter) {
			$items = $req->getList([
				'order' => [
					'ID' => 'ASC'
				],
				'filter' => $filter
			])->fetchAll();

			if(!$items && $inn) {
				$items = $req->getList([
					'order' => [
						'ID' => 'ASC'
					],
					'filter' => [
						'RQ_INN' => $inn
					]
				])->fetchAll();
			}

			if($items) {
				foreach($items as $item) {
					if($kpp && $kpp == $item['RQ_KPP']) {
						$company[] = $item['ENTITY_ID'];
						return $company;
					}
					$company[] = $item['ENTITY_ID'];
				}
			}
		}

		return $company;
	}

	public static function GetRequisite(int $companyId) {
		$req = new \Bitrix\Crm\EntityRequisite();
		$result = [];
		
		$items = $req->getList([
			'order' => [
				'ID' => 'ASC'
			],
			'filter' => [
				'ENTITY_ID' => $companyId,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Company
			]
		])->fetchAll();

		foreach($items as $item) {
			$result[] = $item;
		}

		return $result;
	}

	public static function SkipDomen() {
		return [
			'mail.ru',
			'gmail.com',
			'yandex.ru',
			'bk.ru',
			'inbox.ru',
			'highsystem.ru',
			'gwgr.ru',
			'ya.ru',
			'rambler.ru',
			'list.ru',
			'scanberry.ru',
			'icloud.com',
			'YANDEX.RU',
			'MAIL.RU',
			'YA.RU',
			'Mail.ru',
			'Yandex.ru',
			'Gmail.com',
			'GMAIL.COM',
			'RAMBLER.RU',
			'gmail.ru',
			'gmai.com',
			'yandex.kz',
			'hotmail.com',
			'mail.com',
			'BK.RU'
		];
	}

	public static function GetList(array $order = [], array $filter = [], array $arSelectFields = [], bool $nPageTop = false) {
		$result = [];

        if (
            !\CModule::IncludeModule("crm") || empty($filter)
        ) {
            return $result;
        }

        $dbRes = \CCrmCompany::GetList(
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

	public static function GetItem($companyId) {
        if (
            !\CModule::IncludeModule("crm")
        ) {
            return $result;
        }

		$filter = [
			'ID' => $companyId
		];

        $dbRes = \CCrmCompany::GetList(
            [],
            $filter,
            [],
            false
        );

        return $dbRes->Fetch();
    }

	public static function Update($companyId, $fields, $currentUser = '') {
        if (
            !\CModule::IncludeModule("crm") || empty($fields)
        ) {
            return false;
        }

        $entity = new \CCrmCompany(false);

        $option = [
            'DISABLE_USER_FIELD_CHECK' => true,
            'REGISTER_SONET_EVENT' => 'Y'
        ];

        if($currentUser) {
            $option['CURRENT_USER'] = intval($currentUser);
    	}

        $update = $entity->Update($companyId, $fields, true, true, $option);
    }

	public static function getCompanyContactIDs(int $companyId) {
		if (
            !\CModule::IncludeModule("crm")
        ) {
            return false;
        }
		return \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($companyId);
	}
}