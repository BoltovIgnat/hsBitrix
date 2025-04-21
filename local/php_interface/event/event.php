<?php
$eventManager = Bitrix\Main\EventManager::getInstance();

/* $eventManager->addEventHandler(
    'imopenlines',
    '\Bitrix\ImOpenLines\Model\Session::onAfterAdd',
    [
        '\Hs\OpenLines',
        'onAfterSessionAdd'
    ]
); */

$eventManager->addEventHandler(
    "main",
    "OnProlog",
    [
        '\Hs\Helper',
        'AddBtn'
    ]
);

$eventManager->addEventHandler(
    'tasks',
    '\Bitrix\Tasks\Internals\Task\SearchIndex::onAfterAdd',
    [
        '\Hs\Search',
        'onAfterTasksIndexAdd'
    ]
);

$eventManager->addEventHandler(
    'tasks',
    '\Bitrix\Tasks\Internals\Task\SearchIndex::onAfterUpdate',
    [
        '\Hs\Search',
        'onAfterTasksIndexUpdate'
    ]
);

$eventManager->addEventHandler(
    'crm',
    'OnBeforeCrmDealAdd',
    [
        '\Hs\Holding',
        'onDealAddCompanyLink'
    ]
);

$eventManager->addEventHandler(
    'crm',
    'OnBeforeCrmDealUpdate',
    [
        '\Hs\Holding',
        'onDealCompanyLink'
    ],
    200
);  

$eventManager->addEventHandler(
    'crm',
    'OnAfterCrmContactAdd',
    [
        '\Hs\Holding',
        'onContactAddCompanyLink'
    ]
);

$eventManager->addEventHandler(
    'crm',
    'OnBeforeCrmContactUpdate',
    [
        '\Hs\Holding',
        'onContactCompanyLink'
    ]
);

$eventManager->addEventHandler(
    'crm',
    '\Bitrix\Crm\Relation\EntityRelation::onAfterDelete',
    [
        '\Hs\Holding',
        'onAfterRelationDelete'
    ]
);

$eventManager->addEventHandler(
    'crm',
    '\Bitrix\Crm\Relation\EntityRelation::onAfterAdd',
    [
        '\Hs\Holding',
        'onAfterRelationAdd'
    ]
);

//$eventManager->addEventHandler(
//    'main',
//    'OnBeforeUserLogin',
//    [
//        '\Hs\UserEvents',
//        'OnBeforeUserLoginHandler'
//    ]
//);

$eventManager->addEventHandler(
    'crm',
    'OnBeforeCrmLeadUpdate',
    [
        '\Dbbo\Event\LeadEvent',
        'onBeforeCrmLeadUpdate'
    ]
);

$eventManager->addEventHandler(
    'crm',
    'OnBeforeCrmContactUpdate',
    [
        '\Dbbo\Event\ContactEvent',
        'OnBeforeCrmContactUpdate'
    ]
);

$eventManager->addEventHandler(
    'crm',
    'OnBeforeCrmCompanyUpdate',
    [
        '\Dbbo\Event\ContactEvent',
        'OnBeforeCrmContactUpdate'
    ]
);


$eventManager->addEventHandler(
    'crm',
    'OnBeforeCrmLeadAdd',
    [
        '\Dbbo\Event\LeadEvent',
        'onBeforeCrmLeadAdd'
    ]
);

$eventManager->addEventHandler(
    'crm',
    'OnAfterCrmLeadAdd',
    [
        '\Dbbo\Event\LeadEvent',
        'onAfterCrmLeadAdd'
    ]
);

$eventManager->addEventHandler(
    'crm',
    'OnBeforeCrmDealUpdate',
    [
        '\Dbbo\Event\DealEvent',
        'onBeforeCrmDealUpdate'
    ],
    100
);

$eventManager->addEventHandler(
    'crm',
    'OnAfterCrmDealUpdate',
    [
        '\Dbbo\Event\DealEvent',
        'onAfterCrmDealUpdate'
    ],
    100
);


/*
Запуск БП Дожим в оплату
$eventManager->addEventHandler(
    'crm',
    'OnActivityUpdate',
    [
        '\Dbbo\Event\ActivityEvent',
        'onActivityUpdate'
    ]
); */