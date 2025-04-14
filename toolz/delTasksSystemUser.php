<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('tasks');
\Bitrix\Main\Loader::includeModule('socialnetwork');

($_REQUEST['QUAN']) ? $quan = $_REQUEST['QUAN'] : $quan = 100;
($_REQUEST["TITLE"]) ? $reqName = $_REQUEST["TITLE"] : $reqName = "*";

//Названия
/*$res = \Bitrix\Tasks\TaskTable::query()
    ->addSelect(new \Bitrix\Main\Entity\ExpressionField('TITLES',
        'DISTINCT %s', array('TITLE')
    ))
    ->exec();

$rows = $res->fetchAll();

foreach ($rows as $row) {
    $names[] = $row;
}

d($names);*/


//Элементы

if ($_REQUEST['QUAN']) {

    $pos = strpos($_REQUEST["TITLE"],'%');

/*     if ($pos > 0) {
        $res = \Bitrix\Tasks\TaskTable::query()
        ->setSelect(['ID','CREATED_DATE','TITLE','CREATED_BY','RESPONSIBLE_ID','GROUP_ID'])
        ->whereLike('TITLE', $_REQUEST["TITLE"])
        ->setLimit($_REQUEST['QUAN'])
        ->exec();
    }
    elseif (strlen($_REQUEST["TITLE"] > 1) && $pos == 0){
        $res = \Bitrix\Tasks\TaskTable::query()
        ->setSelect(['ID','CREATED_DATE','TITLE','CREATED_BY','RESPONSIBLE_ID','GROUP_ID'])
        ->whereIn('TITLE', $_REQUEST["TITLE"])
        ->setLimit($_REQUEST['QUAN'])
        ->exec();
    }
    else { */
        $res = \Bitrix\Tasks\TaskTable::query()
        ->setSelect(['ID','CREATED_DATE','TITLE','CREATED_BY','RESPONSIBLE_ID','GROUP_ID'])
        ->setFilter(["CREATED_BY" => 406,"RESPONSIBLE_ID" =>406])
        ->setLimit($_REQUEST['QUAN'])
        ->exec();
   // }

    $rows = $res->fetchAll();

    foreach ($rows as $row) {
        $createdFio = CUser::GetByID($row["CREATED_BY"])->Fetch()['NAME'];
        $responseFio = CUser::GetByID($row["RESPONSIBLE_ID"])->Fetch()['NAME'];

        $res = Bitrix\Socialnetwork\WorkgroupTable::query()
            ->setSelect(['ID','NAME'])
            ->where('ID', $row["GROUP_ID"])
            ->exec();

        $group = $res->fetch()["NAME"];

        $row["CREATED_DATE"] = $row["CREATED_DATE"]->format("d.m.Y H:i:s");
        $row["CREATED_BY"] = $createdFio;
        $row["RESPONSIBLE_ID"] = $responseFio;
        $row["GROUP_ID"] = $group;
        $els[] = $row;
    }
}

if ($_REQUEST['DEL'] == "Y") {
    $tm = new \Bitrix\Tasks\Control\Task(1);
    foreach ($els as $el) {
        $tm->delete($el["ID"]);
    }
    echo 'Удаление '.$_REQUEST['QUAN'].' позиций завершено';
}
else {
    +Kint::dump($els);
}

?>

<form name="PARAMS" action="">
    <label for="TITLE">Название задачи
        <input type="text" name="TITLE" placeholder="Название задачи" <?=($_REQUEST['TITLE']) ? 'value="'.$_REQUEST['TITLE'].'"':''?>/>
    </label>
    <label for="TITLE">Кол-во элементов для выдачи/удаления
        <input type="text" name="QUAN" placeholder="Кол-во" <?=($_REQUEST['QUAN']) ? 'value="'.$_REQUEST['QUAN'].'"':''?>/>
    </label>
    <button name="SHOW" type="submit" value="Y">Показать</button>
    <button name="DEL" type="submit" value="Y">Удалить</button>
</form>
