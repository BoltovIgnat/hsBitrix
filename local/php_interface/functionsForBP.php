<?php

class BPFunctions {

    public static function getActivitiesForContact($contactsIDS,$datefrom = "",$dateto = "") {
        if (!is_array($contactsIDS)) {
            $contactsIDS = [$contactsIDS];
        }
        $date_from = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($datefrom));
        $date_to = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($dateto));
        $query = \Bitrix\Crm\ActivityTable::query()
        ->setSelect(["OWNER_ID","PROVIDER_TYPE_ID"])
        ->whereBetween("CREATED", $date_from, $date_to)
        ->setFilter([ "OWNER_TYPE_ID" => 3, "OWNER_ID" => $contactsIDS ])
        ->exec();
        $activities = $query->fetchAll();
        $arResult = [];
        foreach($activities as $activitie) {
            $arResult[$activitie["OWNER_ID"]][$activitie["PROVIDER_TYPE_ID"]] += 1;
        }
        return $arResult;
    }

    public static function getOpenedProjectsByCompanyId($companyID) {
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(174);
        $items = $factory->getItems([
            'filter' => ['COMPANY_ID' => $companyID, "STAGE_ID" => PROJECTOPENEDSTATUSES ],
            'select' => ['*'],
        ]);
        
        foreach ($items as $item) {
            $els[] = $item->getData();
        }
        return $els;
    }
    
    public static function getOpenedLeadsByCompanyId($companyID) {
        $query = \Bitrix\Crm\LeadTable::query()
        ->setSelect(["*"])
        ->setFilter([ "COMPANY_ID" => $companyID, "STATUS_ID" => LEADOPENEDSTATUSES ])
        ->exec();
        $activeLeads = $query->fetchAll();

        return $activeLeads;
    }

    public static function getOpenedDealsByCompanyId($companyID) {
        $query = \Bitrix\Crm\DealTable::query()
        ->setSelect(["*"])
        ->setFilter([ "CATEGORY_ID" => 0,"COMPANY_ID" => $companyID, "STAGE_ID" => DEALOPENEDSTAGES ])
        ->exec();
        $activeDeals = $query->fetchAll();

        return $activeDeals;
    }

    public static function getOpenClosedCRMEntities($elementsIds = false, $entityTypeID) {
        $class = match ($entityTypeID) {
            \CCrmOwnerType::Deal => "\Bitrix\Crm\DealTable",
            \CCrmOwnerType::Lead => "\Bitrix\Crm\LeadTable"
        };
    
        $query = $class::query()
            ->setSelect(["ID","STAGE_SEMANTIC_ID"])
            ->setFilter([ "!STAGE_SEMANTIC_ID" => ["S","F"] ])
            ->exec();
        $openCount = $query->getSelectedRowsCount();
    
        $query = $class::query()
            ->setSelect(["ID","STAGE_SEMANTIC_ID"])
            ->setFilter([ "STAGE_SEMANTIC_ID" => ["S","F"] ])
            ->exec();
        $closedCount = $query->getSelectedRowsCount();
    
        return ["OPEN" => $openCount,"CLOSED" => $closedCount];
    
    }

    public static function getOpenClosedSmartProccesses($elementsIds=false,$smartID) {
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($smartID);
        if ($elementsIds) {
            $filterOpen = ["!STAGE_ID" => ["%FAIL","%SUCCESS"],"ID" => $elementsIds];
            $filterClosed = ['STAGE_ID' => ["%FAIL","%SUCCESS"],"ID" => $elementsIds];
        }
        else {
            $filterOpen = ['!STAGE_ID' => ["%FAIL","%SUCCESS"]];
            $filterClosed = ['STAGE_ID' => ["%FAIL","%SUCCESS"]];
        }
    
        $open = $factory->getItems([
            'filter' => $filterOpen,
            'select' => ['ID'],
            'order' => ['ID' => 'ASC'],
        ]);
        $openCount = count($open);
    
        $closed = $factory->getItems([
            'filter' => $filterClosed,
            'select' => ['ID'],
            'order' => ['ID' => 'ASC'],
        ]);
        $closedCount = count($closed);
        
        return ["OPEN" => $openCount,"CLOSED" => $closedCount];
    }

    public static function delObserver($entityTypeID,$entityID,$userID) {
        Bitrix\Crm\Observer\Entity\ObserverTable::delete(
            ["ENTITY_TYPE_ID" => $entityTypeID,"ENTITY_ID" => $entityID,"USER_ID" => $userID]
        );
    }

    public static function addObserver($entityTypeID,$entityID,$userID) {
        $query = \Bitrix\Crm\Observer\Entity\ObserverTable::query()
        ->setSelect(["USER_ID"])
        ->setFilter(["ENTITY_TYPE_ID" => $entityTypeID,"ENTITY_ID" => $entityID,"USER_ID" => $userID])
        ->exec();
        $curObserver = $query->fetch()["USER_ID"];

        if (empty($curObserver)) {
            Bitrix\Crm\Observer\Entity\ObserverTable::add(
                [
                    "ENTITY_TYPE_ID" => $entityTypeID,
                    "ENTITY_ID" => $entityID,
                    "USER_ID" => $userID,
                    "SORT" => 10,
                    "CREATED_TIME" => new \Bitrix\Main\Type\DateTime(),
                    "LAST_UPDATED_TIME" => new \Bitrix\Main\Type\DateTime()
                ]
            );
        }
    }
    
    public static function addCrmElement($typeID,$fields) {
        $class = match($typeID){
            CCrmOwnerType::Lead => '\CCrmLead',
            CCrmOwnerType::Deal => '\CCrmDeal',
            CCrmOwnerType::Contact => '\CCrmContact',
            CCrmOwnerType::Company => '\CCrmCompany',
            CCrmOwnerType::Requisite => '\Bitrix\Crm\EntityRequisite',
            default => 'smart'
        };
        if ($class != 'smart') {
            $options = array('CURRENT_USER'=>1);
            $itemID = (new $class(false))->Add($fields,true,$options);
        }
        else if ($class == 'smart') {
            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($typeID);
            if (!is_array($fields["CONTACT_ID"])) {
                $fields["CONTACT_ID"] = [$fields["CONTACT_ID"]];
            }
            if (!empty($fields["CONTACT_ID"]) && count($fields["CONTACT_ID"]) > 1) {
                $contacts = $fields["CONTACT_ID"];
                $fields["CONTACT_ID"] = $fields["CONTACT_ID"][0];
                $item = $factory->createItem($fields);
                unset($contacts[0]);
                $conctactsBinding = \Bitrix\Crm\Binding\EntityBinding::prepareEntityBindings(CCrmOwnerType::Contact,$contacts);
                $item->bindContacts($conctactsBinding);
            }
            else {
                $fields["CONTACT_ID"] = $fields["CONTACT_ID"][0];
                $item = $factory->createItem($fields);    
            }

            $operation = $factory->getAddOperation($item)
                ->disableCheckAccess()
                ->disableCheckFields();
            $result = $operation->launch();
            
            $item->save();
            $itemID = $item->getId();
        }
        if ($itemID) {
            \CCrmBizProcHelper::AutoStartWorkflows(
                $typeID,
                $itemID,
                \CCrmBizProcEventType::Create,
                $arErrors,
            );
            $starter = new \Bitrix\Crm\Automation\Starter($typeID, $itemID);
            $starter->setUserId(1);
            $starter->runOnAdd();
            return $itemID;
        }
        else {
            return 'Ошибка создания';
        }
    }

    public static function editCrmElement($typeID,$elementID,$fields) {
        //TODO: Добавить изменяемые поля
        $class = match($typeID){
            CCrmOwnerType::Lead => '\CCrmLead',
            CCrmOwnerType::Deal => '\CCrmDeal',
            CCrmOwnerType::Contact => '\CCrmContact',
            CCrmOwnerType::Company => '\CCrmCompany',
            CCrmOwnerType::Requisite => '\Bitrix\Crm\EntityRequisite',
            default => 'smart'
        };
        if ($class != 'smart') {
            (new $class(false))->Update($elementID,$fields);
        }
        else if ($class == 'smart') {
            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($typeID);
            $class = $factory->getDataClass();
            $class::update($elementID,$fields);
        }
        \CCrmBizProcHelper::AutoStartWorkflows(
            $typeID,
            $elementID,
            \CCrmBizProcEventType::Edit,
            $arErrors,
        );
        $starter = new \Bitrix\Crm\Automation\Starter($typeID, $elementID);
        $starter->setUserId(1);
        $starter->runOnUpdate($fields,$fields);
    }

    public static function addComment($text,$authorID,$typeID,$elementID) {
        $entryID = \Bitrix\Crm\Timeline\CommentEntry::create(
            [
                'TEXT' => $text,
                'AUTHOR_ID' => $authorID ?: 0,
                'BINDINGS' => [['ENTITY_TYPE_ID' => $typeID, 'ENTITY_ID' => $elementID]]
            ]
        );
        $saveData = array(
            'COMMENT' => $text,
            'ENTITY_TYPE_ID' => $typeID,
            'ENTITY_ID' => $elementID,
        );

        Bitrix\Crm\Timeline\CommentController::getInstance()->onCreate($entryID, $saveData);
    }

}
class DealBPFunctions {
    public static function checkFutureDate($specifiedDate) {
        $currentDate = date("Y-m-d"); // Текущая дата (без времени)
        $timestamp = strtotime($specifiedDate);

        if ($timestamp === false) {
            $status = "Некорректный формат даты!";
            return $status;
        } else {
            if ($specifiedDate < $currentDate) {
                $status = "Дата находится в прошлом.";
                return $status;
            } elseif ($specifiedDate > $currentDate) {
                $dayOfWeek = date('w', $timestamp);

                if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                    // Перемещаем дату на следующий понедельник
                    $nextMonday = strtotime('next Monday', $timestamp);
                    // Дата находится в будущем. День недели - выходной. Перенесено на " . date("Y-m-d", $nextMonday);
                    return date("Y-m-d", $nextMonday);
                } else {
                    return $specifiedDate;
                }
            } else {
                $dayOfWeek = date('w', $timestamp);

                if ($dayOfWeek == 6) { // Пятница
                    // Перемещаем дату на следующий понедельник
                    $nextMonday = strtotime('next Monday', $timestamp);
                    return date("Y-m-d", $nextMonday);
                } else {
                    return "Сегодня";
                }
            }
        }
    }

    //Определяем через сколько наступит дата
    public static  function checkWeek($specifiedDate) {
        $specifiedTimestamp = strtotime($specifiedDate);

        if ($specifiedTimestamp === false) {
            return "Некорректный формат даты!";
        } else {
            $currentWeekNumber = date('W'); // Номер текущей недели
            $specifiedWeekNumber = date('W', $specifiedTimestamp); // Номер недели указанной даты

            if ($specifiedWeekNumber == $currentWeekNumber) {
                return "Дата находится на текущей неделе.";
            } elseif ($specifiedWeekNumber == ($currentWeekNumber + 1)) {
                return "Дата находится на следующей неделе.";
            } else {
                return "Дата находится через две недели и более.";
            }
        }
    }

    //Определяем дату начала недели
    public static  function getStartOfWeekFromDate($date) {
        $currentDayOfWeek = date('N', strtotime($date)); // Номер текущего дня недели (1 - понедельник, 7 - воскресенье)

        if ($currentDayOfWeek >= 2 && $currentDayOfWeek <= 5) {
            // Если текущий день недели вторник-пятница, то начало недели - текущий понедельник
            $startOfPreviousWeek = strtotime("this week", strtotime($date));
            return date("Y-m-d", $startOfPreviousWeek);

        } elseif ($currentDayOfWeek == 1) {
            // Если текущий день недели понедельник, то начало недели - предыдущий понедельник
            return $date;
        } else {
            // Если текущий день недели суббота или воскресенье, то начало недели - прошедший понедельник
            $daysToSubtract = $currentDayOfWeek + 6;

            // Вычисляем таймстамп начала текущей недели и вычитаем нужное количество дней
            $startOfCurrentWeek = strtotime("last Monday", strtotime($date));
            $startOfPreviousWeek = $startOfCurrentWeek - ($daysToSubtract * 24 * 60 * 60); // 1 день = 24 часа * 60 минут * 60 секунд

            return date("Y-m-d", $startOfPreviousWeek);
        }

    }

    //Определяем дату предыдущей недели
    public static  function getStartOfPreviousWeek($date) {
        $currentDayOfWeek = date('N', strtotime($date)); // Номер текущего дня недели (1 - понедельник, 7 - воскресенье)

        if ($currentDayOfWeek >= 2 && $currentDayOfWeek <= 5) {
            // Если текущий день недели вторник-пятница, то начало недели - текущий понедельник
            $daysToSubtract = $currentDayOfWeek - 1;
        } elseif ($currentDayOfWeek == 1) {
            // Если текущий день недели понедельник, то начало недели - предыдущий понедельник
            $daysToSubtract = 7;
        } else {
            // Если текущий день недели суббота или воскресенье, то начало недели - прошедший понедельник
            $daysToSubtract = $currentDayOfWeek + 6;
        }

        // Вычисляем таймстамп начала текущей недели и вычитаем нужное количество дней
        $startOfCurrentWeek = strtotime("last Monday", strtotime($date));
        $startOfPreviousWeek = $startOfCurrentWeek - ($daysToSubtract * 24 * 60 * 60); // 1 день = 24 часа * 60 минут * 60 секунд

        return date("Y-m-d", $startOfPreviousWeek);
    }

    public static  function NewFormatDateBP ($date) {
        $objDateTime = new DateTime($date);
        return $objDateTime->format("d.m.Y H:i:s");
    }

}

class SmartBPFunctions {
    public static function getSmartsByFilter($smartID,$filter){
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($smartID);
        if (array_key_exists('ASSIGNED_BY_ID',$filter)) {
            $assignedID = $filter["ASSIGNED_BY_ID"];
            unset($filter["ASSIGNED_BY_ID"]);
        }
        $items = $factory->getItems([
            'filter' => $filter,
            'select' => ['*'],
            'order' => ['ID' => 'ASC'],
        ]);
        foreach ($items as $item) {
            if ($item["ASSIGNED_BY_ID"] != $assignedID) continue;
            $els[] = $item->getData();
        }
        return $els;
    }
}

class TasksBPFunctions {
    public static function createTask($arFields,$UserID = 128) {
        \CModule::IncludeModule("tasks");

        $newTask = \Bitrix\Tasks\Access\Model\TaskModel::createFromRequest($arFields);
		$oldTask = \Bitrix\Tasks\Access\Model\TaskModel::createNew($newTask->getGroupId());

		$mgrResult = \Bitrix\Tasks\Manager\Task::add($UserID, $arFields);

        if ($arFields["REPORT"] == "Y" && $mgrResult["DATA"]["ID"] > 0 ) {
            \Bitrix\Tasks\Internals\Task\ParameterTable::add(['TASK_ID' => $mgrResult["DATA"]["ID"], 'CODE' => 3, 'VALUE' => 'Y']);
        }

        if ($mgrResult["DATA"]["ID"] > 0 ) {
            return $mgrResult["DATA"]["ID"];
        }
        else {
            return $mgrResult;
        }

        prToFile("Поля");
        prToFile($arFields);
        prToFile("Результат");
        prToFile($mgrResult);
		
    }

    public static function updateTask($ID,$arFields,$UserID = 128,$mute = false) {
        \CModule::IncludeModule("tasks");
        if ($mute === false) {
            $res = \CTaskItem::getInstance($ID,$UserID)->Update($arFields);
        }
        else {
            \Bitrix\Tasks\Internals\TaskTable::update($ID,$arFields);
        }
    }

    public static function addCommentTask($taskId,$userId = 128,$messageText) {
        if ( \Bitrix\Main\Loader::includeModule('tasks') && \Bitrix\Main\Loader::includeModule('forum') )
        {
            $result = \Bitrix\Tasks\Integration\Forum\Task\Comment::add($taskId, [
                'AUTHOR_ID' => $userId,
                'POST_MESSAGE' => $messageText,
            ]);
        }
    }

    public static function closeTask($taskId,$UserID = 128,$read = false) {
        \Bitrix\Main\Loader::includeModule('tasks');

        if (strpos($UserID,"user_") || strpos($UserID,"User_")) {
            $UserID = str_replace(["User_","user_"],"",$UserID);
        }

        $task = \CTaskItem::getInstance($taskId, $UserID);
        $task->complete();

        if ($read === true) {
            \Bitrix\Main\Loader::includeModule('im');

            $query = \Bitrix\Im\Model\MessageParamTable::query()
                ->addSelect("MESSAGE_ID")
                ->setFilter(["PARAM_VALUE" => "taskId", "PARAM_VALUE" => $taskId])
                ->exec();
            $params = $query->fetchAll();
            foreach ($params as $param) {
                \Bitrix\Im\Model\MessageTable::update($param["MESSAGE_ID"],["NOTIFY_READ" => "Y"]);
            } 

            $query = \Bitrix\Tasks\Internals\Counter\CounterTable::query()
            ->addSelect("ID")
            ->setFilter(["TYPE" => 'my_new_comments',"TASK_ID" => $taskId])
            ->exec();
            $scores = $query->fetchAll();
            
            foreach ($scores as $scorer) {
                \Bitrix\Tasks\Internals\Counter\CounterTable::delete($scorer["ID"]);
            }   
        }
        
    }

}