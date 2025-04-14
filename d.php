<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");


use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;

/* \IIT\MailSyncModule\MailSync::syncMailbox(87); */

$items = \CCrmFieldMulti::GetListEx(["ELEMENT_ID" => "ASC"], [
    'VALUE' => "e.vasiljeva@grandline.ru",
    'TYPE_ID' => 'EMAIL',
    'ENTITY_ID' => ["CONTACT","COMPANY"]
], false, false, [], []);

while ($item = $items->fetch()) {	
        if($item["ENTITY_ID"] == "COMPANY") {
            $arCompany = $item;
        }
        if($item["ENTITY_ID"] == "CONTACT") {
            $arContact = $item;
        }
}

echo '<pre>'; print_r($arCompany); echo '</pre>';
echo '<pre>'; print_r($arContact); echo '</pre>';


//echo '<pre>'; print_r($arCompany); echo '</pre>';



//\Dbbo\Agent\BPAgents::openProjectsStartBP();

/* \Bitrix\Main\Loader::includeModule('iblock');
$res = CIBlockElement::GetList([], [
        'IBLOCK_ID' => 53,
        'PROPERTY_PROEKT' => 1013
        ],
    false,
    false,
    ["ID","NAME","PROPERTY_REZULTAT_SOGLASOVANIYA","PROPERTY_PRICHINA_OTKAZA"]
);

while ($el = $res->fetch()) {
    $el["REZULTAT_SOGLASOVANIYA"] = $el["PROPERTY_REZULTAT_SOGLASOVANIYA_VALUE"];
    $el["OTKAZ"] = $el["PROPERTY_PRICHINA_OTKAZA_VALUE"];
    $els[] = $el;
}

foreach($els as $el) {
    echo "ID: " . $el['ID'] . "<br>";
    echo "Название: " . $el['NAME'] . "<br>";
    echo ($el['REZULTAT_SOGLASOVANIYA']) ? 'Статус: ' . $el['REZULTAT_SOGLASOVANIYA'] . "<br>" : "";
    echo  ($el['OTKAZ']) ? 'Причина отказа: ' . $el['OTKAZ'] . "<br>" : "";

    echo "<hr>";
}
 */



/* $res = \Bitrix\Crm\RequisiteTable::query()
    ->setSelect(['ID', 'RQ_COMPANY_NAME', 'RQ_INN', "ENTITY_ID", "RQ_FIRST_NAME", "RQ_LAST_NAME", "RQ_SECOND_NAME"])
    ->setFilter(["ENTITY_TYPE_ID" => 4,"PRESET_ID" => 3])
    ->exec();
$rows = $res->fetchAll();

foreach ($rows as $req) {
    if (!empty($req["RQ_LAST_NAME"])) {
        \Bitrix\Crm\RequisiteTable::update($req["ID"],["NAME" => "ИП"." ".$req["RQ_LAST_NAME"]." ".$req["RQ_FIRST_NAME"]." ".$req["RQ_SECOND_NAME"]]);
    }
} */

//\IIT\MailSyncModule\MailSync::syncAllMailboxes();


/* \Bitrix\Main\Loader::includeModule('mail');

$selectResult = \CMailbox::getList([], ["ACTIVE" => "Y"]);
while ($mailbox = $selectResult->fetch()) {
    $mailboxes[] = $mailbox;
}

foreach ($mailboxes as $key => $mailbox) {
    $query = \Bitrix\Mail\MailFilterTable::query()
    ->setSelect(["MAILBOX_ID","ACTION_PHP"])
    ->setFilter([
        "MAILBOX_ID" => $mailbox["ID"],
    ])
    ->exec();
    $action = $query->fetch()["ACTION_PHP"];

    if (strstr($action,"activity.php")) {
        $mailboxes[$key]["FILTER"] = "Y";
    }
    else unset($mailboxes[$key]);
}

echo '<pre>'; print_r($mailboxes); echo '</pre>'; */





//\Dbbo\Agent\BPAgents::openProjectsStartBP();

/* $hasStarted = (\CIblockElement::GetList(["ID" => "ASC"], ["IBLOCK_ID" => 52, "=NAME" => "openProjectsStartBP", "PROPERTY_DATE_VALUE" => (new DateTime('now'))->format('d.m.Y') ], false, false, ["ID","NAME"])->Fetch()["ID"] > 0);

echo '<pre>'; print_r($hasStarted); echo '</pre>'; */

/* $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(130);
$items = $factory->getItems([
    'filter' => ["UF_CRM_5_1683047652" => ""],
    'select' => ['ID'],
]);

foreach ($items as $item) {

    $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
    ->addSelect("SRC_ENTITY_ID")
    ->setFilter([
        "SRC_ENTITY_TYPE_ID" => \CCrmOwnerType::Deal,
        //"SRC_ENTITY_ID" => ,
        "DST_ENTITY_TYPE_ID" => 130,
        "DST_ENTITY_ID" => $item["ID"]
    ])
    ->exec();
    $diDeal = $query->fetch()["SRC_ENTITY_ID"];

        $dbDeal = \CCrmDeal::GetListEx(
            [
                'ID' => 'DESC'
            ],
            [
                'ID' => $diDeal,
                'CHECK_PERMISSIONS' => 'N',
                //'STAGE_ID' => DEALOPENEDSTAGES
            ],
            false,
            false,
            ['ID','UF_CRM_1639645454','UF_CRM_1667466360305']
        );

        if($res = $dbDeal->Fetch()) {
            if (!empty($res["UF_CRM_1667466360305"])) {
                $item->set("UF_CRM_5_1683047652", $res["UF_CRM_1667466360305"]);
                $item->save();
            }
           /*  //id заказа  
            //$item->set("UF_CRM_5_1683046543", $res["UF_CRM_1639645454"]);
            //ссылка на заказ
            $item->set("UF_CRM_5_1683047652", $res["UF_CRM_1667466360305"]);
            $item->set("TITLE", $res["UF_CRM_1639645454"]); 
            
        }
} */




//$res = resendEmail($message,604,"m.o.r.f@mail.ru",10111,$type=1);


/* $res = \Dbbo\Mail::Send("a.larkin@highsystem.ru","Тест проверки","Тест",false,'php script');
echo '<pre>'; print_r($res); echo '</pre>'; */

/* $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(CRM_SMART["holding"]);
$items = $factory->getItems([
    'filter' => [],
    'select' => ['ID','COMPANY_ID'],
]);

foreach ($items as $item) {

    $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
    ->addSelect("DST_ENTITY_ID")
    ->setFilter([
        "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
        "SRC_ENTITY_ID" => $item["ID"],
        "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
        //"DST_ENTITY_ID" => 
    ])
    ->exec();
    $companiesWithHolding = $query->fetchAll();

    foreach ($companiesWithHolding as $ch) {

        $dbDeal = \CCrmDeal::GetListEx(
            [
                'ID' => 'DESC'
            ],
            [
                'COMPANY_ID' => $ch['DST_ENTITY_ID'],
                'CHECK_PERMISSIONS' => 'N',
                //'STAGE_ID' => DEALOPENEDSTAGES
            ],
            false,
            false,
            ['ID']
        );
    
        while($res = $dbDeal->Fetch()) {
            $deals[$ch["DST_ENTITY_ID"]][] = $res;
                $fields = ["PARENT_ID_".CRM_SMART["holding"] => $item["ID"]];
                (new \CCrmDeal(false))->update($res["ID"],$fields);
        }
    }

} */

/* $res = \Dbbo\Mail::Send("alexlarkin@mail.ru","Тест проверки","Тест",false);

echo '<pre>'; print_r($res); echo '</pre>'; */

/*
$dbDeal = \CCrmDeal::GetListEx(
    [
        'ID' => 'DESC'
    ],
    [
        'ID' => intval(138297),
        'CHECK_PERMISSIONS' => 'N',
        'STAGE_ID' => DEALOPENEDSTAGES
    ],
    false,
    false,
    ['ID','LEAD_ID', 'STAGE_ID', "ASSIGNED_BY_ID", 'UF_CRM_6318FC326D01B', 'UF_CRM_6318FC334D52D']
);

if($res = $dbDeal->Fetch()) {
echo '<pre>'; print_r($res); echo '</pre>';
} */	


/* $arFields = [
    "FM" => [
        "PHONE" => [
            "n0" => [
                "VALUE" => "+74956200221",
                "VALUE_TYPE" => "WORK"
            ]
        ],
        "EMAIL" => [
            "n0" => [
                "VALUE" => "kuznetsov@tpprf.ru",
                "VALUE_TYPE" => "WORK"
            ]
        ],
    ]
];


(new \CCrmLead(false))->Update(280822,$arFields); */

/* 
$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(CRM_SMART["project"]);
$items = $factory->getItems([
    'filter' => [],
    'select' => ['ID','COMPANY_ID'],
]);

foreach ($items as $item) {
    //echo '<pre>'; print_r($item["COMPANY_ID"]); echo '</pre>';

    $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
    ->addSelect("SRC_ENTITY_ID")
    ->setFilter([
        "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
        //"SRC_ENTITY_ID" => $eventFields["SRC_ENTITY_ID"],
        "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
        "DST_ENTITY_ID" => $item["COMPANY_ID"]
    ])
    ->exec();
    $exist = $query->fetchAll();

    if (!empty($exist)) {
        $item->set("PARENT_ID_".CRM_SMART["holding"], $exist[0]["SRC_ENTITY_ID"]);
        $item->save();
    }
    
} */

/* 
CPullWatch::AddToStack('PULL_TEST',
    Array(
        'module_id' => 'hs_lead_detail',
        'command' => 'update_comment',
        'params' => ["LEAD_ID" => $arFields["ID"],"COMMENT" => $comment ],
    )
);
 */
//ini_set('error_reporting', E_ALL);
/* \Bitrix\Main\Loader::includeModule('im');

\IIT\MailSyncModule\MailSync::syncMailbox(87);
echo '123'; */
/* 
$res = \Bitrix\Im\Model\MessageTable::query()
->setSelect(['ID','MESSAGE','DATE_CREATE','AUTHOR_ID'])
->setFilter([">DATE_CREATE" => "13.03.2024","AUTHOR_ID" => 96])
->whereLike('MESSAGE', "%Контроль проекта:%")
->setLimit(10000)
->exec();
$rows = $res->fetchAll();

foreach ($rows as $row) {
    \Bitrix\Im\Model\MessageTable::delete(["ID" => $row["ID"]]);
} 
 */
/* +Kint::dump($rows); */

/* $date_from = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime("-1 day"));

$res = \Bitrix\Tasks\TaskTable::query()
->setSelect(['ID','CREATED_DATE','TITLE','CREATED_BY','RESPONSIBLE_ID','GROUP_ID'])
->setFilter([">CREATED_DATE" => "13.03.2024"])
->whereLike('TITLE', "Контроль проекта:%")
//->setLimit(1000)
->exec();

$rows = $res->fetchAll();

foreach ($rows as $row) {
    $row["CREATED_DATE"] = $row["CREATED_DATE"]->format("d.m.Y H:i:s");
    $els[] = $row;
}

$tm = new \Bitrix\Tasks\Control\Task(1);
foreach ($els as $el) {
    $tm->delete($el["ID"]);
} 

//+Kint::dump($els); */

/* $rsUsers = \CUser::GetList(($by="ID"), ($order="desc"), ["ID" => 1396],["SELECT" =>["UF_DEPARTMENT"],"FIELDS" =>["ID","NAME","LAST_NAME"]]);
while($userRes = $rsUsers->GetNext()) {
    echo '<pre>'; print_r($userRes); echo '</pre>';
} */


//\Dbbo\Agent\AgentLead::checkStartBPForLeads();

//\Bitrix\Main\Loader::includeModule('crm');

/* $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(161);
$items = $factory->getItems([
    'filter' => ['ID' => 539],//тут задаем фильтр для выборки, можно по полям элемента
    'select' => ['*'],//Какие поля получить, можно указать * если нужны все
    'order' => ['ID' => 'ASC'],//Указываем поле по которому будет сортироваться выборка и направление сортировки
]);
echo '<pre>'; print_r($items[0]->getData()); echo '</pre>'; */


/* $fields = [
    "ASSIGNED_BY_ID" => 603
];
\BPFunctions::editCrmElement(161,539,$fields); */



/* $entityTypeID = 161;
$entityID = 642;
$userID = 603;

$addObserver = \BPFunctions::addObserver($entityTypeID,$entityID,$userID); */

/* 
$leadId = 126973;
$companyLead = '';
$contactsIdLead = '';
$clientName = 'Иван';
$clientLastName = 'Иванов';
$clientPhone = '+79264990734';
$clientMail = '123@yandex.ru';
$source = 'EMAIL';
$userROP = '';

$smartIdReanimation = 136;

$fields = [
    'TITLE' => "333 {$leadId}",                               //Название элемента
    'STAGE_ID' => 'DT136_17:2',                                         //Стадия - Взять в работу
    'PARENT_ID_1' => $leadId,                                           //Выбранный РОП
    'COMPANY_ID' => $companyLead,                                       //Компания в лиде
    'CONTACT_ID' => $contactsIdLead,                                    //Контакты в лиде
    'CREATED_BY' => 406,                                                //Создан под пользователем                                     //Руководитель сотрудника
    'UF_CRM_13_1701870663' => $clientName,                              //Имя клиента
    'UF_CRM_13_1701870707' => $clientLastName,                          //Фамилия клиента
    'UF_CRM_13_1701870718' => $clientPhone,                             //Номер телефона клиента
    'UF_CRM_13_1701870734' => $clientMail,                              //Адрес электронной почты клиента
    'UF_CRM_13_SOURCE' => $source,                                      //Источник лида
];

$createSmartDocument = \BPFunctions::addCrmElement($smartIdReanimation, $fields);         //Создание элемента смарт процесса
var_dump($createSmartDocument); */


/* 
$arFilter = ["STAGE_ID" => "UC_AMPHT1", "=IS_RECURRING" =>"N","CHECK_PERMISSIONS" => "N",">=DATE_CREATE" => "01.11.2023","<=DATE_CREATE" => "30.11.2023"];
$dbResult = CCrmDeal::GetListEx(
    ["ID" => "DESC"],
    $arFilter,
    false,
    false,
    ["ID"],
    false
);

while($arDeal = $dbResult->GetNext())
{
    $els[] = $arDeal["ID"];
}

foreach ($els as $key => $el) {
    $wfId = CBPDocument::StartWorkflow(
        380,
         [ "crm", "CCrmDocumentDeal", "DEAL_".$el ],
         [ "TargetUser" => "user_128" ],
         $arErrorsTmp
     );   
     prToFile($el);
}

 */


/* $filter = [
    "ASSIGNED_BY_ID" =>578,
    "STAGE_ID" =>"DT168_15:PREPARATION"
];
$smarts = SmartBPFunctions::getSmartsByFilter(168,$filter);

echo '<pre>'; print_r(count($smarts)); echo '</pre>'; */

/* $fields = ["TITLE" => '123'];
$res =  \BPFunctions::addCrmElement(187,$fields);

echo '<pre>'; print_r($res); echo '</pre>'; */


/* $entityType = CCrmOwnerType::ResolveName(187);
$documentId = $entityType . '_' . 1;

$fields["ASSIGNED_BY_ID"] = 8;

$updateResult = \Bitrix\Crm\Integration\BizProc\Document\Dynamic::UpdateDocument($documentId, $fields, 1);
if (is_string($updateResult))
{
    $errors->setError(new Error($updateResult));
}

return $errors; */

/* Loader::includeModule('mail');

//\CCrmOwnerType::Lead
//\CCrmOwnerType::Deal
$arFilter = ["COMPANY_ID" => 40484, "=IS_RECURRING" =>"N","CHECK_PERMISSIONS" => "N"];
$dbResult = CCrmDeal::GetListEx(
    ["ID" => "DESC"],
    $arFilter,
    false,
    false,
    ["*"],
    false
);
while($arDeal = $dbResult->GetNext())
{
    $els[] = $arDeal;
}
echo count($els); */

/*$elementsIds = [116,117,118,123,122,6,7,22];
$entityTypeID = \CCrmOwnerType::Deal;

$res = getOpenClosedCRMEntities(false,$entityTypeID);

function getOpenClosedCRMEntities($elementsIds = false, $entityTypeID){
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

}*/


/*$elementsIds = [116,117,118,123,122,6,7,22];
$res = getOpenClosedSmartProccesses($elementsIds,174);

function getOpenClosedSmartProccesses($elementsIds=false,$smartID) {
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
}*/

/*//\CCrmOwnerType::Lead
//\CCrmOwnerType::Deal
$entityID = 114821;
$userID = 86;
$entityTypeID = \CCrmOwnerType::Deal;

addObserver($entityTypeID,$entityID,$userID);
delObserver($entityTypeID,$entityID,$userID);

function addObserver($entityTypeID,$entityID,$userID)
{
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

function delObserver($entityTypeID,$entityID,$userID)
{
    Bitrix\Crm\Observer\Entity\ObserverTable::delete(
        ["ENTITY_TYPE_ID" => $entityTypeID,"ENTITY_ID" => $entityID,"USER_ID" => $userID],
    );
}
function setObserver($entityTypeID,$entityID,$userID)
{
    $res = Bitrix\Crm\Observer\Entity\ObserverTable::query()
        ->setSelect(['USER_ID', 'ENTITY_ID', 'ENTITY_TYPE_ID'])
        ->setFilter(['ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID])
        ->exec();
    $observerID = $res->fetch()["USER_ID"];

    if (!$observerID) {
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
    } else {
        Bitrix\Crm\Observer\Entity\ObserverTable::update(
            ["ENTITY_TYPE_ID" => $entityTypeID,"ENTITY_ID" => $entityID,"USER_ID" => $observerID],
            [
                "USER_ID" => $userID,
                "LAST_UPDATED_TIME" => new \Bitrix\Main\Type\DateTime()
            ]
        );
    }
}

echo '<pre>'; print_r($rows); echo '</pre>';*/



/*
$rsFile = CFile::GetFileArray(636278);
$attachment = MailMessageAttachmentTable::getList([
    'filter' => ['MESSAGE_ID' => 591136]
])->fetchAll();
$filetype = mb_substr($attachment[0]["FILE_NAME"],-4);
echo '<pre>'; print_r($filetype); echo '</pre>';*/

//\CCrmOwnerType::Lead
//\CCrmOwnerType::Deal
/* global $USER;
$USER->Authorize(1);

BPFunctions::addComment('Тест добавления комментария',604,\CCrmOwnerType::Deal,124456);

$arFields[] =
(new CCrmDeal(false))->Update(); */

/*\Bitrix\Main\Loader::includeModule('tasks');
\Bitrix\Main\Loader::includeModule('socialnetwork');

$res = \Dbbo\Sbis\SbisAgent::Check(5);*/

/*$res = \Bitrix\Crm\RequisiteTable::query()
    ->setSelect(['ID', 'RQ_COMPANY_NAME', 'RQ_INN', "ENTITY_ID"])
    ->setFilter(["ENTITY_TYPE_ID" => 4,"PRESET_ID" => 3])
    ->exec();
$rows = $res->fetchAll();

foreach ($rows as $req) {

$res = \Bitrix\Crm\CompanyTable::query()
->setSelect(['ID', 'TITLE',"UF_CRM_1697786836"])
->setFilter(["UF_CRM_1697786836" => false,"UF_CRM_1697786836" => " ","UF_CRM_1697786836" <= 0])
//->addFilter("=UF_CRM_1697786836",0)
->exec();
$companies = $res->fetchAll(); 

foreach ($companies as $key => &$company) {
    $query = \Bitrix\Crm\RequisiteTable::query()
    ->setSelect(["ID","NAME","RQ_INN","RQ_KPP"])
    ->where("ENTITY_ID",$company["ID"])
    ->where("PRESET_ID",1)
    ->exec();
    $reqs = $query->fetchAll();
    foreach ($reqs as $req) {
        $company["REQUSITES"][] = $req;
    }
}

 foreach ($companies as $company) {
    if ( empty($company["REQUSITES"][0]["RQ_INN"]) ) { continue; }
    \Dbbo\Sbis\Sbis::AddToAgent(['companyId' => $company["ID"]]);
} 

echo '<pre>'; print_r($count); echo '</pre>';
/*

//\CCrmOwnerType::Lead
//\CCrmOwnerType::Deal
$entityID = 114821;
$res = Bitrix\Crm\Observer\Entity\ObserverTable::query()
    ->setSelect(['ID','USER_ID','ENTITY_ID','ENTITY_TYPE_ID'])
    ->setFilter(['ENTITY_TYPE_ID' => \CCrmOwnerType::Deal, $entityID])
    ->exec();
$rows = $res->fetchAll();

echo '<pre>'; print_r($rows); echo '</pre>';*/

/*
 * $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(174);

$items = $factory->getItems([
    'filter' => ['STAGE_ID' => ["DT174_10:1","DT174_10:2","DT174_10:3","DT174_10:CLIENT","DT174_10:PREPARATION","DT174_10:NEW"]],//тут задаем фильтр для выборки, можно по полям элемента
    'select' => ['ID'],//Какие поля получить, можно указать * если нужны все
    'order' => ['ID' => 'ASC'],//Указываем поле по которому будет сортироваться выборка и направление сортировки
]);

foreach ($items as $item) {
    $documentId = ["crm", "Bitrix\Crm\Integration\BizProc\Document\Dynamic", "DYNAMIC_174_".$item["ID"] ];
    $wfId = CBPDocument::StartWorkflow(
        362,
        $documentId,
        [],
        $arErrorsTmp
    );
}
*/


/*
\Bitrix\Main\Loader::includeModule('tasks');

$res = \Bitrix\Tasks\TaskTable::query()
    ->setSelect(['ID','START_DATE_PLAN','TITLE'])
    ->setFilter(['TITLE' => "Необходимо актуализировать данные по проекту",">START_DATE_PLAN" => "01.11.2023 11:50:00"])
    ->exec();
$rows = $res->fetchAll();

$tm = new \Bitrix\Tasks\Control\Task(1);
foreach ($rows as $row) {
    $tm->delete($row["ID"]);
}
*/
