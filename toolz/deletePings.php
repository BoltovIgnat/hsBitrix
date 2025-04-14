<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('tasks');
$query = \Bitrix\Tasks\Internals\TaskTable::query()
->setSelect(["ID","CREATED_BY","CLOSED_BY", "RESPONSIBLE_ID"])
->setFilter(["CREATED_BY" => 406])
->whereNotNull("CLOSED_BY")
->exec();
$tasks = $query->fetchAll();

foreach ($tasks as $task) {
    $ids[] = $task;
}

if ($_REQUEST["PROCCESS"] ="Y") {
    foreach ($ids as $id) {
        \Bitrix\Main\Loader::includeModule('im');
        $query = \Bitrix\Im\Model\MessageParamTable::query()
            ->setSelect(["MESSAGE_ID"])
            ->setFilter(["PARAM_VALUE" => "taskId", "PARAM_VALUE" => $id["ID"]])
            ->exec();
        $params = $query->fetchAll();

        foreach ($params as $param) {
            \Bitrix\Im\Model\MessageTable::update($param["MESSAGE_ID"],["NOTIFY_READ" => "Y"]);
        } 

        $query = \Bitrix\Tasks\Internals\Counter\CounterTable::query()
        ->addSelect("ID")
        ->setFilter(["TASK_ID" => $id["ID"]])
        ->exec();
        $scores = $query->fetchAll();
        
        foreach ($scores as $scorer) {
            \Bitrix\Tasks\Internals\Counter\CounterTable::delete($scorer["ID"]);
        }
    }
}
?>
<a href="?PROCCESS=Y">Выполнить чтение пингов (комментариев)</a>
<?
+Kint::Dump($ids);


