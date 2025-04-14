<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$APPLICATION->ShowHead();
?>
<div class="panel">
    <form class="form">
        <div class="section">
            <label for="oneMoreRequisite">Показать компании у которых больше 1 реквизита</label>
            <input name="oneMoreRequisite" type="checkbox" value="Y">
        </div>
        <div class="section">
            <label for="emptyRequisitesINNKPP">Показать компании у которых не заполнен ИНН/КПП</label>
            <input name="emptyRequisitesINNKPP" type="checkbox" value="Y">
        </div>
        <div class="section">
            <label for="duplicateINNKPP">Показать компании с одинаковыми ИНН/КПП</label>
            <input name="duplicateINNKPP" type="checkbox" value="Y">
        </div>
        <button class="ui-btn-main" type="submit">Показать</button>
    </form>
</div>
<?
if ($_REQUEST["oneMoreRequisite"]) {
    showOneMoreRequisites();
}
if ($_REQUEST["emptyRequisitesINNKPP"]) {
    emptyRequisitesINNKPP();
}

if ($_REQUEST["duplicateINNKPP"]){
    duplicateINNKPP();
}

function duplicateINNKPP() {
?>
    <table class="table">
        <thead>
            <td>ID реквизита</td>
            <td>Название реквизита</td>
            <td>Название компании в реквизитах</td>
            <td>ИНН в реквизите</td>
            <td>КПП в реквизите</td>
            <td>ID компании</td>
            <td>Название компании</td>
        </thead>
<?
    $res = \Bitrix\Crm\CompanyTable::query()
    ->setSelect(['ID', 'TITLE'])
    //->where('ID', 55377)
    //->setLimit('10')
    /* ->addFilter("!UF_CRM_1697715810","Не найдено")
    ->addFilter("=UF_CRM_1697715781"," ") */
    ->exec();
    $rescomp = $res->fetchAll();

    foreach($rescomp as $company) {
        $companies[$company["ID"]] = $company; 
    }


    $query = \Bitrix\Crm\RequisiteTable::query()
    ->setSelect(["ID","NAME","RQ_INN","RQ_KPP","RQ_COMPANY_NAME","ENTITY_ID"])
    //->where("ENTITY_TYPE_ID",4)
    //->where("PRESET_ID",1)
    ->exec();
    $reqs = $query->fetchAll();

    foreach ($reqs as $req) {
        if (!empty($req["RQ_INN"]) && !empty($req["RQ_KPP"])) {
            $fields = [
                "REQ_ID" => $req["ID"],
                "NAME" => $req["NAME"],
                "RQ_COMPANY_NAME" => $req["RQ_COMPANY_NAME"],
                "RQ_INN" => $req["RQ_INN"],
                "RQ_KPP" => $req["RQ_KPP"],
                "COMPANY_ID" => $companies[$req["ENTITY_ID"]]["ID"],
                "COMPANY_NAME" => $companies[$req["ENTITY_ID"]]["TITLE"],
            ];
            $duplicates[$req["RQ_INN"].'_'.$req["RQ_KPP"]][] = $fields;
        }
    }

    $fp = fopen($_SERVER["DOCUMENT_ROOT"] . '/duplicateINNKPP.csv', 'w');
    fputcsv($fp, mb_convert_encoding(["ID Реквизита", "Название реквизита", "Название компании в реквизитах","ИНН в реквизите","КПП в реквизите","ID компании","Название компании"], 'windows-1251', 'utf-8'), ";");
    $count = 0;
    foreach ($duplicates as $duplicate) {
        if (count($duplicate) > 1) {
            foreach ($duplicate as $dupl) {
                $count++;
                $fields = [
                    "REQ_ID" => mb_convert_encoding($dupl["REQ_ID"], 'windows-1251', 'utf-8'),
                    "NAME" => mb_convert_encoding($dupl["NAME"], 'windows-1251', 'utf-8'),
                    "RQ_COMPANY_NAME" => mb_convert_encoding($dupl["RQ_COMPANY_NAME"], 'windows-1251', 'utf-8'),
                    "RQ_INN" => mb_convert_encoding($dupl["RQ_INN"], 'windows-1251', 'utf-8'),
                    "RQ_KPP" => mb_convert_encoding($dupl["RQ_KPP"], 'windows-1251', 'utf-8'),
                    "COMPANY_ID" => mb_convert_encoding($dupl["COMPANY_ID"], 'windows-1251', 'utf-8'),
                    "COMPANY_NAME" => mb_convert_encoding($dupl["COMPANY_NAME"], 'windows-1251', 'utf-8'),
                ];
                fputcsv($fp, $fields, ";");
                echo ('<tr>
                <td>'.$dupl["REQ_ID"].'</td>
                <td>'.$dupl["NAME"].'</td>
                <td>'.$dupl["RQ_COMPANY_NAME"].'</td>
                <td>'.$dupl["RQ_INN"].'</td>
                <td>'.$dupl["RQ_KPP"].'</td>
                <td>'.$dupl["COMPANY_ID"].'</td>
                <td>'.$dupl["COMPANY_NAME"].'</td>
                </tr>'); 
            }
        }
    }
    echo 'Всего найдено: '.$count.' <a href="/duplicateINNKPP.csv">Скачать список</a><br><br>';
}

function emptyRequisitesINNKPP() {

    $fp = fopen($_SERVER["DOCUMENT_ROOT"] . '/emptyINN.csv', 'w');
    fputcsv($fp, mb_convert_encoding(["ID Реквизита", "ID сущности", "ID Типа Сущности", "Название реквизита", "Название компании в реквизитах","ИНН в реквизите","КПП в реквизите"], 'windows-1251', 'utf-8'), ";");
    
    $query = \Bitrix\Crm\RequisiteTable::query()
    ->setSelect(["ID","NAME","RQ_INN","RQ_KPP","RQ_COMPANY_NAME","ENTITY_ID","ENTITY_TYPE_ID"])
    ->where("RQ_INN"," ")
    //->where("PRESET_ID",1)
    ->exec();
    $reqs = $query->fetchAll();

    $query = \Bitrix\Crm\RequisiteTable::query()
    ->setSelect(["ID","NAME","RQ_INN","RQ_KPP","RQ_COMPANY_NAME","ENTITY_ID","ENTITY_TYPE_ID"])
    ->where("RQ_INN","0")
    //->where("PRESET_ID",1)
    ->exec();
    $reqsZeroInn = $query->fetchAll();

    $query = \Bitrix\Crm\RequisiteTable::query()
    ->setSelect(["ID","NAME","RQ_INN","RQ_KPP","RQ_COMPANY_NAME","ENTITY_ID","ENTITY_TYPE_ID"])
    ->where("RQ_KPP"," ")
    ->where("PRESET_ID",1)
    ->exec();
    $reqsKpp = $query->fetchAll();

    $query = \Bitrix\Crm\RequisiteTable::query()
    ->setSelect(["ID","NAME","RQ_INN","RQ_KPP","RQ_COMPANY_NAME","ENTITY_ID","ENTITY_TYPE_ID"])
    ->where("RQ_KPP","0")
    ->where("PRESET_ID",1)
    ->exec();
    $reqsZeroKpp = $query->fetchAll();
    
    $count = 0;
    $fp = fopen($_SERVER["DOCUMENT_ROOT"] . '/emptyINN.csv', 'w');
    
    ?>
    <table class="table">
        <thead>
            <td>ID реквизита</td>
            <td>ID сущности</td>
            <td>ID типа сущности</td>
            <td>Название реквизита</td>
            <td>Название компании в реквизитах</td>
            <td>ИНН в реквизите</td>
            <td>КПП в реквизите</td>
        </thead>
<?
    foreach ($reqs as $req) {
        $count++;
        $fields = [
            'REQ_ID' => mb_convert_encoding($req["ID"], 'windows-1251', 'utf-8'),
            'ENTITY_ID' => mb_convert_encoding($req["ENTITY_ID"], 'windows-1251', 'utf-8'),
            'ENTITY_TYPE_ID' => mb_convert_encoding($req["ENTITY_TYPE_ID"], 'windows-1251', 'utf-8'),
            'RQ_NAME' => mb_convert_encoding($req["NAME"], 'windows-1251', 'utf-8'),
            'RQ_COMPANY_NAME' => mb_convert_encoding($req["RQ_COMPANY_NAME"], 'windows-1251', 'utf-8'),
            'RQ_INN' => mb_convert_encoding($req["RQ_INN"], 'windows-1251', 'utf-8'),
            'RQ_KPP' => mb_convert_encoding($req["RQ_KPP"], 'windows-1251', 'utf-8'),
        ];
        fputcsv($fp, $fields, ";");
        echo ('<tr>
        <td>'.$req["ID"].'</td>
        <td>'.$req["ENTITY_ID"].'</td>
        <td>'.$req["ENTITY_TYPE_ID"].'</td>
        <td>'.$req["NAME"].'</td>
        <td>'.$req["RQ_COMPANY_NAME"].'</td>
        <td>'.$req["RQ_INN"].'</td>
        <td>'.$req["RQ_KPP"].'</td>
        </tr>');           
    }

    foreach ($reqsZeroInn as $req) {
        $count++;
        $fields = [
            'REQ_ID' => mb_convert_encoding($req["ID"], 'windows-1251', 'utf-8'),
            'ENTITY_ID' => mb_convert_encoding($req["ENTITY_ID"], 'windows-1251', 'utf-8'),
            'ENTITY_TYPE_ID' => mb_convert_encoding($req["ENTITY_TYPE_ID"], 'windows-1251', 'utf-8'),
            'RQ_NAME' => mb_convert_encoding($req["NAME"], 'windows-1251', 'utf-8'),
            'RQ_COMPANY_NAME' => mb_convert_encoding($req["RQ_COMPANY_NAME"], 'windows-1251', 'utf-8'),
            'RQ_INN' => mb_convert_encoding($req["RQ_INN"], 'windows-1251', 'utf-8'),
            'RQ_KPP' => mb_convert_encoding($req["RQ_KPP"], 'windows-1251', 'utf-8'),
        ];
        fputcsv($fp, $fields, ";"); 
        echo ('<tr>
        <td>'.$req["ID"].'</td>
        <td>'.$req["ENTITY_ID"].'</td>
        <td>'.$req["ENTITY_TYPE_ID"].'</td>
        <td>'.$req["NAME"].'</td>
        <td>'.$req["RQ_COMPANY_NAME"].'</td>
        <td>'.$req["RQ_INN"].'</td>
        <td>'.$req["RQ_KPP"].'</td>
        </tr>');                  
    }

    foreach ($reqsKpp as $req) {
        $count++;
        $fields = [
            'REQ_ID' => mb_convert_encoding($req["ID"], 'windows-1251', 'utf-8'),
            'ENTITY_ID' => mb_convert_encoding($req["ENTITY_ID"], 'windows-1251', 'utf-8'),
            'ENTITY_TYPE_ID' => mb_convert_encoding($req["ENTITY_TYPE_ID"], 'windows-1251', 'utf-8'),
            'RQ_NAME' => mb_convert_encoding($req["NAME"], 'windows-1251', 'utf-8'),
            'RQ_COMPANY_NAME' => mb_convert_encoding($req["RQ_COMPANY_NAME"], 'windows-1251', 'utf-8'),
            'RQ_INN' => mb_convert_encoding($req["RQ_INN"], 'windows-1251', 'utf-8'),
            'RQ_KPP' => mb_convert_encoding($req["RQ_KPP"], 'windows-1251', 'utf-8'),
        ];
        fputcsv($fp, $fields, ";");
        echo ('<tr>
        <td>'.$req["ID"].'</td>
        <td>'.$req["ENTITY_ID"].'</td>
        <td>'.$req["ENTITY_TYPE_ID"].'</td>
        <td>'.$req["NAME"].'</td>
        <td>'.$req["RQ_COMPANY_NAME"].'</td>
        <td>'.$req["RQ_INN"].'</td>
        <td>'.$req["RQ_KPP"].'</td>
        </tr>');        
    }

    foreach ($reqsZeroKpp as $req) {
        $count++;
        $fields = [
            'REQ_ID' => mb_convert_encoding($req["ID"], 'windows-1251', 'utf-8'),
            'ENTITY_ID' => mb_convert_encoding($req["ENTITY_ID"], 'windows-1251', 'utf-8'),
            'ENTITY_TYPE_ID' => mb_convert_encoding($req["ENTITY_TYPE_ID"], 'windows-1251', 'utf-8'),
            'RQ_NAME' => mb_convert_encoding($req["NAME"], 'windows-1251', 'utf-8'),
            'RQ_COMPANY_NAME' => mb_convert_encoding($req["RQ_COMPANY_NAME"], 'windows-1251', 'utf-8'),
            'RQ_INN' => mb_convert_encoding($req["RQ_INN"], 'windows-1251', 'utf-8'),
            'RQ_KPP' => mb_convert_encoding($req["RQ_KPP"], 'windows-1251', 'utf-8'),
        ];
        fputcsv($fp, $fields, ";");
        echo ('<tr>
        <td>'.$req["ID"].'</td>
        <td>'.$req["ENTITY_ID"].'</td>
        <td>'.$req["ENTITY_TYPE_ID"].'</td>
        <td>'.$req["NAME"].'</td>
        <td>'.$req["RQ_COMPANY_NAME"].'</td>
        <td>'.$req["RQ_INN"].'</td>
        <td>'.$req["RQ_KPP"].'</td>
        </tr>');
    }
    echo 'Всего найдено: '.$count.' <a href="/emptyINN.csv">Скачать список</a><br><br>';
}

function showOneMoreRequisites() {
    $res = \Bitrix\Crm\CompanyTable::query()
    ->setSelect(['ID', 'TITLE'])
    //->where('ID', 55377)
    //->setLimit('10')
    /* ->addFilter("!UF_CRM_1697715810","Не найдено")
    ->addFilter("=UF_CRM_1697715781"," ") */
    ->exec();
    $companies = $res->fetchAll();

    foreach ($companies as $key => &$company) {
        $query = \Bitrix\Crm\RequisiteTable::query()
        ->setSelect(["ID","NAME","RQ_INN","RQ_KPP","RQ_COMPANY_NAME"])
        ->where("ENTITY_ID",$company["ID"])
        //->where("PRESET_ID",1)
        ->exec();
        $reqs = $query->fetchAll();
        foreach ($reqs as $req) {
            $company["REQUSITES"][] = $req;
        }
    }
    $count = 0;
    $fp = fopen($_SERVER["DOCUMENT_ROOT"] . '/requisiteDuplicate.csv', 'w');
    fputcsv($fp, mb_convert_encoding(["ID Компании", "Название компании", "ID реквизита", "Название реквизита", "Название компании в реквизитах"], 'windows-1251', 'utf-8'), ";");
    ?>
    <table class="table">
        <thead>
            <td>ID Компании</td>
            <td>Название компании</td>
            <td>ID реквизита</td>
            <td>Название реквизита</td>
            <td>Название компании в реквизитах</td>
        </thead>
    <?
    foreach ($companies as $company) {
        if (is_array($company["REQUSITES"]) && count($company["REQUSITES"]) > 1) {
            $count++;
            foreach ($company["REQUSITES"] as $key => $req) {
                $fields = [
                    'COMPANY_ID' => mb_convert_encoding($company["ID"], 'windows-1251', 'utf-8'),
                    'COMPANY_NAME' => mb_convert_encoding($company["TITLE"], 'windows-1251', 'utf-8'),
                    'REQ_ID' => mb_convert_encoding($req["ID"], 'windows-1251', 'utf-8'),
                    'RQ_NAME' => mb_convert_encoding($req["NAME"], 'windows-1251', 'utf-8'),
                    'RQ_COMPANY_NAME' => mb_convert_encoding($req["RQ_COMPANY_NAME"], 'windows-1251', 'utf-8'),
                ];
                fputcsv($fp, $fields, ";");
                echo ('<tr>
                <td>'.$company["ID"].'</td>
                <td>'.$company["TITLE"].'</td>
                <td>'.$req["ID"].'</td>
                <td>'.$req["NAME"].'</td>
                <td>'.$req["RQ_COMPANY_NAME"].'</td>
                </tr>');
            }         
        }
    }
    echo 'Всего обнаружено компаний с несколькими реквизитами: <span class="count">'.$count.'</span><br><a href="/requisiteDuplicate.csv">Скачать список</a><br><br>';
}
?>

<style>
.table,table tr,table td {
    border: 1px solid gray;
    border-collapse: collapse;
}
.table td {
    padding:5px;
}
.panel {
    background-color: #ccf2ff;
    padding:15px;
}
.form .section{
    margin:0 20px;
}
.form {
    display: flex;
}
</style>