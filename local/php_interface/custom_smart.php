<?php

use Bitrix\Crm\Item,
Bitrix\Crm\Service,
Bitrix\Main\Diag,
Bitrix\Crm\Service\Operation,
Bitrix\Main\DI,
Bitrix\Main\Loader,
Bitrix\Main\Result,
Bitrix\Main\Error,
Bitrix\Main\Web\HttpClient;

Loader::includeModule("crm");

$container = new class extends Service\Container {
    public function getFactory(int $entityTypeId): ?Service\Factory
    {

//        if ($entityTypeId === 178) {
        if (in_array($entityTypeId,[CRM_SMART["project"], 178, 130, 161, 151])) {
            $type = $this->getTypeByEntityTypeId($entityTypeId);

            $factory = new class ($type) extends Service\Factory\Dynamic {
                
                public function getAddOperation(Item $item, Service\Context $context = null): Operation\Add
                {

                    $operation = parent::getAddOperation($item, $context);

                    $operation->addAction(

                        Operation::ACTION_BEFORE_SAVE,

                        new class extends Operation\Action {

                        public function process(Item $item): Result
                        {

                            /*ваша функция*/

                            return new Result();

                        }

                        }

                    );

                    return $operation->addAction(

                        Operation::ACTION_AFTER_SAVE,

                        new class extends Operation\Action {

                        public function process(Item $item): Result
                        {

                            try {
                                $url = "http://price.local/b24/smart";
                                $httpClient = new HttpClient();

                                $sendArray = [
                                    "auth[application_token]" => "5y3t2wkjjf96ydgsp649q7hv5s3l2182",
                                    "event" => "ONCRMDYNAMICITEMADD",
                                    "data[FIELDS][ID]" => $item['id'],
                                    "stageId" => $item->getStageId(),
                                    "typeId" => $item->getEntityTypeId()
                                ];
    
                                $httpClient->post($url,$sendArray,true);
                            } catch(Exception $e) {
                                Diag\Debug::writeToFile(
                                    [
                                      'text'   => "Ошибка",
                                      'fields' => $e->getMessage()
                                    ],
                                    date('d.m.Y'),
                                    "./local/logs/smart-items.log"
                                  ); 
                            }

                            if ($item->getEntityTypeId() == CRM_SMART["project"]) {
                                $itemId = $item['id'];
                                $itemData = $item->getData();
                                
                                if (!empty($itemData["COMPANY_ID"])) {
                                    $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
                                        ->addSelect("SRC_ENTITY_ID")
                                        ->setFilter([
                                            "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                            "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
                                            "DST_ENTITY_ID" => $itemData["COMPANY_ID"]
                                        ])
                                        ->exec();
                                    $exist = $query->fetchAll();
                                    if (!empty($exist)) {
                                        $fields = [
                                            "RELATION_TYPE" => "BINDING",
                                            "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                            "SRC_ENTITY_ID" => $exist[0]["SRC_ENTITY_ID"],
                                            "DST_ENTITY_TYPE_ID" => CRM_SMART["project"],
                                            "DST_ENTITY_ID" => $itemData["ID"],
                                        ];
                                        $addRes = $query = \Bitrix\Crm\Relation\EntityRelationTable::Add($fields);
                                    }
                                }
                                else if ($itemData["COMPANY_ID"] == 0) {
                                    $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
                                    ->addSelect("SRC_ENTITY_ID")
                                    ->setFilter([
                                        "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                        "DST_ENTITY_TYPE_ID" => CRM_SMART["project"],
                                        "DST_ENTITY_ID" => $itemData["ID"]
                                    ])
                                    ->exec();
                                    $exist = $query->fetchAll();
                                    if ($exist) {
                                        $fields = [
                                            "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                            "SRC_ENTITY_ID" => $exist[0]["SRC_ENTITY_ID"],
                                            "DST_ENTITY_TYPE_ID" => CRM_SMART["project"],
                                            "DST_ENTITY_ID" => $itemData["ID"],
                                        ]; 
                                        $delRes = $query = \Bitrix\Crm\Relation\EntityRelationTable::Delete($fields);
                                    }
                                }
                            }

                            if ($item->getEntityTypeId() == CRM_SMART["registratsiya_proekta"]) {
                                $itemId = $item['id'];
                                $itemData = $item->getData();
                                
                                if (!empty($itemData["COMPANY_ID"])) {
                                    $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
                                        ->addSelect("SRC_ENTITY_ID")
                                        ->setFilter([
                                            "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                            "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
                                            "DST_ENTITY_ID" => $itemData["COMPANY_ID"]
                                        ])
                                        ->exec();
                                    $exist = $query->fetchAll();
                                    if (!empty($exist)) {
                                        $fields = [
                                            "RELATION_TYPE" => "BINDING",
                                            "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                            "SRC_ENTITY_ID" => $exist[0]["SRC_ENTITY_ID"],
                                            "DST_ENTITY_TYPE_ID" => CRM_SMART["registratsiya_proekta"],
                                            "DST_ENTITY_ID" => $itemData["ID"],
                                        ];
                                        $addRes = $query = \Bitrix\Crm\Relation\EntityRelationTable::Add($fields);

                                    }
                                }
                                else if ($itemData["COMPANY_ID"] == 0) {
                                    $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
                                    ->addSelect("SRC_ENTITY_ID")
                                    ->setFilter([
                                        "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                        "DST_ENTITY_TYPE_ID" => CRM_SMART["registratsiya_proekta"],
                                        "DST_ENTITY_ID" => $itemData["ID"]
                                    ])
                                    ->exec();
                                    $exist = $query->fetchAll();
                                    if ($exist) {
                                        $fields = [
                                            "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                            "SRC_ENTITY_ID" => $exist[0]["SRC_ENTITY_ID"],
                                            "DST_ENTITY_TYPE_ID" => CRM_SMART["registratsiya_proekta"],
                                            "DST_ENTITY_ID" => $itemData["ID"],
                                        ]; 
                                        $delRes = $query = \Bitrix\Crm\Relation\EntityRelationTable::Delete($fields);
                                    }
                                }
                            }

                            return new Result();
                        }

                        }

                    );

                }
                public function getUpdateOperation(Item $item, Service\Context $context = null): Operation\Update
                {
                    $operation = parent::getUpdateOperation($item, $context);

                    $operation->addAction(
                        Operation::ACTION_BEFORE_SAVE,
                        new class extends Operation\Action {
                            public function process(Item $item): Result
                            {
                                if ($item->getEntityTypeId() == CRM_SMART["project"]) {
                                    $itemId = $item['id'];
                                    $itemData = $item->getData();
                                    
                                    if (!empty($itemData["COMPANY_ID"])) {
                                        $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
                                            ->addSelect("SRC_ENTITY_ID")
                                            ->setFilter([
                                                "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                                "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
                                                "DST_ENTITY_ID" => $itemData["COMPANY_ID"]
                                            ])
                                            ->exec();
                                        $exist = $query->fetchAll();
                                        if (!empty($exist)) {
                                            $fields = [
                                                "RELATION_TYPE" => "BINDING",
                                                "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                                "SRC_ENTITY_ID" => $exist[0]["SRC_ENTITY_ID"],
                                                "DST_ENTITY_TYPE_ID" => CRM_SMART["project"],
                                                "DST_ENTITY_ID" => $itemData["ID"],
                                            ];
                                            $addRes = $query = \Bitrix\Crm\Relation\EntityRelationTable::Add($fields);
                                        }
                                    }
                                    else if ($itemData["COMPANY_ID"] == 0) {
                                        $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
                                        ->addSelect("SRC_ENTITY_ID")
                                        ->setFilter([
                                            "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                            "DST_ENTITY_TYPE_ID" => CRM_SMART["project"],
                                            "DST_ENTITY_ID" => $itemData["ID"]
                                        ])
                                        ->exec();
                                        $exist = $query->fetchAll();
                                        if ($exist) {
                                            $fields = [
                                                "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                                "SRC_ENTITY_ID" => $exist[0]["SRC_ENTITY_ID"],
                                                "DST_ENTITY_TYPE_ID" => CRM_SMART["project"],
                                                "DST_ENTITY_ID" => $itemData["ID"],
                                            ]; 
                                            $delRes = $query = \Bitrix\Crm\Relation\EntityRelationTable::Delete($fields);
                                        }
                                    }
                                }

                                if ($item->getEntityTypeId() == CRM_SMART["registratsiya_proekta"]) {
                                    $itemId = $item['id'];
                                    $itemData = $item->getData();
                                    
                                    if (!empty($itemData["COMPANY_ID"])) {
                                        $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
                                            ->addSelect("SRC_ENTITY_ID")
                                            ->setFilter([
                                                "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                                "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
                                                "DST_ENTITY_ID" => $itemData["COMPANY_ID"]
                                            ])
                                            ->exec();
                                        $exist = $query->fetchAll();
                                        if (!empty($exist)) {
                                            $fields = [
                                                "RELATION_TYPE" => "BINDING",
                                                "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                                "SRC_ENTITY_ID" => $exist[0]["SRC_ENTITY_ID"],
                                                "DST_ENTITY_TYPE_ID" => CRM_SMART["registratsiya_proekta"],
                                                "DST_ENTITY_ID" => $itemData["ID"],
                                            ];
                                            $addRes = $query = \Bitrix\Crm\Relation\EntityRelationTable::Add($fields);

                                        }
                                    }
                                    else if ($itemData["COMPANY_ID"] == 0) {
                                        $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
                                        ->addSelect("SRC_ENTITY_ID")
                                        ->setFilter([
                                            "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                            "DST_ENTITY_TYPE_ID" => CRM_SMART["registratsiya_proekta"],
                                            "DST_ENTITY_ID" => $itemData["ID"]
                                        ])
                                        ->exec();
                                        $exist = $query->fetchAll();
                                        if ($exist) {
                                            $fields = [
                                                "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
                                                "SRC_ENTITY_ID" => $exist[0]["SRC_ENTITY_ID"],
                                                "DST_ENTITY_TYPE_ID" => CRM_SMART["registratsiya_proekta"],
                                                "DST_ENTITY_ID" => $itemData["ID"],
                                            ]; 
                                            $delRes = $query = \Bitrix\Crm\Relation\EntityRelationTable::Delete($fields);
                                        }
                                    }
                                }

                                return new Result();
                            }
                        }
                    );
  
                    return $operation->addAction(

                        Operation::ACTION_AFTER_SAVE,

                        new class extends Operation\Action {

                        public function process(Item $item): Result
                        {
                            try {
                                $url = "http://price.local/b24/smart";
                                $httpClient = new HttpClient();

                                $sendArray = [
                                    "auth[application_token]" => "5y3t2wkjjf96ydgsp649q7hv5s3l2182",
                                    "event" => "ONCRMDYNAMICITEMUPDATE",
                                    "data[FIELDS][ID]" => $item['id'],
                                    "stageId" => $item->getStageId(),
                                    "typeId" => $item->getEntityTypeId()
                                                                                                            
                                ];
    
                                $httpClient->post($url,$sendArray,true);
                            } catch(Exception $e) {
                                Diag\Debug::writeToFile(
                                    [
                                      'text'   => "Ошибка",
                                      'fields' => $e->getMessage()
                                    ],
                                    date('d.m.Y'),
                                    "./local/logs/smart-items.log"
                                  ); 
                            }
                            return new Result();
                        }

                        }

                    );

                }
                public function getDeleteOperation(Item $item, \Bitrix\Crm\Service\Context $context = null): Operation\Delete
                {

                    $operation = parent::getDeleteOperation($item, $context);

                    $operation->addAction(

                        Operation::ACTION_BEFORE_SAVE,

                        new class extends Operation\Action {

                        public function process(Item $item): Result
                        {
                            return new Result();

                        }

                        }

                    );


                    return $operation;

                }
            };


            return $factory;
        }
        return parent::getFactory($entityTypeId);

    }

};


DI\ServiceLocator::getInstance()->addInstance('crm.service.container', $container);
