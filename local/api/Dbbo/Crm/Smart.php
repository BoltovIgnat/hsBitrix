<?php

namespace Dbbo\Crm;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Application,
    Bitrix\Main\Loader,
    Bitrix\Sale\Cashbox,
    Bitrix\Sale\Payment,
    Bitrix\Sale\Order,
    Bitrix\Main\Result,
    Bitrix\Main\Error,
    Bitrix\Crm\ItemIdentifier,
    Bitrix\Crm\Service\Container;

class Smart {
    var $result = null;

    var $config = [];

    public function __construct() {
        $this->result = new Result();

        if(!Loader::includeModule('sale')) {
            $this->result->AddError(new Error('Не установлен модуль Торговый каталог', 'NOT_INSTALLED_MODULE_SALE'));
        }

        if(!$this->result->isSuccess()) {
            return $this->result;
        }
    }

	public function SetEntityId(int $entityId) {
		$this->entityId = $entityId;
	}

    public function GetChilds($parentTypeId, $parentId, $childTypeId) {
        $parentIdentifier = new ItemIdentifier($parentTypeId, $parentId);

        $childRelations = Container::getInstance()->getRelationManager()->getChildRelations($parentTypeId);

        $childIds = [];

        foreach ($childRelations as $childRelation) {
            $childEntityTypeId = $childRelation->getChildEntityTypeId();

            if($childEntityTypeId != $childTypeId) {
                continue;
            }

            $childItems = $childRelation->getChildElements($parentIdentifier);
            foreach ($childItems as $childItem) {
                $childIds[] = $childItem->getEntityId();
            }
        }

        return $childIds;
    }

    public function GetItems($params) {
        $childs = [];
        $factory = Container::getInstance()->getFactory($this->entityId);

        $items = $factory->getItems($params);

        foreach($items as $item) {
            $childs[] = $item->getData();
        }

        return $childs;
    }

    public function UpdateItem($id, $fields) {
        $factory = Container::getInstance()->getFactory($this->entityId);

        $item = $factory->getItems([
            'select' => ['*'],
            'filter' => ['=ID' => $id],
        ])[0];

        if($item) {
            $item->setFromCompatibleData($fields);
            $result = $item->save();
            return ($result->isSuccess()) ? $item->getId() : false;
            }
    }

    public function AddItem($params) {
        $result = false;
        
        $factory = Container::getInstance()->getFactory($this->entityId);

        $item = $factory->createItem();

        foreach($params as $key => $value) {
            $item->set($key, $value);
        }

        $operation = $factory->getAddOperation($item)
            ->disableCheckAccess()
            ->disableCheckFields();

        $result = $operation->launch();
        
        return ($result->isSuccess()) ? $item->getId() : false;
    }
}