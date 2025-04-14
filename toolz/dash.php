<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$APPLICATION->ShowHead();
$APPLICATION->ShowHeadStrings();
$APPLICATION->ShowHeadScripts();

if ($_REQUEST["AJAX_LOAD"] == "Y") {
    $APPLICATION->restartBuffer();   
}

CJSCore::Init(array("jquery","date"));


$show = ($_REQUEST["show"]) ? ($_REQUEST["show"]) : ["all"];

//d($show);

$refreshTime = ($_REQUEST["refreshtime"]) ? ($_REQUEST["refreshtime"] * 1000) : 0;

$rsUsers = CUser::GetList(($by="ID"), ($order="desc"), ["ACTIVE" => "Y", "UF_DEPARTMENT" => 35],["SELECT" =>["UF_DEPARTMENT"],"FIELDS" =>["ID","NAME","LAST_NAME"]]);
while($userRes = $rsUsers->GetNext()) {
    $userRes["LAST_NAME"] = ($userRes["LAST_NAME"]) ? $userRes["LAST_NAME"] : $userRes["NAME"];
    $team1Users[$userRes["ID"]] = $userRes;
}
if (empty($team1Users)) {
    $team1Users = [0 => "Нет сотрудников"];
}
$rsUsers = CUser::GetList(($by="ID"), ($order="desc"), ["ACTIVE" => "Y", "UF_DEPARTMENT" => 36],["SELECT" =>["UF_DEPARTMENT"],"FIELDS" =>["ID","NAME","LAST_NAME"]]);
while($userRes = $rsUsers->GetNext()) {
    $userRes["LAST_NAME"] = ($userRes["LAST_NAME"]) ? $userRes["LAST_NAME"] : $userRes["NAME"];
    $team2Users[$userRes["ID"]] = $userRes;
}
if (empty($team2Users)) {
    $team2Users = [0 => "Нет сотрудников"];
}

$rsUsers = CUser::GetList(($by="ID"), ($order="desc"), ["ACTIVE" => "Y", "UF_DEPARTMENT" => [35,36]],["SELECT" =>["UF_DEPARTMENT"],"FIELDS" =>["ID","NAME","LAST_NAME"]]);
while($userRes = $rsUsers->GetNext()) {
    $userRes["LAST_NAME"] = ($userRes["LAST_NAME"]) ? $userRes["LAST_NAME"] : $userRes["NAME"];
    $allUsers[$userRes["ID"]] = $userRes;
}


if ($_REQUEST['date_to']) {
    $date_to = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($_REQUEST['date_to']));
    $_SESSION["dash_date_to"] = $date_to;
}
elseif ($_SESSION["dash_date_to"]) {
    $date_to = $_SESSION["dash_date_to"];
}
else {
    $date_to = new \Bitrix\Main\Type\DateTime();
}

if ($_REQUEST['date_from']) {
    $date_from = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($_REQUEST['date_from']));
    $_SESSION["dash_date_from"] = $date_from;
}
elseif ($_SESSION["dash_date_from"]) {
    $date_from = $_SESSION["dash_date_from"];
}
else {
    $date_from = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime("/*-1*/ month"));
}


//$date_to = ($_REQUEST['date_to']) ? \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($_REQUEST['date_to'])) : new \Bitrix\Main\Type\DateTime();
//$date_from = ($_REQUEST['date_from']) ? \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($_REQUEST['date_from'])) : \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime("/*-1*/ month")); 
//$date_to = $_SESSION["dash_date_to"];
//$date_from = $_SESSION["dash_date_from"];


$query = Bitrix\Crm\LeadTable::query()
    ->setSelect(["ID","TITLE","DATE_CREATE","ASSIGNED_BY_ID","STATUS_ID","SOURCE_ID"])
    ->whereBetween("DATE_CREATE", $date_from, $date_to)
    ->setFilter(["SOURCE_ID" => ["CALL","EMAIL","WEB","7","FACE_TRACKER","5"]])
    ->setOrder(["DATE_MODIFY" => "ASC"])
    //->setLimit()
    ->exec();
$leads = $query->fetchAll();

$countTeam1 = 0;
$countTeam2 = 0;
$countTeam1Success = 0;
$countTeam2Success = 0;

foreach ($leads as $lead) {
    if (array_key_exists($lead["ASSIGNED_BY_ID"],$team1Users)) {
        $countTeam1 += 1;
        $leadOnStatus["team1"][$lead["SOURCE_ID"]][] = $lead["ID"];
        $leadOnUser["team1"][$team1Users[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["COUNT"] += 1;
        $leadOnUser["team1"][$team1Users[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]][$lead["SOURCE_ID"]][] = $lead["ID"];
        if ($lead["STATUS_ID"] == "CONVERTED") {
            $countTeam1Success += 1;
            $leadOnStatus["team1"][$lead["SOURCE_ID"]]["SUCCESS"][] = $lead["ID"];
            $leadOnUser["team1"][$team1Users[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["SUCCESS"]["COUNT"] += 1;
            $leadOnUser["team1"][$team1Users[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]][$lead["SOURCE_ID"]]["SUCCESS"][] = $lead["ID"];
        }
    }
    elseif (array_key_exists($lead["ASSIGNED_BY_ID"],$team2Users)) {
        $countTeam2 += 1;
        $leadOnStatus["team2"][$lead["SOURCE_ID"]][] = $lead["ID"];
        $leadOnUser["team2"][$team2Users[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["COUNT"] += 1;
        $leadOnUser["team2"][$team2Users[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]][$lead["SOURCE_ID"]][] = $lead["ID"];
        if ($lead["STATUS_ID"] == "CONVERTED") {
            $countTeam2Success += 1;
            $leadOnStatus["team2"][$lead["SOURCE_ID"]]["SUCCESS"][] = $lead["ID"];
            $leadOnUser["team2"][$team2Users[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["SUCCESS"]["COUNT"] += 1;
            $leadOnUser["team2"][$team2Users[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]][$lead["SOURCE_ID"]]["SUCCESS"][] = $lead["ID"];
        }
    }
}

foreach ($leads as $lead) { 
    if (array_key_exists($lead["ASSIGNED_BY_ID"],$allUsers)) {
        $allUsersLeadsCount[$allUsers[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["TOTAL"] += 1;
        if ($lead["STATUS_ID"] == "CONVERTED") {
            $allUsersLeadsCountSuccess[$allUsers[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["TOTAL"] += 1;
        }
        if ($lead["SOURCE_ID"] == "CALL") {
            $allUsersLeadsCountCALL[$allUsers[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["TOTAL"] += 1;
            if ($lead["STATUS_ID"] == "CONVERTED"){
                $allUsersLeadsCountCALLSUCCESS[$allUsers[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["TOTAL"] += 1;
            }
        }
        if ($lead["SOURCE_ID"] == "EMAIL") {
            $allUsersLeadsCountEMAIL[$allUsers[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["TOTAL"] += 1;
            if ($lead["STATUS_ID"] == "CONVERTED"){
                $allUsersLeadsCountEMAILSUCCESS[$allUsers[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["TOTAL"] += 1;
            }
        }
        if ($lead["SOURCE_ID"] == "WEB") {
            $allUsersLeadsCountWEB[$allUsers[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["TOTAL"] += 1;
            if ($lead["STATUS_ID"] == "CONVERTED"){
                $allUsersLeadsCountWEBSUCCESS[$allUsers[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["TOTAL"] += 1;
            }
        }
        if ($lead["SOURCE_ID"] == "7") {
            $allUsersLeadsCountCARROT[$allUsers[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["TOTAL"] += 1;
            if ($lead["STATUS_ID"] == "CONVERTED"){
                $allUsersLeadsCountCARROTSUCCESS[$allUsers[$lead["ASSIGNED_BY_ID"]]["LAST_NAME"]]["TOTAL"] += 1;
            }
        }
    }
}

if (empty($leadOnUser["team1"])) {
   // $leadOnUser["team1"]["FIO"] = "Нет сотрудников";
    $leadOnUser["team1"]["user"]["COUNT"] = 1;
    $$leadOnUser["team1"]["user"]["SUCCESS"]["COUNT"] = 1;
}

if (empty($leadOnUser["team2"])) {
    //$leadOnUser["team2"]["FIO"] = "Нет сотрудников";
    $leadOnUser["team2"]["user"]["COUNT"] = 1;
    $$leadOnUser["team2"]["user"]["SUCCESS"]["COUNT"] = 1;
}

foreach ($allUsersLeadsCountSuccess as $user => $count) {
    $allUsersLeadsCountSuccessConversion[$user] = ($allUsersLeadsCountSuccess[$user]["TOTAL"] / $allUsersLeadsCount[$user]["TOTAL"] * 100);
}

foreach ($allUsersLeadsCountCALLSUCCESS as $user => $count) {
    $allUsersLeadsCountCALLSUCCESSConversion[$user] = ($allUsersLeadsCountCALLSUCCESS[$user]["TOTAL"] / $allUsersLeadsCountCALL[$user]["TOTAL"] * 100);
}

foreach ($allUsersLeadsCountEMAILSUCCESS as $user => $count) {
    $allUsersLeadsCountEMAILSUCCESSConversion[$user] = ($allUsersLeadsCountEMAILSUCCESS[$user]["TOTAL"] / $allUsersLeadsCountEMAIL[$user]["TOTAL"] * 100);
}

foreach ($allUsersLeadsCountWEBSUCCESS as $user => $count) {
    $allUsersLeadsCountWEBSUCCESSConversion[$user] = ($allUsersLeadsCountWEBSUCCESS[$user]["TOTAL"] / $allUsersLeadsCountWEB[$user]["TOTAL"] * 100);
}

foreach ($allUsersLeadsCountCARROTSUCCESS as $user => $count) {
    $allUsersLeadsCountCARROTSUCCESSConversion[$user] = ($allUsersLeadsCountCARROTSUCCESS[$user]["TOTAL"] / $allUsersLeadsCountCARROT[$user]["TOTAL"] * 100);
}


if(is_array($allUsersLeadsCount)) {
    arsort($allUsersLeadsCount);
}

if(is_array($allUsersLeadsCountSuccess)) {
    arsort($allUsersLeadsCountSuccess);
}

if(is_array($allUsersLeadsCountSuccessConversion)) {
    arsort($allUsersLeadsCountSuccessConversion);
}

if(is_array($allUsersLeadsCountCALL)) {
    arsort($allUsersLeadsCountCALL);
}

if(is_array($allUsersLeadsCountCALLSUCCESS)) {
    arsort($allUsersLeadsCountCALLSUCCESS);
}

if(is_array($allUsersLeadsCountCALLSUCCESSConversion)) {
    arsort($allUsersLeadsCountCALLSUCCESSConversion);
}

if(is_array($allUsersLeadsCountEMAIL)) {
    arsort($allUsersLeadsCountEMAIL);
}

if(is_array($allUsersLeadsCountEMAILSUCCESS)) {
    arsort($allUsersLeadsCountEMAILSUCCESS);
}

if(is_array($allUsersLeadsCountEMAILSUCCESSConversion)) {
    arsort($allUsersLeadsCountEMAILSUCCESSConversion);
}

if(is_array($allUsersLeadsCountWEB)) {
    arsort($allUsersLeadsCountWEB);
}

if(is_array($allUsersLeadsCountWEBSUCCESS)) {
    arsort($allUsersLeadsCountWEBSUCCESS);
}

if(is_array($allUsersLeadsCountWEBSUCCESSConversion)) {
    arsort($allUsersLeadsCountWEBSUCCESSConversion);
}

if(is_array($allUsersLeadsCountCARROT)) {
    arsort($allUsersLeadsCountCARROT);
}

if(is_array($allUsersLeadsCountCARROTSUCCESS)) {
    arsort($allUsersLeadsCountCARROTSUCCESS);
}

if(is_array($allUsersLeadsCountCARROTSUCCESSConversion)) {
    arsort($allUsersLeadsCountCARROTSUCCESSConversion);
}


//+Kint::Dump( $leadOnUser["team1"] );

?>

<?if (!$_REQUEST["AJAX_LOAD"]):?>
    <div class="settingsPanel">
        <form action="<?=$APPLICATION->GetCurPage();?>" method="POST" name="settings" class="settingsForm">
            Дата с: <input type="text" value="<?=$date_from;?>" name="date_from" onclick="BX.calendar({node: this, field: this, bTime: false});">
            Дата по: <input type="text" value="<?=$date_to;?>" name="date_to" onclick="BX.calendar({node: this, field: this, bTime: false});"><br>
            Обновлять каждые, сек (0 не обновлять): <input type="number" name="refreshtime"><br>
            <input type="checkbox" name="show[table1]" <?=(array_key_exists('table1',$show)?'checked':'');?>><span>Общий срез лидов</span>
            <input type="checkbox" name="show[table2]" <?=(array_key_exists('table2',$show)?'checked':'');?>> <span>Конверсия лидов по отделам</span>
            <input type="checkbox" name="show[table3]" <?=(array_key_exists('table3',$show)?'checked':'');?>> <span>Team 1</span>
            <input type="checkbox" name="show[table4]" <?=(array_key_exists('table4',$show)?'checked':'');?>> <span>Team 2</span>
            <input type="checkbox" name="show[tablesTop1]" <?=(array_key_exists('tablesTop1',$show)?'checked':'');?>> <span>Топ Лиды</span>
            <input type="checkbox" name="show[tablesTop2]" <?=(array_key_exists('tablesTop2',$show)?'checked':'');?>> <span>Топ Звонки</span>
            <input type="checkbox" name="show[tablesTop3]" <?=(array_key_exists('tablesTop3',$show)?'checked':'');?>> <span>Топ Почта</span>
            <input type="checkbox" name="show[tablesTop4]" <?=(array_key_exists('tablesTop4',$show)?'checked':'');?>> <span>Топ Корзина</span>
            <input type="checkbox" name="show[tablesTop5]" <?=(array_key_exists('tablesTop5',$show)?'checked':'');?>> <span>Топ Морковь</span>
            <input type="checkbox" name="show[all]" class="checkallbox"> Все
            <button class="button" type="submit">Показать</button>
        </form>
    </div>

    <?/*<div class="bPanel">
        <a href="javascript:;" onclick="showTable(1)">Таблица 1</a>
        <a href="javascript:;" onclick="showTable(2)">Таблица 2</a>
        <a href="javascript:;" onclick="showTable(3)">Таблица 3</a>
        <a href="javascript:;" onclick="showTable(4)">Таблица 4</a>
        <a href="javascript:;" onclick="showTable('all')">Все</a>
        <a href="javascript:;" onclick="refreshDash()">Обновить</a>
    </div>
    */?>

    <div class="LoadContent">
<?endif;?>

<div class="tables"> <div style="margin-bottom:20px"><?=(new \Bitrix\Main\Type\DateTime());?></div>
    <div class="table1" <?=(!array_key_exists('table1',$show) && $show[0] != "all")  ? 'style="display:none"':'';?>>
        <h2>Общий срез лидов</h2>
        <table class="dashtable">
            <tr>
                <td></td>
                <td></td>
                <td>Тотал лидов</td>
                <td>Из них звонки</td>
                <td>Из них почта</td>
                <td>Из них корзина</td>
                <td>Из них морковь</td>
                <td>Создан вручную</td>
                <td>Генератор обзвона</td>
                <td class="green">Качественные</td>
                <td>Из них звонки</td>
                <td>Из них почта</td>
                <td>Из них корзина</td>
                <td>Из них морковь</td>
                <td>Создан вручную</td>
                <td>Генератор обзвона</td>
            </tr>
            <tr>
                <td>Team1</td>
                <td>Измайлов</td>
                <td class="right"><?=$countTeam1;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["CALL"])?count($leadOnStatus["team1"]["CALL"]) /*-1*/ :0;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["EMAIL"])?count($leadOnStatus["team1"]["EMAIL"]) /*-1*/ :0;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["WEB"])?count($leadOnStatus["team1"]["WEB"]) /*-1*/ :0;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["7"])?count($leadOnStatus["team1"]["7"]) /*-1*/ :0;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["FACE_TRACKER"])?count($leadOnStatus["team1"]["FACE_TRACKER"]) /*-1*/ :0;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["5"])?count($leadOnStatus["team1"]["5"]) /*-1*/ :0;?></td>
                <td class="green right"><?=$countTeam1Success;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["CALL"]["SUCCESS"])?count($leadOnStatus["team1"]["CALL"]["SUCCESS"]):0;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["EMAIL"]["SUCCESS"])?count($leadOnStatus["team1"]["EMAIL"]["SUCCESS"]):0;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["WEB"]["SUCCESS"])?count($leadOnStatus["team1"]["WEB"]["SUCCESS"]):0;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["7"]["SUCCESS"])?count($leadOnStatus["team1"]["7"]["SUCCESS"]):0;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["FACE_TRACKER"]["SUCCESS"])?count($leadOnStatus["team1"]["FACE_TRACKER"]["SUCCESS"]):0;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["5"]["SUCCESS"])?count($leadOnStatus["team1"]["5"]["SUCCESS"]):0;?></td>
            </tr>
            <tr>
                <td>Team2</td>
                <td>Кленичев</td>
                <td class="right"><?=$countTeam2;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["CALL"])?count($leadOnStatus["team2"]["CALL"]) /*-1*/ :0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["EMAIL"])?count($leadOnStatus["team2"]["EMAIL"]) /*-1*/ :0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["WEB"])?count($leadOnStatus["team2"]["WEB"]) /*-1*/ :0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["7"])?count($leadOnStatus["team2"]["7"]) /*-1*/ :0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["FACE_TRACKER"])?count($leadOnStatus["team2"]["FACE_TRACKER"]) /*-1*/ :0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["5"])?count($leadOnStatus["team2"]["5"]) /*-1*/ :0;?></td>
                <td class="green right"><?=$countTeam2Success;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["CALL"]["SUCCESS"])?count($leadOnStatus["team2"]["CALL"]["SUCCESS"]):0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["EMAIL"]["SUCCESS"])?count($leadOnStatus["team2"]["EMAIL"]["SUCCESS"]):0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["WEB"]["SUCCESS"])?count($leadOnStatus["team2"]["WEB"]["SUCCESS"]):0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["7"]["SUCCESS"])?count($leadOnStatus["team2"]["7"]["SUCCESS"]):0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["FACE_TRACKER"]["SUCCESS"])?count($leadOnStatus["team2"]["FACE_TRACKER"]["SUCCESS"]):0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["5"]["SUCCESS"])?count($leadOnStatus["team2"]["5"]["SUCCESS"]):0;?></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="right yellow"><?=intval(((($countTeam2) ? $countTeam2 : 1) / (($countTeam1) ? $countTeam1 : 1) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["CALL"])?count($leadOnStatus["team2"]["CALL"]) /*-1*/ :1) / (($leadOnStatus["team1"]["CALL"]) ? count($leadOnStatus["team1"]["CALL"]) /*-1*/ : 1) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["EMAIL"])?count($leadOnStatus["team2"]["EMAIL"]) /*-1*/ : 1) / (($leadOnStatus["team1"]["EMAIL"]) ? count($leadOnStatus["team1"]["EMAIL"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["WEB"])?count($leadOnStatus["team2"]["WEB"]) /*-1*/ : 1 ) / (($leadOnStatus["team1"]["WEB"]) ? count($leadOnStatus["team1"]["WEB"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["7"])?count($leadOnStatus["team2"]["7"]) /*-1*/ : 1) / (($leadOnStatus["team1"]["7"]) ? count($leadOnStatus["team1"]["7"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["FACE_TRACKER"])?count($leadOnStatus["team2"]["FACE_TRACKER"]) /*-1*/ : 1 ) / (($leadOnStatus["team1"]["FACE_TRACKER"]) ? count($leadOnStatus["team1"]["FACE_TRACKER"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["5"])?count($leadOnStatus["team2"]["5"]) /*-1*/ : 1 ) / (($leadOnStatus["team1"]["5"]) ? count($leadOnStatus["team1"]["5"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($countTeam2Success) ? $countTeam2Success : 1) / (($countTeam1Success) ? $countTeam1Success : 1) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["CALL"]["SUCCESS"])?count($leadOnStatus["team2"]["CALL"]["SUCCESS"]) : 1) / (($leadOnStatus["team1"]["CALL"]["SUCCESS"]) ? count($leadOnStatus["team1"]["CALL"]["SUCCESS"]) : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["EMAIL"]["SUCCESS"])?count($leadOnStatus["team2"]["EMAIL"]["SUCCESS"]) : 1) / (($leadOnStatus["team1"]["EMAIL"]["SUCCESS"]) ? count($leadOnStatus["team1"]["EMAIL"]["SUCCESS"]) : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["WEB"]["SUCCESS"])?count($leadOnStatus["team2"]["WEB"]["SUCCESS"]) : 1 ) / (($leadOnStatus["team1"]["WEB"]["SUCCESS"]) ? count($leadOnStatus["team1"]["WEB"]["SUCCESS"]) : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["7"]["SUCCESS"])?count($leadOnStatus["team2"]["7"]["SUCCESS"]) : 1 ) / (($leadOnStatus["team1"]["7"]["SUCCESS"]) ? count($leadOnStatus["team1"]["7"]["SUCCESS"]) : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["FACE_TRACKER"]["SUCCESS"])?count($leadOnStatus["team2"]["FACE_TRACKER"]["SUCCESS"]) : 1 ) / (($leadOnStatus["team1"]["FACE_TRACKER"]["SUCCESS"]) ? count($leadOnStatus["team1"]["FACE_TRACKER"]["SUCCESS"]) : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["5"]["SUCCESS"])?count($leadOnStatus["team2"]["5"]["SUCCESS"]) : 1 ) / (($leadOnStatus["team1"]["5"]["SUCCESS"]) ? count($leadOnStatus["team1"]["5"]["SUCCESS"]) : 1 ) * 100))." %";?></td>
            </tr>
        </table>
    </div>

    <div style="height:50px;clear:both"></div>

    <div class="table2" <?=(!array_key_exists('table2',$show) && $show[0] != "all" )  ? 'style="display:none"':'';?>>
        <h2>Конверсия лидов по отделам</h2>
        <table class="dashtable">
            <tr>
                <td>Team 1</td>
                <td>Измайлов</td>
                <td class="green">Тотал лидов</td>
                <td>Из них звонки</td>
                <td>Из них почта</td>
                <td>Из них корзина</td>
                <td>Из них морковь</td>
                <td>Создан вручную</td>
                <td>Генератор обзвона</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="green right"><?=$countTeam1;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["CALL"]) ? count($leadOnStatus["team1"]["CALL"]) /*-1*/ : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["EMAIL"]) ? count($leadOnStatus["team1"]["EMAIL"]) /*-1*/ : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["WEB"]) ? count($leadOnStatus["team1"]["WEB"]) /*-1*/ : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["7"]) ? count($leadOnStatus["team1"]["7"]) /*-1*/ : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["FACE_TRACKER"]) ? count($leadOnStatus["team1"]["FACE_TRACKER"]) /*-1*/ : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["5"]) ? count($leadOnStatus["team1"]["5"]) /*-1*/ : 0 ;?></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="green">Качественные</td>
                <td>Из них звонки</td>
                <td>Из них почта</td>
                <td>Из них корзина</td>
                <td>Из них морковь</td>
                <td>Создан вручную</td>
                <td>Генератор обзвона</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="green right"><?=$countTeam1Success;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["CALL"]["SUCCESS"]) ? count($leadOnStatus["team1"]["CALL"]["SUCCESS"]) : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["EMAIL"]["SUCCESS"]) ? count($leadOnStatus["team1"]["EMAIL"]["SUCCESS"]) : 0;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["WEB"]["SUCCESS"]) ? count($leadOnStatus["team1"]["WEB"]["SUCCESS"]) : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["7"]["SUCCESS"]) ? count($leadOnStatus["team1"]["7"]["SUCCESS"]) : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["FACE_TRACKER"]["SUCCESS"]) ? count($leadOnStatus["team1"]["FACE_TRACKER"]["SUCCESS"]) : 0;?></td>
                <td class="right"><?=($leadOnStatus["team1"]["5"]["SUCCESS"]) ? count($leadOnStatus["team1"]["5"]["SUCCESS"]) : 0 ;?></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="right yellow"><?=intval(((($countTeam1Success) ? $countTeam1Success : 1) / (($countTeam1) ? $countTeam1 : 1) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team1"]["CALL"]["SUCCESS"]) ? count($leadOnStatus["team1"]["CALL"]["SUCCESS"]) : 1) / (($leadOnStatus["team1"]["CALL"]) ? count($leadOnStatus["team1"]["CALL"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team1"]["EMAIL"]["SUCCESS"]) ? count($leadOnStatus["team1"]["EMAIL"]["SUCCESS"]) : 1) / (($leadOnStatus["team1"]["EMAIL"]) ? count($leadOnStatus["team1"]["EMAIL"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team1"]["WEB"]["SUCCESS"]) ? count($leadOnStatus["team1"]["WEB"]["SUCCESS"]) : 1) / (($leadOnStatus["team1"]["WEB"]) ? count($leadOnStatus["team1"]["WEB"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team1"]["7"]["SUCCESS"]) ? count($leadOnStatus["team1"]["7"]["SUCCESS"]) : 1) / (($leadOnStatus["team1"]["7"]) ? count($leadOnStatus["team1"]["7"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team1"]["FACE_TRACKER"]["SUCCESS"]) ? count($leadOnStatus["team1"]["FACE_TRACKER"]["SUCCESS"]) : 1) / (($leadOnStatus["team1"]["FACE_TRACKER"]) ? count($leadOnStatus["team1"]["FACE_TRACKER"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team1"]["5"]["SUCCESS"]) ? count($leadOnStatus["team1"]["5"]["SUCCESS"]) : 1) / (($leadOnStatus["team1"]["5"]) ? count($leadOnStatus["team1"]["5"]) /*-1*/ : 1 ) * 100))." %";?></td>
            </tr>
        </table>

        <div style="height:50px;clear:both"></div>

        <table class="dashtable">
            <tr>
                <td>Team 2</td>
                <td>Кленичев</td>
                <td class="green">Тотал лидов</td>
                <td>Из них звонки</td>
                <td>Из них почта</td>
                <td>Из них корзина</td>
                <td>Из них морковь</td>
                <td>Создан вручную</td>
                <td>Генератор обзвона</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="green right"><?=$countTeam2;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["CALL"]) ? count($leadOnStatus["team2"]["CALL"]) /*-1*/ : 0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["EMAIL"]) ? count($leadOnStatus["team2"]["EMAIL"]) /*-1*/ : 0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["WEB"]) ? count($leadOnStatus["team2"]["WEB"]) /*-1*/ : 0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["7"]) ? count($leadOnStatus["team2"]["7"]) /*-1*/ : 0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["FACE_TRACKER"]) ? count($leadOnStatus["team2"]["FACE_TRACKER"]) /*-1*/ : 0;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["5"]) ? count($leadOnStatus["team2"]["5"]) /*-1*/ : 0;?></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="green">Качественные</td>
                <td>Из них звонки</td>
                <td>Из них почта</td>
                <td>Из них корзина</td>
                <td>Из них морковь</td>
                <td>Создан вручную</td>
                <td>Генератор обзвона</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="green right"><?=$countTeam2Success;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["CALL"]["SUCCESS"]) ? count($leadOnStatus["team2"]["CALL"]["SUCCESS"]) : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["EMAIL"]["SUCCESS"]) ? count($leadOnStatus["team2"]["EMAIL"]["SUCCESS"]) : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["WEB"]["SUCCESS"]) ? count($leadOnStatus["team2"]["WEB"]["SUCCESS"]) : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["7"]["SUCCESS"]) ? count($leadOnStatus["team2"]["7"]["SUCCESS"]) : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["FACE_TRACKER"]["SUCCESS"]) ? count($leadOnStatus["team2"]["FACE_TRACKER"]["SUCCESS"]) : 0 ;?></td>
                <td class="right"><?=($leadOnStatus["team2"]["5"]["SUCCESS"]) ? count($leadOnStatus["team2"]["5"]["SUCCESS"]) : 0 ;?></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="right yellow"><?=intval(((($countTeam2Success) ? $countTeam2Success : 1) / (($countTeam2) ? $countTeam2 : 1) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["CALL"]["SUCCESS"]) ? count($leadOnStatus["team2"]["CALL"]["SUCCESS"]) : 1 ) / (($leadOnStatus["team2"]["CALL"]) ? count($leadOnStatus["team2"]["CALL"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["EMAIL"]["SUCCESS"]) ? count($leadOnStatus["team2"]["EMAIL"]["SUCCESS"]) : 1 ) / (($leadOnStatus["team2"]["EMAIL"]) ? count($leadOnStatus["team2"]["EMAIL"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["WEB"]["SUCCESS"]) ? count($leadOnStatus["team2"]["WEB"]["SUCCESS"]) : 1 ) / (($leadOnStatus["team2"]["WEB"]) ? count($leadOnStatus["team2"]["WEB"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["7"]["SUCCESS"]) ? count($leadOnStatus["team2"]["7"]["SUCCESS"]) : 1 ) / (($leadOnStatus["team2"]["7"]) ? count($leadOnStatus["team2"]["7"]) /*-1*/ : 1 ) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["FACE_TRACKER"]["SUCCESS"]) ? count($leadOnStatus["team2"]["FACE_TRACKER"]["SUCCESS"]) : 1 ) / (($leadOnStatus["team2"]["FACE_TRACKER"]) ? count($leadOnStatus["team2"]["FACE_TRACKER"]) /*-1*/ : 1) * 100))." %";?></td>
                <td class="right yellow"><?=intval(((($leadOnStatus["team2"]["5"]["SUCCESS"]) ? count($leadOnStatus["team2"]["5"]["SUCCESS"]) : 1 ) / (($leadOnStatus["team2"]["5"]) ? count($leadOnStatus["team2"]["5"]) /*-1*/ : 1 ) * 100))." %";?></td>
            </tr>
        </table>
    </div>

    <div style="height:50px;clear:both"></div>

    <div class="table3" <?=(!array_key_exists('table3',$show) && $show[0] != "all" )  ? 'style="display:none"':'';?>>
        <h2>Team1</h2>
        <table class="dashtable">
            <tr>
                <td></td>
                <td>Team1</td>
                <td>Всего</td>
                <td>Из них звонки</td>
                <td>Из них почта</td>
                <td>Из них корзина</td>
                <td>Из них морковь</td>
                <td>Создан вручную</td>
                <td>Генератор обзвона</td>
            </tr>
                <?
                    $minCall = (is_array(current($leadOnUser["team1"])["CALL"])) ? count(current($leadOnUser["team1"])["CALL"]) : 0;
                    $maxCall = 0;
                    $minEmail = (is_array(current($leadOnUser["team1"])["EMAIL"])) ? count(current($leadOnUser["team1"])["EMAIL"]) : 0;
                    $maxEmail = 0;
                    $minWeb = (is_array(current($leadOnUser["team1"])["WEB"])) ? count(current($leadOnUser["team1"])["WEB"]) : 0;
                    $maxWeb = 0;
                    $minCarrot = (is_array(current($leadOnUser["team1"])["7"])) ? count(current($leadOnUser["team1"])["7"]) : 0;
                    $maxCarrot = 0;
                    $minTracker = (is_array(current($leadOnUser["team1"])["FACE_TRACKER"])) ? count(current($leadOnUser["team1"])["FACE_TRACKER"]) : 0;
                    $maxTracker = 0;
                    $minGenerator = (is_array(current($leadOnUser["team1"])["5"])) ? count(current($leadOnUser["team1"])["5"]) : 0;
                    $maxGenerator = 0;

                    foreach ($leadOnUser["team1"] as $fio => $userLeads) {
                        if (!is_array($userLeads["CALL"])) {
                            $userLeads["CALL"] = []; 
                        }
                        if (is_array($userLeads["CALL"])) { 
                            if (count($userLeads["CALL"]) <= $minCall) {
                                $minCall = count($userLeads["CALL"]);
                            }
                            if (count($userLeads["CALL"]) >= $maxCall) {
                                $maxCall = count($userLeads["CALL"]);
                            }
                        };
                        if (!is_array($userLeads["EMAIL"])) {
                            $userLeads["EMAIL"] = []; 
                        }
                        if (is_array($userLeads["EMAIL"])) { 
                            if (count($userLeads["EMAIL"]) <= $minEmail) {
                                $minEmail = count($userLeads["EMAIL"]);
                            }
                            if (count($userLeads["EMAIL"]) >= $maxEmail) {
                                $maxEmail = count($userLeads["EMAIL"]);
                            }
                        };
                        if (!is_array($userLeads["WEB"])) {
                            $userLeads["WEB"] = []; 
                        }
                        if (is_array($userLeads["WEB"])) { 
                            if (count($userLeads["WEB"]) <= $minWeb) {
                                $minWeb = count($userLeads["WEB"]);
                            }
                            if (count($userLeads["WEB"]) >= $maxWeb) {
                                $maxWeb = count($userLeads["WEB"]);
                            }
                        };
                        if (!is_array($userLeads["7"])) {
                            $userLeads["7"] = []; 
                        }
                        if (is_array($userLeads["7"])) {
                            if (count($userLeads["7"]) <= $minCarrot) {
                                $minCarrot = count($userLeads["7"]);
                            }
                            if (count($userLeads["7"]) >= $maxCarrot) {
                                $maxCarrot = count($userLeads["7"]);
                            }
                        };
                        if (!is_array($userLeads["FACE_TRACKER"])) {
                            $userLeads["FACE_TRACKER"] = []; 
                        }
                        if (is_array($userLeads["FACE_TRACKER"])) { 
                            if (count($userLeads["FACE_TRACKER"]) <= $minTracker) {
                                $minTracker = count($userLeads["FACE_TRACKER"]);
                            }
                            if (count($userLeads["FACE_TRACKER"]) >= $maxTracker) {
                                $maxTracker = count($userLeads["FACE_TRACKER"]);
                            }
                        };
                        if (!is_array($userLeads["5"])) {
                            $userLeads["5"] = []; 
                        }
                        if (is_array($userLeads["5"])) { 
                            if (count($userLeads["5"]) <= $minGenerator) {
                                $minGenerator = count($userLeads["5"]);
                            }
                            if (count($userLeads["5"]) >= $maxGenerator) {
                                $maxGenerator = count($userLeads["5"]);
                            }
                        };
                        
                    }

                    ?>

                    <?foreach ($leadOnUser["team1"] as $fio => $userLeads):?>
                <? 
                    $countlight ="";
                    $countCalllight = "";
                    $countEmaillight = "";
                    $countWeblight = "";
                    $countCarrotlight = "";
                    $countTrackerlight = "";
                    $countGeneratorlight = "";

                    if ($userLeads["COUNT"] == max($leadOnUser["team1"])["COUNT"]) {
                        $countlight = "green";
                    }
                    elseif ($userLeads["COUNT"] == min($leadOnUser["team1"])["COUNT"]) {
                        $countlight = "red"; 
                    }

                    if ((($userLeads["CALL"])?count($userLeads["CALL"]) : 1) == $maxCall)
                    {
                        $countCalllight = "green";
                    }
                    elseif ((($userLeads["CALL"]) ? count($userLeads["CALL"]) : 0) == $minCall ) {
                        $countCalllight = "red";
                    }

                    if ((($userLeads["EMAIL"])?count($userLeads["EMAIL"]) : 1) == $maxEmail)
                    {
                        $countEmaillight = "green";
                    }
                    elseif ((($userLeads["EMAIL"])?count($userLeads["EMAIL"]) : 0) == $minEmail) {
                        $countEmaillight = "red";
                    }

                    if ((($userLeads["WEB"])?count($userLeads["WEB"]) : 1) == $maxWeb)
                    {
                        $countWeblight = "green";
                    }
                    elseif ((($userLeads["WEB"])?count($userLeads["WEB"]) : 0) == $minWeb) {
                        $countWeblight = "red";
                    }
                    
                    if ((($userLeads["7"])?count($userLeads["7"]) : 1) == $maxCarrot)
                    {
                        $countCarrotlight = "green";
                    }
                    elseif ((($userLeads["7"])?count($userLeads["7"]) : 0) == $minCarrot) {
                        $countCarrotlight = "red";
                    }
                    
                    if ((($userLeads["FACE_TRACKER"])?count($userLeads["FACE_TRACKER"]) : 1) == $maxTracker)
                    {
                        $countTrackerlight = "green";
                    }
                    elseif ((($userLeads["FACE_TRACKER"])?count($userLeads["FACE_TRACKER"]) : 0) == $minTracker) {
                        $countTrackerlight = "red";
                    }
                    
                    if ((($userLeads["5"])?count($userLeads["5"]) : 1) == $maxGenerator)
                    {
                        $countGeneratorlight = "green";
                    }
                    elseif ((($userLeads["5"])?count($userLeads["5"]) : 0) == $minGenerator) {
                        $countGeneratorlight = "red";
                    }

                ?>
                <tr>
                    <td>Все</td>
                    <td><?=$fio;?></td>
                    <td class="<?=$countlight;?>"><?=$userLeads["COUNT"];?></td>
                    <td class="<?=$countCalllight;?>"><?=($userLeads["CALL"])?count($userLeads["CALL"]) - 1 : 0 ;?></td>
                    <td class="<?=$countEmaillight;?>"><?=($userLeads["EMAIL"])?count($userLeads["EMAIL"]) - 1 : 0 ;?></td>
                    <td class="<?=$countWeblight;?>"><?=($userLeads["WEB"])?count($userLeads["WEB"]) /*-1*/  : 0 ;?></td>
                    <td class="<?=$countCarrotlight;?>"><?=($userLeads["7"])?count($userLeads["7"]) /*-1*/  : 0 ;?></td>
                    <td class="<?=$countTrackerlight;?>"><?=($userLeads["FACE_TRACKER"])?count($userLeads["FACE_TRACKER"]) /*-1*/  : 0 ;?></td>
                    <td class="<?=$countGeneratorlight;?>"><?=($userLeads["5"])?count($userLeads["5"]) /*-1*/  : 0 ;?></td>
                </tr>
            <?endforeach;?>
            <tr class="blue">
                <td colspan="9"></td>
            </tr>
                <?
                    $minCall = (is_array(current($leadOnUser["team1"])["CALL"]["SUCCESS"])) ? count(current($leadOnUser["team1"])["CALL"]["SUCCESS"]) : 0;
                    $maxCall = 0;
                    $minEmail = (is_array(current($leadOnUser["team1"])["EMAIL"]["SUCCESS"])) ? count(current($leadOnUser["team1"])["EMAIL"]["SUCCESS"]) : 0;
                    $maxEmail = 0;
                    $minWeb = (is_array(current($leadOnUser["team1"])["WEB"]["SUCCESS"])) ? count(current($leadOnUser["team1"])["WEB"]["SUCCESS"]) : 0;
                    $maxWeb = 0;
                    $minCarrot = (is_array(current($leadOnUser["team1"])["7"]["SUCCESS"])) ? count(current($leadOnUser["team1"])["7"]["SUCCESS"]) : 0;
                    $maxCarrot = 0;
                    $minTracker = (is_array(current($leadOnUser["team1"])["FACE_TRACKER"]["SUCCESS"])) ? count(current($leadOnUser["team1"])["FACE_TRACKER"]["SUCCESS"]) : 0;
                    $maxTracker = 0;
                    $minGenerator = (is_array(current($leadOnUser["team1"])["5"]["SUCCESS"])) ? count(current($leadOnUser["team1"])["5"]["SUCCESS"]) : 0;
                    $maxGenerator = 0;

                    foreach ($leadOnUser["team1"] as $fio => $userLeads) {
                        if (!is_array($userLeads["CALL"]["SUCCESS"])) {
                            $userLeads["CALL"]["SUCCESS"] = []; 
                        }
                        if (is_array($userLeads["CALL"]["SUCCESS"])) { 
                            if (count($userLeads["CALL"]["SUCCESS"]) <= $minCall) {
                                $minCall = count($userLeads["CALL"]["SUCCESS"]);
                            }
                            if (count($userLeads["CALL"]["SUCCESS"]) >= $maxCall) {
                                $maxCall = count($userLeads["CALL"]["SUCCESS"]);
                            }
                        };
                        if (!is_array($userLeads["EMAIL"]["SUCCESS"])) {
                            $userLeads["EMAIL"]["SUCCESS"] = []; 
                        }
                        if (is_array($userLeads["EMAIL"]["SUCCESS"])) { 
                            if (count($userLeads["EMAIL"]["SUCCESS"]) <= $minEmail) {
                                $minEmail = count($userLeads["EMAIL"]["SUCCESS"]);
                            }
                            if (count($userLeads["EMAIL"]["SUCCESS"]) >= $maxEmail) {
                                $maxEmail = count($userLeads["EMAIL"]["SUCCESS"]);
                            }
                        };
                        if (!is_array($userLeads["WEB"]["SUCCESS"])) {
                            $userLeads["WEB"]["SUCCESS"] = []; 
                        }
                        if (is_array($userLeads["WEB"]["SUCCESS"])) { 
                            if (count($userLeads["WEB"]["SUCCESS"]) <= $minWeb) {
                                $minWeb = count($userLeads["WEB"]["SUCCESS"]);
                            }
                            if (count($userLeads["WEB"]["SUCCESS"]) >= $maxWeb) {
                                $maxWeb = count($userLeads["WEB"]["SUCCESS"]);
                            }
                        };
                        if (!is_array($userLeads["7"]["SUCCESS"])) {
                            $userLeads["7"]["SUCCESS"] = []; 
                        }
                        if (is_array($userLeads["7"]["SUCCESS"])) {
                            if (count($userLeads["7"]["SUCCESS"]) <= $minCarrot) {
                                $minCarrot = count($userLeads["7"]["SUCCESS"]);
                            }
                            if (count($userLeads["7"]["SUCCESS"]) >= $maxCarrot) {
                                $maxCarrot = count($userLeads["7"]["SUCCESS"]);
                            }
                        };
                        if (!is_array($userLeads["FACE_TRACKER"]["SUCCESS"])) {
                            $userLeads["FACE_TRACKER"]["SUCCESS"] = []; 
                        }
                        if (is_array($userLeads["FACE_TRACKER"]["SUCCESS"])) { 
                            if (count($userLeads["FACE_TRACKER"]["SUCCESS"]) <= $minTracker) {
                                $minTracker = count($userLeads["FACE_TRACKER"]["SUCCESS"]);
                            }
                            if (count($userLeads["FACE_TRACKER"]["SUCCESS"]) >= $maxTracker) {
                                $maxTracker = count($userLeads["FACE_TRACKER"]["SUCCESS"]);
                            }
                        };
                        if (!is_array($userLeads["5"]["SUCCESS"])) {
                            $userLeads["5"]["SUCCESS"] = []; 
                        }
                        if (is_array($userLeads["5"]["SUCCESS"])) { 
                            if (count($userLeads["5"]["SUCCESS"]) <= $minGenerator) {
                                $minGenerator = count($userLeads["5"]["SUCCESS"]);
                            }
                            if (count($userLeads["5"]["SUCCESS"]) >= $maxGenerator) {
                                $maxGenerator = count($userLeads["5"]["SUCCESS"]);
                            }
                        };
                        
                    }

                ?>
            <?foreach ($leadOnUser["team1"] as $fio => $userLeads):?>
                <?
                    $countlight ="";
                    $countCalllight = "";
                    $countEmaillight = "";
                    $countWeblight = "";
                    $countCarrotlight = "";
                    $countTrackerlight = "";
                    $countGeneratorlight = "";

                    if ($userLeads["SUCCESS"]["COUNT"] == max($leadOnUser["team1"])["SUCCESS"]["COUNT"]) {
                        $countlight = "green";
                    }
                    elseif ($userLeads["SUCCESS"]["COUNT"] == min($leadOnUser["team1"])["SUCCESS"]["COUNT"]) {
                        $countlight = "red"; 
                    }

                    if ((($userLeads["CALL"]["SUCCESS"])?count($userLeads["CALL"]["SUCCESS"]) : 1) == $maxCall)
                    {
                        $countCalllight = "green";
                    }
                    elseif ((($userLeads["CALL"]["SUCCESS"]) ? count($userLeads["CALL"]["SUCCESS"]) : 0) == $minCall ) {
                        $countCalllight = "red";
                    }

                    if ((($userLeads["EMAIL"]["SUCCESS"])?count($userLeads["EMAIL"]["SUCCESS"]) : 1) == $maxEmail)
                    {
                        $countEmaillight = "green";
                    }
                    elseif ((($userLeads["EMAIL"]["SUCCESS"])?count($userLeads["EMAIL"]["SUCCESS"]) : 0) == $minEmail) {
                        $countEmaillight = "red";
                    }

                    if ((($userLeads["WEB"]["SUCCESS"])?count($userLeads["WEB"]["SUCCESS"]) : 1) == $maxWeb)
                    {
                        $countWeblight = "green";
                    }
                    elseif ((($userLeads["WEB"]["SUCCESS"])?count($userLeads["WEB"]["SUCCESS"]) : 0) == $minWeb) {
                        $countWeblight = "red";
                    }
                    
                    if ((($userLeads["7"]["SUCCESS"])?count($userLeads["7"]["SUCCESS"]) : 1) == $maxCarrot)
                    {
                        $countCarrotlight = "green";
                    }
                    elseif ((($userLeads["7"]["SUCCESS"])?count($userLeads["7"]["SUCCESS"]) : 0) == $minCarrot) {
                        $countCarrotlight = "red";
                    }
                    
                    if ((($userLeads["FACE_TRACKER"]["SUCCESS"])?count($userLeads["FACE_TRACKER"]["SUCCESS"]) : 1) == $maxTracker)
                    {
                        $countTrackerlight = "green";
                    }
                    elseif ((($userLeads["FACE_TRACKER"]["SUCCESS"])?count($userLeads["FACE_TRACKER"]["SUCCESS"]) : 0) == $minTracker) {
                        $countTrackerlight = "red";
                    }
                    
                    if ((($userLeads["5"]["SUCCESS"])?count($userLeads["5"]["SUCCESS"]) : 1) == $maxGenerator)
                    {
                        $countGeneratorlight = "green";
                    }
                    elseif ((($userLeads["5"]["SUCCESS"])?count($userLeads["5"]["SUCCESS"]) : 0) == $minGenerator) {
                        $countGeneratorlight = "red";
                    }
                ?>
                <tr>
                    <td>Качественные</td>
                    <td class="green"><?=$fio;?></td>
                    <td class="<?=$countlight;?>"><?=$userLeads["SUCCESS"]["COUNT"];?></td>
                    <td class="<?=$countCalllight;?>"><?=($userLeads["CALL"]["SUCCESS"])?count($userLeads["CALL"]["SUCCESS"]) : 0 ;?></td>
                    <td class="<?=$countEmaillight;?>"><?=($userLeads["EMAIL"]["SUCCESS"])?count($userLeads["EMAIL"]["SUCCESS"]) : 0 ;?></td>
                    <td class="<?=$countWeblight;?>"><?=($userLeads["WEB"]["SUCCESS"])?count($userLeads["WEB"]["SUCCESS"]) : 0 ;?></td>
                    <td class="<?=$countCarrotlight;?>"><?=($userLeads["7"]["SUCCESS"])?count($userLeads["7"]["SUCCESS"]) : 0 ;?></td>
                    <td class="<?=$countTrackerlight;?>"><?=($userLeads["FACE_TRACKER"]["SUCCESS"])?count($userLeads["FACE_TRACKER"]["SUCCESS"]) : 0 ;?></td>
                    <td class="<?=$countGeneratorlight;?>"><?=($userLeads["5"]["SUCCESS"])?count($userLeads["5"]["SUCCESS"]) : 0 ;?></td>
                </tr>
            <?endforeach;?>
            <tr class="yellow">
                <td></td>
                <td>% Конверсии</td>
                <td>Всего</td>
                <td>Из них звонки</td>
                <td>Из них почта</td>
                <td>Из них корзина</td>
                <td>Из них морковь</td>
                <td>Создан вручную</td>
                <td>Генератор обзвона</td>
            </tr>
            <?
                $curCountConv = intval( (current($leadOnUser["team1"])["SUCCESS"]["COUNT"] / current($leadOnUser["team1"])["COUNT"]) * 100);
                $minCountConv = $curCountConv;
                $maxCountConv = 0;

                $curCallConv = intval((((current($leadOnUser["team1"])["CALL"]["SUCCESS"]) ? count(current($leadOnUser["team1"])["CALL"]["SUCCESS"]) : 1 ) / ((current($leadOnUser["team1"])["CALL"]) ? count(current($leadOnUser["team1"])["CALL"]) : 1 ) * 100));
                $minCall = $curCallConv;
                $maxCall = 0;

                $curMailConv = intval((((current($leadOnUser["team1"])["EMAIL"]["SUCCESS"]) ? count(current($leadOnUser["team1"])["EMAIL"]["SUCCESS"]) : 1 ) / ((current($leadOnUser["team1"])["EMAIL"]) ? count(current($leadOnUser["team1"])["EMAIL"]) : 1) * 100));
                $minEmail = $curMailConv;
                $maxEmail = 0;

                $curWebConv = intval((((current($leadOnUser["team1"])["WEB"]["SUCCESS"]) ? count(current($leadOnUser["team1"])["WEB"]["SUCCESS"]) : 1 ) / ((current($leadOnUser["team1"])["WEB"]) ? count(current($leadOnUser["team1"])["WEB"]) : 1) * 100));
                $minWeb = $curWebConv;
                $maxWeb = 0;

                $curCarrotConv = intval((((current($leadOnUser["team1"])["7"]["SUCCESS"]) ? count(current($leadOnUser["team1"])["7"]["SUCCESS"]) : 1 ) / ((current($leadOnUser["team1"])["7"]) ? count(current($leadOnUser["team1"])["7"]) : 1) * 100));
                $minCarrot = $curCarrotConv;
                $maxCarrot = 0;

                $curTrackerConv = intval((((current($leadOnUser["team1"])["FACE_TRACKER"]["SUCCESS"]) ? count(current($leadOnUser["team1"])["FACE_TRACKER"]["SUCCESS"]) : 1 ) / ((current($leadOnUser["team1"])["FACE_TRACKER"]) ? count(current($leadOnUser["team1"])["FACE_TRACKER"]) : 1) * 100));
                $minTracker = $curTrackerConv;
                $maxTracker = 0;

                $curGeneratorConv = intval((((current($leadOnUser["team1"])["5"]["SUCCESS"]) ? count(current($leadOnUser["team1"])["5"]["SUCCESS"]) : 1 ) / ((current($leadOnUser["team1"])["5"]) ? count(current($leadOnUser["team1"])["5"]) : 1) * 100));
                $minGenerator = $curGeneratorConv;
                $maxGenerator = 0;

                foreach ($leadOnUser["team1"] as $fio => $userLeads) {

                    $curCountConv = intval($userLeads["SUCCESS"]["COUNT"] / (($userLeads["COUNT"]) ? $userLeads["COUNT"] : 1) * 100);
                    $curCallConv = intval(((($userLeads["CALL"]["SUCCESS"]) ? count($userLeads["CALL"]["SUCCESS"]) : 1 ) / (($userLeads["CALL"]) ? count($userLeads["CALL"]) : 1 ) * 100));
                    $curMailConv = intval(((($userLeads["EMAIL"]["SUCCESS"]) ? count($userLeads["EMAIL"]["SUCCESS"]) : 1 ) / (($userLeads["EMAIL"]) ? count($userLeads["EMAIL"]) : 1) * 100));
                    $curWebConv = intval(((($userLeads["WEB"]["SUCCESS"]) ? count($userLeads["WEB"]["SUCCESS"]) : 1 ) / (($userLeads["WEB"]) ? count($userLeads["WEB"]) : 1) * 100));
                    $curCarrotConv = intval(((($userLeads["7"]["SUCCESS"]) ? count($userLeads["7"]["SUCCESS"]) : 1 ) / (($userLeads["7"]) ? count($userLeads["7"]) : 1) * 100));
                    $curTrackerConv = intval(((($userLeads["FACE_TRACKER"]["SUCCESS"]) ? count($userLeads["FACE_TRACKER"]["SUCCESS"]) : 1 ) / (($userLeads["FACE_TRACKER"]) ? count($userLeads["FACE_TRACKER"]) : 1) * 100));
                    $curGeneratorConv = intval(((($userLeads["5"]["SUCCESS"]) ? count($userLeads["5"]["SUCCESS"]) : 1 ) / (($userLeads["5"]) ? count($userLeads["5"]) : 1) * 100));

                    if ($curCountConv <= $minCountConv) {
                        $minCountConv = $curCountConv;
                    }
                    if ($curCountConv >= $maxCountConv) {
                        $maxCountConv = $curCountConv;
                    }

                    if ($curCallConv <= $minCall) {
                        $minCall = $curCallConv;
                    }
                    if ($curCallConv >= $maxCall) {
                        $maxCall = $curCallConv;
                    }

                    if ($curMailConv <= $minEmail) {
                        $minEmail = $curMailConv;
                    }
                    if ($curMailConv >= $maxEmail) {
                        $maxEmail = $curMailConv;
                    }

                    if ($curWebConv <= $minWeb) {
                        $minWeb = $curWebConv;
                    }
                    if ($curWebConv >= $maxWeb) {
                        $maxWeb = $curWebConv;
                    }

                    if ($curCarrotConv <= $minCarrot) {
                        $minCarrot = $curCarrotConv;
                    }
                    if ($curCarrotConv >= $maxCarrot) {
                        $maxCarrot = $curCarrotConv;
                    }

                    if ($curTrackerConv <= $minTracker) {
                        $minTracker = $curTrackerConv;
                    }
                    if ($curTrackerConv >= $maxTracker) {
                        $maxTracker = $curTrackerConv;
                    }

                    if ($curGeneratorConv <= $minGenerator) {
                        $minGenerator = $curGeneratorConv;
                    }
                    if ($curGeneratorConv >= $maxGenerator) {
                        $maxGenerator = $curGeneratorConv;
                    }

                }

            ?>
            
            <?foreach ($leadOnUser["team1"] as $fio => $userLeads):?>
                <? 
                    $countlight ="";
                    $countCalllight = "";
                    $countEmaillight = "";
                    $countWeblight = "";
                    $countCarrotlight = "";
                    $countTrackerlight = "";
                    $countGeneratorlight = "";

                    $curCountConv = intval($userLeads["SUCCESS"]["COUNT"] / (($userLeads["COUNT"]) ? $userLeads["COUNT"] : 1) * 100);
                    $curCallConv = intval(((($userLeads["CALL"]["SUCCESS"]) ? count($userLeads["CALL"]["SUCCESS"]) : 1 ) / (($userLeads["CALL"]) ? count($userLeads["CALL"]) : 1 ) * 100));
                    $curMailConv = intval(((($userLeads["EMAIL"]["SUCCESS"]) ? count($userLeads["EMAIL"]["SUCCESS"]) : 1 ) / (($userLeads["EMAIL"]) ? count($userLeads["EMAIL"]) : 1) * 100));
                    $curWebConv = intval(((($userLeads["WEB"]["SUCCESS"]) ? count($userLeads["WEB"]["SUCCESS"]) : 1 ) / (($userLeads["WEB"]) ? count($userLeads["WEB"]) : 1) * 100));
                    $curCarrotConv = intval(((($userLeads["7"]["SUCCESS"]) ? count($userLeads["7"]["SUCCESS"]) : 1 ) / (($userLeads["7"]) ? count($userLeads["7"]) : 1) * 100));
                    $curTrackerConv = intval(((($userLeads["FACE_TRACKER"]["SUCCESS"]) ? count($userLeads["FACE_TRACKER"]["SUCCESS"]) : 1 ) / (($userLeads["FACE_TRACKER"]) ? count($userLeads["FACE_TRACKER"]) : 1) * 100));
                    $curGeneratorConv = intval(((($userLeads["5"]["SUCCESS"]) ? count($userLeads["5"]["SUCCESS"]) : 1 ) / (($userLeads["5"]) ? count($userLeads["5"]) : 1) * 100));

                    if ($curCountConv == $maxCountConv) {
                        $countlight = "green";
                    }
                    elseif ($curCountConv == $minCountConv) {
                        $countlight = "red"; 
                    }

                    if ($curCallConv == $maxCall)
                    {
                        $countCalllight = "green";
                    }
                    elseif ($curCallConv == $minCall ) {
                        $countCalllight = "red";
                    }

                    if ($curMailConv == $maxEmail)
                    {
                        $countEmaillight = "green";
                    }
                    elseif ($curMailConv == $minEmail) {
                        $countEmaillight = "red";
                    }

                    if ($curWebConv == $maxWeb)
                    {
                        $countWeblight = "green";
                    }
                    elseif ($curWebConv == $minWeb) {
                        $countWeblight = "red";
                    }
                    
                    if ($curCarrotConv == $maxCarrot)
                    {
                        $countCarrotlight = "green";
                    }
                    elseif ($curCarrotConv == $minCarrot) {
                        $countCarrotlight = "red";
                    }
                    
                    if ($curTrackerConv == $maxTracker)
                    {
                        $countTrackerlight = "green";
                    }
                    elseif ($curTrackerConv == $minTracker) {
                        $countTrackerlight = "red";
                    }
                    
                    if ($curGeneratorConv == $maxGenerator)
                    {
                        $countGeneratorlight = "green";
                    }
                    elseif ($curGeneratorConv == $minGenerator) {
                        $countGeneratorlight = "red";
                    }

                ?>
                <tr>
                    <td class="yellow">% Конверсии</td>
                    <td class="yellow"><?=$fio;?></td>
                    <td class="<?=$countlight;?>"><?=intval($userLeads["SUCCESS"]["COUNT"] / (($userLeads["COUNT"]) ? $userLeads["COUNT"] : 1) * 100)." %";?></td>
                    <td class="<?=$countCalllight;?>"><?=intval(((($userLeads["CALL"]["SUCCESS"]) ? count($userLeads["CALL"]["SUCCESS"]) : 1 ) / (($userLeads["CALL"]) ? count($userLeads["CALL"]) : 1 ) * 100))." %";?></td>
                    <td class="<?=$countEmaillight;?>"><?=intval(((($userLeads["EMAIL"]["SUCCESS"]) ? count($userLeads["EMAIL"]["SUCCESS"]) : 1 ) / (($userLeads["EMAIL"]) ? count($userLeads["EMAIL"]) : 1) * 100))." %";?></td>
                    <td class="<?=$countWeblight;?>"><?=intval(((($userLeads["WEB"]["SUCCESS"]) ? count($userLeads["WEB"]["SUCCESS"]) : 1 ) / (($userLeads["WEB"]) ? count($userLeads["WEB"]) : 1) * 100))." %";?></td>
                    <td class="<?=$countCarrotlight;?>"><?=intval(((($userLeads["7"]["SUCCESS"]) ? count($userLeads["7"]["SUCCESS"]) : 1 ) / (($userLeads["7"]) ? count($userLeads["7"]) : 1) * 100))." %";?></td>
                    <td class="<?=$countTrackerlight;?>"><?=intval(((($userLeads["FACE_TRACKER"]["SUCCESS"]) ? count($userLeads["FACE_TRACKER"]["SUCCESS"]) : 1 ) / (($userLeads["FACE_TRACKER"]) ? count($userLeads["FACE_TRACKER"]) : 1) * 100))." %";?></td>
                    <td class="<?=$countGeneratorlight;?>"><?=intval(((($userLeads["5"]["SUCCESS"]) ? count($userLeads["5"]["SUCCESS"]) : 1 ) / (($userLeads["5"]) ? count($userLeads["5"]) : 1) * 100))." %";?></td>
                </tr>
            <?endforeach;?>
        </table>
    </div>

    <div style="height:50px;clear:both"></div>

    <div class="table4" <?=(!array_key_exists('table4',$show) && $show[0] != "all" )  ? 'style="display:none"':'';?>>
        <h2>Team2</h2>
        <table class="dashtable">
            <tr>
                <td></td>
                <td>Team 2</td>
                <td>Всего</td>
                <td>Из них звонки</td>
                <td>Из них почта</td>
                <td>Из них корзина</td>
                <td>Из них морковь</td>
                <td>Создан вручную</td>
                <td>Генератор обзвона</td>
            </tr>
                <?
                    $minCount = (current($leadOnUser["team2"])["COUNT"]) ? current($leadOnUser["team2"])["COUNT"] : 0;
                    $maxCount = 0;
                    $minCall = (is_array(current($leadOnUser["team2"])["CALL"])) ? count(current($leadOnUser["team2"])["CALL"]) : 0;
                    $maxCall = 0;
                    $minEmail = (is_array(current($leadOnUser["team2"])["EMAIL"])) ? count(current($leadOnUser["team2"])["EMAIL"]) : 0;
                    $maxEmail = 0;
                    $minWeb = (is_array(current($leadOnUser["team2"])["WEB"])) ? count(current($leadOnUser["team2"])["WEB"]) : 0;
                    $maxWeb = 0;
                    $minCarrot = (is_array(current($leadOnUser["team2"])["7"])) ? count(current($leadOnUser["team2"])["7"]) : 0;
                    $maxCarrot = 0;
                    $minTracker = (is_array(current($leadOnUser["team2"])["FACE_TRACKER"])) ? count(current($leadOnUser["team2"])["FACE_TRACKER"]) : 0;
                    $maxTracker = 0;
                    $minGenerator = (is_array(current($leadOnUser["team2"])["5"])) ? count(current($leadOnUser["team2"])["5"]) : 0;
                    $maxGenerator = 0;

                    foreach ($leadOnUser["team2"] as $fio => $userLeads) {
                       
                        if ($userLeads["COUNT"] <= $minCount) {
                            $minCount = $userLeads["COUNT"];
                        }
                        if ($userLeads["COUNT"] >= $maxCount) {
                            $maxCount = $userLeads["COUNT"];
                        }

                        if (!is_array($userLeads["CALL"])) {
                            $userLeads["CALL"] = []; 
                        }
                        if (is_array($userLeads["CALL"])) { 
                            if (count($userLeads["CALL"]) <= $minCall) {
                                $minCall = count($userLeads["CALL"]);
                            }
                            if (count($userLeads["CALL"]) >= $maxCall) {
                                $maxCall = count($userLeads["CALL"]);
                            }
                        };
                        if (!is_array($userLeads["EMAIL"])) {
                            $userLeads["EMAIL"] = []; 
                        }
                        if (is_array($userLeads["EMAIL"])) { 
                            if (count($userLeads["EMAIL"]) <= $minEmail) {
                                $minEmail = count($userLeads["EMAIL"]);
                            }
                            if (count($userLeads["EMAIL"]) >= $maxEmail) {
                                $maxEmail = count($userLeads["EMAIL"]);
                            }
                        };
                        if (!is_array($userLeads["WEB"])) {
                            $userLeads["WEB"] = []; 
                        }
                        if (is_array($userLeads["WEB"])) { 
                            if (count($userLeads["WEB"]) <= $minWeb) {
                                $minWeb = count($userLeads["WEB"]);
                            }
                            if (count($userLeads["WEB"]) >= $maxWeb) {
                                $maxWeb = count($userLeads["WEB"]);
                            }
                        };
                        if (!is_array($userLeads["7"])) {
                            $userLeads["7"] = []; 
                        }
                        if (is_array($userLeads["7"])) {
                            if (count($userLeads["7"]) <= $minCarrot) {
                                $minCarrot = count($userLeads["7"]);
                            }
                            if (count($userLeads["7"]) >= $maxCarrot) {
                                $maxCarrot = count($userLeads["7"]);
                            }
                        };
                        if (!is_array($userLeads["FACE_TRACKER"])) {
                            $userLeads["FACE_TRACKER"] = []; 
                        }
                        if (is_array($userLeads["FACE_TRACKER"])) { 
                            if (count($userLeads["FACE_TRACKER"]) <= $minTracker) {
                                $minTracker = count($userLeads["FACE_TRACKER"]);
                            }
                            if (count($userLeads["FACE_TRACKER"]) >= $maxTracker) {
                                $maxTracker = count($userLeads["FACE_TRACKER"]);
                            }
                        };
                        if (!is_array($userLeads["5"])) {
                            $userLeads["5"] = []; 
                        }
                        if (is_array($userLeads["5"])) { 
                            if (count($userLeads["5"]) <= $minGenerator) {
                                $minGenerator = count($userLeads["5"]);
                            }
                            if (count($userLeads["5"]) >= $maxGenerator) {
                                $maxGenerator = count($userLeads["5"]);
                            }
                        };
                        
                    }

                    ?>
                    
            <?foreach ($leadOnUser["team2"] as $fio => $userLeads):?>
                <? 
                    $countlight ="";
                    $countCalllight = "";
                    $countEmaillight = "";
                    $countWeblight = "";
                    $countCarrotlight = "";
                    $countTrackerlight = "";
                    $countGeneratorlight = "";
                
                    if ($userLeads["COUNT"] == $maxCount) {
                        $countlight = "green";
                    }
                    elseif ($userLeads["COUNT"] == $minCount) {
                        $countlight = "red"; 
                    }

                    if ((($userLeads["CALL"])?count($userLeads["CALL"]) : 1) == $maxCall)
                    {
                        $countCalllight = "green";
                    }
                    elseif ((($userLeads["CALL"]) ? count($userLeads["CALL"]) : 0) == $minCall ) {
                        $countCalllight = "red";
                    }

                    if ((($userLeads["EMAIL"])?count($userLeads["EMAIL"]) : 1) == $maxEmail)
                    {
                        $countEmaillight = "green";
                    }
                    elseif ((($userLeads["EMAIL"])?count($userLeads["EMAIL"]) : 0) == $minEmail) {
                        $countEmaillight = "red";
                    }

                    if ((($userLeads["WEB"])?count($userLeads["WEB"]) : 1) == $maxWeb)
                    {
                        $countWeblight = "green";
                    }
                    elseif ((($userLeads["WEB"])?count($userLeads["WEB"]) : 0) == $minWeb) {
                        $countWeblight = "red";
                    }
                    
                    if ((($userLeads["7"])?count($userLeads["7"]) : 1) == $maxCarrot)
                    {
                        $countCarrotlight = "green";
                    }
                    elseif ((($userLeads["7"])?count($userLeads["7"]) : 0) == $minCarrot) {
                        $countCarrotlight = "red";
                    }
                    
                    if ((($userLeads["FACE_TRACKER"])?count($userLeads["FACE_TRACKER"]) : 1) == $maxTracker)
                    {
                        $countTrackerlight = "green";
                    }
                    elseif ((($userLeads["FACE_TRACKER"])?count($userLeads["FACE_TRACKER"]) : 0) == $minTracker) {
                        $countTrackerlight = "red";
                    }
                    
                    if ((($userLeads["5"])?count($userLeads["5"]) : 1) == $maxGenerator)
                    {
                        $countGeneratorlight = "green";
                    }
                    elseif ((($userLeads["5"])?count($userLeads["5"]) : 0) == $minGenerator) {
                        $countGeneratorlight = "red";
                    }

                ?>
                <tr>
                    <td>Все</td>
                    <td><?=$fio;?></td>
                    <td class="<?=$countlight;?>"><?=$userLeads["COUNT"];?></td>
                    <td class="<?=$countCalllight;?>"><?=($userLeads["CALL"])?count($userLeads["CALL"]) /*-1*/  : 0 ;?></td>
                    <td class="<?=$countEmaillight;?>"><?=($userLeads["EMAIL"])?count($userLeads["EMAIL"]) /*-1*/ : 0 ;?></td>
                    <td class="<?=$countWeblight;?>"><?=($userLeads["WEB"])?count($userLeads["WEB"]) /*-1*/ : 0 ;?></td>
                    <td class="<?=$countCarrotlight;?>"><?=($userLeads["7"])?count($userLeads["7"]) /*-1*/ : 0 ;?></td>
                    <td class="<?=$countTrackerlight;?>"><?=($userLeads["FACE_TRACKER"])?count($userLeads["FACE_TRACKER"]) /*-1*/ : 0 ;?></td>
                    <td class="<?=$countGeneratorlight;?>"><?=($userLeads["5"])?count($userLeads["5"]) /*-1*/ : 0 ;?></td>
                </tr>
            <?endforeach;?>
                <tr class="blue">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <?
                    $minCount = (current($leadOnUser["team2"])["SUCCESS"]["COUNT"]) ? current($leadOnUser["team2"])["SUCCESS"]["COUNT"] : 0;
                    $maxCount = 0;
                    $minCall = (is_array(current($leadOnUser["team2"])["CALL"]["SUCCESS"])) ? count(current($leadOnUser["team2"])["CALL"]["SUCCESS"]) : 0;
                    $maxCall = 0;
                    $minEmail = (is_array(current($leadOnUser["team2"])["EMAIL"]["SUCCESS"])) ? count(current($leadOnUser["team2"])["EMAIL"]["SUCCESS"]) : 0;
                    $maxEmail = 0;
                    $minWeb = (is_array(current($leadOnUser["team2"])["WEB"]["SUCCESS"])) ? count(current($leadOnUser["team2"])["WEB"]["SUCCESS"]) : 0;
                    $maxWeb = 0;
                    $minCarrot = (is_array(current($leadOnUser["team2"])["7"]["SUCCESS"])) ? count(current($leadOnUser["team2"])["7"]["SUCCESS"]) : 0;
                    $maxCarrot = 0;
                    $minTracker = (is_array(current($leadOnUser["team2"])["FACE_TRACKER"]["SUCCESS"])) ? count(current($leadOnUser["team2"])["FACE_TRACKER"]["SUCCESS"]) : 0;
                    $maxTracker = 0;
                    $minGenerator = (is_array(current($leadOnUser["team2"])["5"]["SUCCESS"])) ? count(current($leadOnUser["team2"])["5"]["SUCCESS"]) : 0;
                    $maxGenerator = 0;

                    foreach ($leadOnUser["team2"] as $fio => $userLeads) {
                        
                        if ($userLeads["SUCCESS"]["COUNT"] <= $minCount) {
                            $minCount = $userLeads["SUCCESS"]["COUNT"];
                        }
                        if ($userLeads["SUCCESS"]["COUNT"] >= $maxCount) {
                            $maxCount = $userLeads["SUCCESS"]["COUNT"];
                        }
                        
                        if (!is_array($userLeads["CALL"]["SUCCESS"])) {
                            $userLeads["CALL"]["SUCCESS"] = []; 
                        }
                        if (is_array($userLeads["CALL"]["SUCCESS"])) { 
                            if (count($userLeads["CALL"]["SUCCESS"]) <= $minCall) {
                                $minCall = count($userLeads["CALL"]["SUCCESS"]);
                            }
                            if (count($userLeads["CALL"]["SUCCESS"]) >= $maxCall) {
                                $maxCall = count($userLeads["CALL"]["SUCCESS"]);
                            }
                        };
                        if (!is_array($userLeads["EMAIL"]["SUCCESS"])) {
                            $userLeads["EMAIL"]["SUCCESS"] = []; 
                        }
                        if (is_array($userLeads["EMAIL"]["SUCCESS"])) { 
                            if (count($userLeads["EMAIL"]["SUCCESS"]) <= $minEmail) {
                                $minEmail = count($userLeads["EMAIL"]["SUCCESS"]);
                            }
                            if (count($userLeads["EMAIL"]["SUCCESS"]) >= $maxEmail) {
                                $maxEmail = count($userLeads["EMAIL"]["SUCCESS"]);
                            }
                        };
                        if (!is_array($userLeads["WEB"]["SUCCESS"])) {
                            $userLeads["WEB"]["SUCCESS"] = []; 
                        }
                        if (is_array($userLeads["WEB"]["SUCCESS"])) { 
                            if (count($userLeads["WEB"]["SUCCESS"]) <= $minWeb) {
                                $minWeb = count($userLeads["WEB"]["SUCCESS"]);
                            }
                            if (count($userLeads["WEB"]["SUCCESS"]) >= $maxWeb) {
                                $maxWeb = count($userLeads["WEB"]["SUCCESS"]);
                            }
                        };
                        if (!is_array($userLeads["7"]["SUCCESS"])) {
                            $userLeads["7"]["SUCCESS"] = []; 
                        }
                        if (is_array($userLeads["7"]["SUCCESS"])) {
                            if (count($userLeads["7"]["SUCCESS"]) <= $minCarrot) {
                                $minCarrot = count($userLeads["7"]["SUCCESS"]);
                            }
                            if (count($userLeads["7"]["SUCCESS"]) >= $maxCarrot) {
                                $maxCarrot = count($userLeads["7"]["SUCCESS"]);
                            }
                        };
                        if (!is_array($userLeads["FACE_TRACKER"]["SUCCESS"])) {
                            $userLeads["FACE_TRACKER"]["SUCCESS"] = []; 
                        }
                        if (is_array($userLeads["FACE_TRACKER"]["SUCCESS"])) { 
                            if (count($userLeads["FACE_TRACKER"]["SUCCESS"]) <= $minTracker) {
                                $minTracker = count($userLeads["FACE_TRACKER"]["SUCCESS"]);
                            }
                            if (count($userLeads["FACE_TRACKER"]["SUCCESS"]) >= $maxTracker) {
                                $maxTracker = count($userLeads["FACE_TRACKER"]["SUCCESS"]);
                            }
                        };
                        if (!is_array($userLeads["5"]["SUCCESS"])) {
                            $userLeads["5"]["SUCCESS"] = []; 
                        }
                        if (is_array($userLeads["5"]["SUCCESS"])) { 
                            if (count($userLeads["5"]["SUCCESS"]) <= $minGenerator) {
                                $minGenerator = count($userLeads["5"]["SUCCESS"]);
                            }
                            if (count($userLeads["5"]["SUCCESS"]) >= $maxGenerator) {
                                $maxGenerator = count($userLeads["5"]["SUCCESS"]);
                            }
                        };
                        
                    }

                ?>
            <?foreach ($leadOnUser["team2"] as $fio => $userLeads):?>
                <?
                    $countlight ="";
                    $countCalllight = "";
                    $countEmaillight = "";
                    $countWeblight = "";
                    $countCarrotlight = "";
                    $countTrackerlight = "";
                    $countGeneratorlight = "";

                    if ($userLeads["SUCCESS"]["COUNT"] == $maxCount) {
                        $countlight = "green";
                    }
                    elseif ($userLeads["SUCCESS"]["COUNT"] == $minCount) {
                        $countlight = "red"; 
                    }

                    if ((($userLeads["CALL"]["SUCCESS"])?count($userLeads["CALL"]["SUCCESS"]) : 1) == $maxCall)
                    {
                        $countCalllight = "green";
                    }
                    elseif ((($userLeads["CALL"]["SUCCESS"]) ? count($userLeads["CALL"]["SUCCESS"]) : 0) == $minCall ) {
                        $countCalllight = "red";
                    }

                    if ((($userLeads["EMAIL"]["SUCCESS"])?count($userLeads["EMAIL"]["SUCCESS"]) : 1) == $maxEmail)
                    {
                        $countEmaillight = "green";
                    }
                    elseif ((($userLeads["EMAIL"]["SUCCESS"])?count($userLeads["EMAIL"]["SUCCESS"]) : 0) == $minEmail) {
                        $countEmaillight = "red";
                    }

                    if ((($userLeads["WEB"]["SUCCESS"])?count($userLeads["WEB"]["SUCCESS"]) : 1) == $maxWeb)
                    {
                        $countWeblight = "green";
                    }
                    elseif ((($userLeads["WEB"]["SUCCESS"])?count($userLeads["WEB"]["SUCCESS"]) : 0) == $minWeb) {
                        $countWeblight = "red";
                    }
                    
                    if ((($userLeads["7"]["SUCCESS"])?count($userLeads["7"]["SUCCESS"]) : 1) == $maxCarrot)
                    {
                        $countCarrotlight = "green";
                    }
                    elseif ((($userLeads["7"]["SUCCESS"])?count($userLeads["7"]["SUCCESS"]) : 0) == $minCarrot) {
                        $countCarrotlight = "red";
                    }
                    
                    if ((($userLeads["FACE_TRACKER"]["SUCCESS"])?count($userLeads["FACE_TRACKER"]["SUCCESS"]) : 1) == $maxTracker)
                    {
                        $countTrackerlight = "green";
                    }
                    elseif ((($userLeads["FACE_TRACKER"]["SUCCESS"])?count($userLeads["FACE_TRACKER"]["SUCCESS"]) : 0) == $minTracker) {
                        $countTrackerlight = "red";
                    }
                    
                    if ((($userLeads["5"]["SUCCESS"])?count($userLeads["5"]["SUCCESS"]) : 1) == $maxGenerator)
                    {
                        $countGeneratorlight = "green";
                    }
                    elseif ((($userLeads["5"]["SUCCESS"])?count($userLeads["5"]["SUCCESS"]) : 0) == $minGenerator) {
                        $countGeneratorlight = "red";
                    }
                ?>
                <tr>
                    <td>Качественные</td>
                    <td class="green"><?=$fio;?></td>
                    <td class="<?=$countlight;?>"><?=$userLeads["SUCCESS"]["COUNT"];?></td>
                    <td class="<?=$countCalllight;?>"><?=($userLeads["CALL"]["SUCCESS"])?count($userLeads["CALL"]["SUCCESS"]) : 0 ;?></td>
                    <td class="<?=$countEmaillight;?>"><?=($userLeads["EMAIL"]["SUCCESS"])?count($userLeads["EMAIL"]["SUCCESS"]) : 0 ;?></td>
                    <td class="<?=$countWeblight;?>"><?=($userLeads["WEB"]["SUCCESS"])?count($userLeads["WEB"]["SUCCESS"]) : 0 ;?></td>
                    <td class="<?=$countCarrotlight;?>"><?=($userLeads["7"]["SUCCESS"])?count($userLeads["7"]["SUCCESS"]) : 0 ;?></td>
                    <td class="<?=$countTrackerlight;?>"><?=($userLeads["FACE_TRACKER"]["SUCCESS"])?count($userLeads["FACE_TRACKER"]["SUCCESS"]) : 0 ;?></td>
                    <td class="<?=$countGeneratorlight;?>"><?=($userLeads["5"]["SUCCESS"])?count($userLeads["5"]["SUCCESS"]) : 0 ;?></td>
                </tr>
            <?endforeach;?>
            <tr class="yellow">
                <td></td>
                <td>% Конверсии</td>
                <td>Всего</td>
                <td>Из них звонки</td>
                <td>Из них почта</td>
                <td>Из них корзина</td>
                <td>Из них морковь</td>
                <td>Создан вручную</td>
                <td>Генератор обзвона</td>
            </tr>
            <?
                $curCountConv = intval((current($leadOnUser["team2"])["SUCCESS"]["COUNT"] / current($leadOnUser["team2"])["COUNT"]) * 100);
                $minCountConv = $curCountConv;
                $maxCountConv = 0;

                $curCallConv = intval((((current($leadOnUser["team2"])["CALL"]["SUCCESS"]) ? count(current($leadOnUser["team2"])["CALL"]["SUCCESS"]) : 1 ) / ((current($leadOnUser["team2"])["CALL"]) ? count(current($leadOnUser["team2"])["CALL"]) : 1 ) * 100));
                $minCall = $curCallConv;
                $maxCall = 0;

                $curMailConv = intval((((current($leadOnUser["team2"])["EMAIL"]["SUCCESS"]) ? count(current($leadOnUser["team2"])["EMAIL"]["SUCCESS"]) : 1 ) / ((current($leadOnUser["team2"])["EMAIL"]) ? count(current($leadOnUser["team2"])["EMAIL"]) : 1) * 100));
                $minEmail = $curMailConv;
                $maxEmail = 0;

                $curWebConv = intval((((current($leadOnUser["team2"])["WEB"]["SUCCESS"]) ? count(current($leadOnUser["team2"])["WEB"]["SUCCESS"]) : 1 ) / ((current($leadOnUser["team2"])["WEB"]) ? count(current($leadOnUser["team2"])["WEB"]) : 1) * 100));
                $minWeb = $curWebConv;
                $maxWeb = 0;

                $curCarrotConv = intval((((current($leadOnUser["team2"])["7"]["SUCCESS"]) ? count(current($leadOnUser["team2"])["7"]["SUCCESS"]) : 1 ) / ((current($leadOnUser["team2"])["7"]) ? count(current($leadOnUser["team2"])["7"]) : 1) * 100));
                $minCarrot = $curCarrotConv;
                $maxCarrot = 0;

                $curTrackerConv = intval((((current($leadOnUser["team2"])["FACE_TRACKER"]["SUCCESS"]) ? count(current($leadOnUser["team2"])["FACE_TRACKER"]["SUCCESS"]) : 1 ) / ((current($leadOnUser["team2"])["FACE_TRACKER"]) ? count(current($leadOnUser["team2"])["FACE_TRACKER"]) : 1) * 100));
                $minTracker = $curTrackerConv;
                $maxTracker = 0;

                $curGeneratorConv = intval((((current($leadOnUser["team2"])["5"]["SUCCESS"]) ? count(current($leadOnUser["team2"])["5"]["SUCCESS"]) : 1 ) / ((current($leadOnUser["team2"])["5"]) ? count(current($leadOnUser["team2"])["5"]) : 1) * 100));
                $minGenerator = $curGeneratorConv;
                $maxGenerator = 0;

                foreach ($leadOnUser["team2"] as $fio => $userLeads) {

                    $curCountConv = intval($userLeads["SUCCESS"]["COUNT"] / (($userLeads["COUNT"]) ? $userLeads["COUNT"] : 1) * 100);
                    $curCallConv = intval(((($userLeads["CALL"]["SUCCESS"]) ? count($userLeads["CALL"]["SUCCESS"]) : 1 ) / (($userLeads["CALL"]) ? count($userLeads["CALL"]) : 1 ) * 100));
                    $curMailConv = intval(((($userLeads["EMAIL"]["SUCCESS"]) ? count($userLeads["EMAIL"]["SUCCESS"]) : 1 ) / (($userLeads["EMAIL"]) ? count($userLeads["EMAIL"]) : 1) * 100));
                    $curWebConv = intval(((($userLeads["WEB"]["SUCCESS"]) ? count($userLeads["WEB"]["SUCCESS"]) : 1 ) / (($userLeads["WEB"]) ? count($userLeads["WEB"]) : 1) * 100));
                    $curCarrotConv = intval(((($userLeads["7"]["SUCCESS"]) ? count($userLeads["7"]["SUCCESS"]) : 1 ) / (($userLeads["7"]) ? count($userLeads["7"]) : 1) * 100));
                    $curTrackerConv = intval(((($userLeads["FACE_TRACKER"]["SUCCESS"]) ? count($userLeads["FACE_TRACKER"]["SUCCESS"]) : 1 ) / (($userLeads["FACE_TRACKER"]) ? count($userLeads["FACE_TRACKER"]) : 1) * 100));
                    $curGeneratorConv = intval(((($userLeads["5"]["SUCCESS"]) ? count($userLeads["5"]["SUCCESS"]) : 1 ) / (($userLeads["5"]) ? count($userLeads["5"]) : 1) * 100));

                    if ($curCountConv <= $minCountConv) {
                        $minCountConv = $curCountConv;
                    }
                    if ($curCountConv >= $maxCountConv) {
                        $maxCountConv = $curCountConv;
                    }

                    if ($curCallConv <= $minCall) {
                        $minCall = $curCallConv;
                    }
                    if ($curCallConv >= $maxCall) {
                        $maxCall = $curCallConv;
                    }

                    if ($curMailConv <= $minEmail) {
                        $minEmail = $curMailConv;
                    }
                    if ($curMailConv >= $maxEmail) {
                        $maxEmail = $curMailConv;
                    }

                    if ($curWebConv <= $minWeb) {
                        $minWeb = $curWebConv;
                    }
                    if ($curWebConv >= $maxWeb) {
                        $maxWeb = $curWebConv;
                    }

                    if ($curCarrotConv <= $minCarrot) {
                        $minCarrot = $curCarrotConv;
                    }
                    if ($curCarrotConv >= $maxCarrot) {
                        $maxCarrot = $curCarrotConv;
                    }

                    if ($curTrackerConv <= $minTracker) {
                        $minTracker = $curTrackerConv;
                    }
                    if ($curTrackerConv >= $maxTracker) {
                        $maxTracker = $curTrackerConv;
                    }

                    if ($curGeneratorConv <= $minGenerator) {
                        $minGenerator = $curGeneratorConv;
                    }
                    if ($curGeneratorConv >= $maxGenerator) {
                        $maxGenerator = $curGeneratorConv;
                    }

                }

            ?>
            <?foreach ($leadOnUser["team2"] as $fio => $userLeads):?>
                <? 
                    $countlight ="";
                    $countCalllight = "";
                    $countEmaillight = "";
                    $countWeblight = "";
                    $countCarrotlight = "";
                    $countTrackerlight = "";
                    $countGeneratorlight = "";

                    $curCountConv = intval($userLeads["SUCCESS"]["COUNT"] / (($userLeads["COUNT"]) ? $userLeads["COUNT"] : 1) * 100);
                    $curCallConv = intval(((($userLeads["CALL"]["SUCCESS"]) ? count($userLeads["CALL"]["SUCCESS"]) : 1 ) / (($userLeads["CALL"]) ? count($userLeads["CALL"]) : 1 ) * 100));
                    $curMailConv = intval(((($userLeads["EMAIL"]["SUCCESS"]) ? count($userLeads["EMAIL"]["SUCCESS"]) : 1 ) / (($userLeads["EMAIL"]) ? count($userLeads["EMAIL"]) : 1) * 100));
                    $curWebConv = intval(((($userLeads["WEB"]["SUCCESS"]) ? count($userLeads["WEB"]["SUCCESS"]) : 1 ) / (($userLeads["WEB"]) ? count($userLeads["WEB"]) : 1) * 100));
                    $curCarrotConv = intval(((($userLeads["7"]["SUCCESS"]) ? count($userLeads["7"]["SUCCESS"]) : 1 ) / (($userLeads["7"]) ? count($userLeads["7"]) : 1) * 100));
                    $curTrackerConv = intval(((($userLeads["FACE_TRACKER"]["SUCCESS"]) ? count($userLeads["FACE_TRACKER"]["SUCCESS"]) : 1 ) / (($userLeads["FACE_TRACKER"]) ? count($userLeads["FACE_TRACKER"]) : 1) * 100));
                    $curGeneratorConv = intval(((($userLeads["5"]["SUCCESS"]) ? count($userLeads["5"]["SUCCESS"]) : 1 ) / (($userLeads["5"]) ? count($userLeads["5"]) : 1) * 100));

                    if ($curCountConv == $maxCountConv) {
                        $countlight = "green";
                    }
                    elseif ($curCountConv == $minCountConv) {
                        $countlight = "red"; 
                    }

                    if ($curCallConv == $maxCall)
                    {
                        $countCalllight = "green";
                    }
                    elseif ($curCallConv == $minCall ) {
                        $countCalllight = "red";
                    }

                    if ($curMailConv == $maxEmail)
                    {
                        $countEmaillight = "green";
                    }
                    elseif ($curMailConv == $minEmail) {
                        $countEmaillight = "red";
                    }

                    if ($curWebConv == $maxWeb)
                    {
                        $countWeblight = "green";
                    }
                    elseif ($curWebConv == $minWeb) {
                        $countWeblight = "red";
                    }
                    
                    if ($curCarrotConv == $maxCarrot)
                    {
                        $countCarrotlight = "green";
                    }
                    elseif ($curCarrotConv == $minCarrot) {
                        $countCarrotlight = "red";
                    }
                    
                    if ($curTrackerConv == $maxTracker)
                    {
                        $countTrackerlight = "green";
                    }
                    elseif ($curTrackerConv == $minTracker) {
                        $countTrackerlight = "red";
                    }
                    
                    if ($curGeneratorConv == $maxGenerator)
                    {
                        $countGeneratorlight = "green";
                    }
                    elseif ($curGeneratorConv == $minGenerator) {
                        $countGeneratorlight = "red";
                    }

                ?>
                <tr>
                    <td class="yellow">% Конверсии</td>
                    <td class="yellow"><?=$fio;?></td>
                    <td class="<?=$countlight;?>"><?=$curCountConv . " %";?></td>
                    <td class="<?=$countCalllight;?>"><?=intval(((($userLeads["CALL"]["SUCCESS"]) ? count($userLeads["CALL"]["SUCCESS"]) : 1 ) / (($userLeads["CALL"]) ? count($userLeads["CALL"]) : 1) * 100))." %";?></td>
                    <td class="<?=$countEmaillight;?>"><?=intval(((($userLeads["EMAIL"]["SUCCESS"]) ? count($userLeads["EMAIL"]["SUCCESS"]) : 1 ) / (($userLeads["EMAIL"]) ? count($userLeads["EMAIL"]) : 1) * 100))." %";?></td>
                    <td class="<?=$countWeblight;?>"><?=intval(((($userLeads["WEB"]["SUCCESS"]) ? count($userLeads["WEB"]["SUCCESS"]) : 1 ) / (($userLeads["WEB"]) ? count($userLeads["WEB"]) : 1) * 100))." %";?></td>
                    <td class="<?=$countCarrotlight;?>"><?=intval(((($userLeads["7"]["SUCCESS"]) ? count($userLeads["7"]["SUCCESS"]) : 1 ) / (($userLeads["7"]) ? count($userLeads["7"]) : 1) * 100))." %";?></td>
                    <td class="<?=$countTrackerlight;?>"><?=intval(((($userLeads["FACE_TRACKER"]["SUCCESS"]) ? count($userLeads["FACE_TRACKER"]["SUCCESS"]) : 1 ) / (($userLeads["FACE_TRACKER"]) ? count($userLeads["FACE_TRACKER"]) : 1) * 100))." %";?></td>
                    <td class="<?=$countGeneratorlight;?>"><?=intval(((($userLeads["5"]["SUCCESS"]) ? count($userLeads["5"]["SUCCESS"]) : 1 ) / (($userLeads["5"]) ? count($userLeads["5"]) : 1) * 100))." %";?></td>
                </tr>
            <?endforeach;?>
        </table>
    </div>

    <div style="height:50px;clear:both"></div>

    <div class="tablesTop tablesTop1 col-3" <?=(!array_key_exists('tablesTop1',$show) && $show[0] != "all" )  ? 'style="display:none"':'';?>>
        <h2>Топ ЛИДЫ</h2>
        <table class="dashtable top">
            <tr class="blue">
                <td>Топ по: </td>
                <td>Лиды</td>
            </tr>

            <?foreach ($allUsersLeadsCount as $user => $count):?>
                <tr>
                    <td><?=$count["TOTAL"];?></td>
                    <td><?=$user;?></td>
                </tr>
            <?endforeach;?>
        </table>
        <table class="dashtable top">
            <tr class="blue">
                    <td>Топ по: </td>
                    <td>Качественным</td>
            </tr>
            <?foreach ($allUsersLeadsCountSuccess as $user => $count):?>
                    <tr>
                        <td><?=$count["TOTAL"];?></td>
                        <td><?=$user;?></td>
                    </tr>
            <?endforeach;?>
        </table>
        <table class="dashtable top">
            <tr class="blue">
                    <td>Топ по: </td>
                    <td>Конвертации %</td>
            </tr>
            <?foreach ($allUsersLeadsCountSuccessConversion as $user => $count):?>
                    <tr>
                        <td><?=intval($count).' %';?></td>
                        <td><?=$user;?></td>
                    </tr>
            <?endforeach;?>
        </table>  
    </div>

    <div class="tablesTop tablesTop2 col-3" <?=(!array_key_exists('tablesTop2',$show) && $show[0] != "all" )  ? 'style="display:none"':'';?>>
        <h2>Топ Звонки</h2>
        <table class="dashtable top">
            <tr class="blue">
                <td>Топ по: </td>
                <td>ВХ.звонки (все)</td>
            </tr>
            <?foreach ($allUsersLeadsCountCALL as $user => $count):?>
                <tr>
                    <td><?=$count["TOTAL"];?></td>
                    <td><?=$user;?></td>
                </tr>
            <?endforeach;?>
        </table>
        <table class="dashtable top">
            <tr class="blue">
                    <td>Топ по: </td>
                    <td>ВХ.звонки (Качественные)</td>
            </tr>
            <?foreach ($allUsersLeadsCountCALLSUCCESS as $user => $count):?>
                    <tr>
                        <td><?=$count["TOTAL"];?></td>
                        <td><?=$user;?></td>
                    </tr>
            <?endforeach;?>
        </table>
        <table class="dashtable top">
            <tr class="blue">
                    <td>Топ по: </td>
                    <td>% Конвертации (Звонки)</td>
            </tr>
            <?foreach ($allUsersLeadsCountCALLSUCCESSConversion as $user => $count):?>
                    <tr>
                        <td><?=intval($count).' %';?></td>
                        <td><?=$user;?></td>
                    </tr>
            <?endforeach;?>
        </table>  
    </div>

    <div class="tablesTop tablesTop3 col-3" <?=(!array_key_exists('tablesTop3',$show) && $show[0] != "all" )  ? 'style="display:none"':'';?>>
        <h2>Топ ПОЧТА</h2>
        <table class="dashtable top">
            <tr class="blue">
                <td>Топ по: </td>
                <td>Почта (все)</td>
            </tr>
            <?foreach ($allUsersLeadsCountEMAIL as $user => $count):?>
                <tr>
                    <td><?=$count["TOTAL"];?></td>
                    <td><?=$user;?></td>
                </tr>
            <?endforeach;?>
        </table>
        <table class="dashtable top">
            <tr class="blue">
                    <td>Топ по: </td>
                    <td>Почта (Качественные)</td>
            </tr>
            <?foreach ($allUsersLeadsCountEMAILSUCCESS as $user => $count):?>
                    <tr>
                        <td><?=$count["TOTAL"];?></td>
                        <td><?=$user;?></td>
                    </tr>
            <?endforeach;?>
        </table>
        <table class="dashtable top">
            <tr class="blue">
                    <td>Топ по: </td>
                    <td>% Конвертации (Почта)</td>
            </tr>
            <?foreach ($allUsersLeadsCountEMAILSUCCESSConversion as $user => $count):?>
                    <tr>
                        <td><?=intval($count).' %';?></td>
                        <td><?=$user;?></td>
                    </tr>
            <?endforeach;?>
        </table>  
    </div>

    <div class="tablesTop tablesTop4 col-3" <?=(!array_key_exists('tablesTop4',$show) && $show[0] != "all" )  ? 'style="display:none"':'';?>>
        <h2>Топ Корзина</h2>
        <table class="dashtable top">
            <tr class="blue">
                <td>Топ по: </td>
                <td>Корзина (все)</td>
            </tr>
            <?foreach ($allUsersLeadsCountWEB as $user => $count):?>
                <tr>
                    <td><?=$count["TOTAL"];?></td>
                    <td><?=$user;?></td>
                </tr>
            <?endforeach;?>
        </table>
        <table class="dashtable top">
            <tr class="blue">
                    <td>Топ по: </td>
                    <td>Корзина (Качественные)</td>
            </tr>
            <?foreach ($allUsersLeadsCountWEBSUCCESS as $user => $count):?>
                    <tr>
                        <td><?=$count["TOTAL"];?></td>
                        <td><?=$user;?></td>
                    </tr>
            <?endforeach;?>
        </table>
        <table class="dashtable top">
            <tr class="blue">
                    <td>Топ по: </td>
                    <td>% Конвертации (Корзина)</td>
            </tr>
            <?foreach ($allUsersLeadsCountWEBSUCCESSConversion as $user => $count):?>
                    <tr>
                        <td><?=intval($count).' %';?></td>
                        <td><?=$user;?></td>
                    </tr>
            <?endforeach;?>
        </table>  
    </div>

    <div class="tablesTop tablesTop5 col-3" <?=(!array_key_exists('tablesTop5',$show) && $show[0] != "all" )  ? 'style="display:none"':'';?>>
        <h2>Топ Морковь</h2>
        <table class="dashtable top">
            <tr class="blue">
                <td>Топ по: </td>
                <td>Морковь (все)</td>
            </tr>
            <?foreach ($allUsersLeadsCountCARROT as $user => $count):?>
                <tr>
                    <td><?=$count["TOTAL"];?></td>
                    <td><?=$user;?></td>
                </tr>
            <?endforeach;?>
        </table>
        <table class="dashtable top">
            <tr class="blue">
                    <td>Топ по: </td>
                    <td>Морковь (Качественные)</td>
            </tr>
            <?foreach ($allUsersLeadsCountCARROTSUCCESS as $user => $count):?>
                    <tr>
                        <td><?=$count["TOTAL"];?></td>
                        <td><?=$user;?></td>
                    </tr>
            <?endforeach;?>
        </table>
        <table class="dashtable top">
            <tr class="blue">
                    <td>Топ по: </td>
                    <td>% Конвертации (Морковь)</td>
            </tr>
            <?foreach ($allUsersLeadsCountCARROTSUCCESSConversion as $user => $count):?>
                    <tr>
                        <td><?=intval($count).' %';?></td>
                        <td><?=$user;?></td>
                    </tr>
            <?endforeach;?>
        </table>  
    </div>

</div>

<?if (!$_REQUEST["AJAX_LOAD"]):?>
<div>
<?endif;?>

<style>
    .dashtable,.dashtable tr td {
        border-collapse: collapse;
        border:1px solid black;
        padding:5px;
    }
    .blue {
        background-color: skyblue;
    }
    .green {
        background-color: lime;
    }
    .red {
        background-color: red;
    }
    .yellow {
        background-color: yellow;
    }
    .right {
        text-align: right;
    }
    .bPanel {
        padding:10px;
        margin:40px 10px;

    }
    .bPanel a,.button {
        border:0px;
        padding:10px;
        background-color: #2fc6f6;
        color:white;
        cursor:pointer;
        text-decoration: none;
    }
    .tablesTop table.dashtable {
        margin-bottom: 30px;
    }
    .tablesTop table.dashtable td:first-child { 
        width:100px;
    }
    .tablesTop table.dashtable td:nth-child(2) { 
        width:200px;
    }
    .col-3 h2{
        position:absolute;
        margin-top: -40px;
    }
    .col-3 {
        display:flex;
        gap:60px;
        margin: 40px 0;
    }
    .settingsPanel {
        background-color: #EEF2F4;
        padding:20px;
        margin-bottom:20px;
    }
    .settingsPanel input {
        margin:10px 5px;
    }
    .settingsPanel input[type="checkbox"] {
        width:15px;
        height:15px;
    }
    .settingsPanel .button{
        margin-left:20px;
    }
    .top tr:nth-child(2){
        background-color: lime;
    }
    .top tr:last-child{
        background-color: red;
    }
</style>

<? if ($_REQUEST["AJAX_LOAD"] == "Y") { die(); } ?>
<script>

    $('.checkallbox').click(function(){
        $('.settingsForm input[type="checkbox"]').each(function(){
                $(this).prop('checked',true);
        });
    });

    function refreshDash(){
        let show = [];
        $('.tables div').each(function(){
            if ($(this).is(":visible")) {
                show.push($(this).attr('class'));
            }
        });
        $.ajax({
                url: window.location.href,
                method: 'get',
                dataType: 'html',
                data: {AJAX_LOAD: "Y",SHOW:show},
                success: function(data){
                    $('.LoadContent').html(data);
                 //   console.log(data);
                }
            });
    };

    let refreshTime = <?=$refreshTime;?>;
    if (refreshTime > 0) {
        setInterval(function(){
            refreshDash();
        }, refreshTime);
    };
    
    function showTable(tid){
        if (tid == 'all') {
           $('.tables table').each(function(){
            $(this).fadeToggle('slow');
           });
        }
        else {
            $('.table'+tid).fadeToggle('slow'); 
        }
    };
    
</script>
