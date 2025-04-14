<?php
namespace Hs\Ajax;

class Company
{
    public static function firedContact() {
        $type = $_REQUEST["type"];
        $uid = $_REQUEST["uid"];
        $eid = $_REQUEST["eid"];
        $cid = $_REQUEST["cid"];

       ($type == "fired") ? $bptype = "Уволился" : $bptype = "Отвязать";

        \CBPDocument::StartWorkflow(
            478,
            array("crm", 'CCrmDocumentCompany', 'COMPANY_' . $eid),
            [ "TargetUser" => "user_".$uid, "typeStartBP" => $bptype, "selectContactId" => $cid ],
            $arErrorsTmp
        );
    }

    public static function setMain() {
        $hId = $_REQUEST["hId"];
        $cId = $_REQUEST["cId"];

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(CRM_SMART["holding"]);
        $items = $factory->getItems([
            'filter' => ['ID' => $hId ],
            'select' => ['COMPANY_ID'],
        ]);

        foreach ($items as $item) {
            $companyID = $item->getData()["COMPANY_ID"];
        }

        $hfields = [
            "COMPANY_ID" => $cId
        ];
        $class = $factory->getDataClass();
        $class::update($hId,$hfields);
        
        
        if (!empty($companyID)) {
            $fields = ["PARENT_ID_".CRM_SMART["holding"] => $hId];
            (new \CCrmCompany(false))->update($companyID,$fields);
        }

        $query = \Bitrix\Crm\CompanyTable::query()
        ->setSelect(["ID","TITLE"])
        ->setFilter(["ID" => [$cId,$companyID] ])
        ->exec();
        $companies = $query->fetchAll();
        
        $companyName =[];
        foreach ($companies as $company) {
            $companyName[$company["ID"]] = $company["TITLE"];
        }

        global $USER;
        $authorID = $USER->GetID();
        if (!empty($companyID)) {
            \BPFunctions::addComment("Основная компания изменена с ".$companyName[$companyID].' на '. $companyName[$cId],$authorID,CRM_SMART["holding"],$hId);
        }
        else {
            \BPFunctions::addComment("Установлена основная компания на ".$companyName[$cId],$authorID,CRM_SMART["holding"],$hId);
        }
    }

    public static function unlinkHolding() {
        $hId = $_REQUEST["hId"];
        $cId = $_REQUEST["cId"];

/*         $fields = [
            "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
            "SRC_ENTITY_ID" => $hId,
            "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
            "DST_ENTITY_ID" => $cId,
        ]; 
        $addRes = \Bitrix\Crm\Relation\EntityRelationTable::Delete($fields); */
        $fields = ["PARENT_ID_".CRM_SMART["holding"] => 0];
        (new \CCrmCompany(false))->update($cId,$fields);

        $query = \Bitrix\Crm\CompanyTable::query()
        ->setSelect(["ID","TITLE"])
        ->setFilter(["ID" => $cId ])
        ->exec();
        $companies = $query->fetchAll();
        
        $companyName =[];
        foreach ($companies as $company) {
            $companyName[$company["ID"]] = $company["TITLE"];
        }

        global $USER;
        $authorID = $USER->GetID();
        
        \BPFunctions::addComment("Отвязана компания ".$companyName[$cId],$authorID,CRM_SMART["holding"],$hId);

        $query = \Bitrix\Crm\Relation\EntityRelationTable::query()
        ->addSelect("DST_ENTITY_ID")
        ->setFilter([
            "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
            "SRC_ENTITY_ID" => $hId,
            "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
        ])
        ->exec();
        $exist = $query->fetchAll();

        if (empty($exist)) {
            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(CRM_SMART["holding"]);
            $items = $factory->getItems([
                'filter' => ['ID' => $hId ],
                'select' => ['ID'],
            ]);
            $items[0]->set("COMPANY_ID", 0);
            $items[0]->save();
        }

    }

    public static function linkToHolding() {
        $hId = $_REQUEST["hId"];
        $cId = $_REQUEST["cId"];

/*         $fields = [
            "RELATION_TYPE" => "BINDING",
            "SRC_ENTITY_TYPE_ID" => CRM_SMART["holding"],
            "SRC_ENTITY_ID" => $hId,
            "DST_ENTITY_TYPE_ID" => \CCrmOwnerType::Company,
            "DST_ENTITY_ID" => $cId,
        ]; 
        $addRes = \Bitrix\Crm\Relation\EntityRelationTable::Add($fields); */

        $fields = ["PARENT_ID_".CRM_SMART["holding"] => $hId];
        (new \CCrmCompany(false))->update($cId,$fields);

    }

    public static function getCustomDocsForDeal() {

        $id = $_REQUEST["PARAMS"]["id"];
        $cId = $_REQUEST["PARAMS"]["companyID"];

        if ($cId == 'undefined') {
            $cId = str_replace(["company_","_details"],"",$id);
        }

        if (!empty($_REQUEST["PARAMS"]["template"]["companyID"])) {
            $cmpids = explode(",",$_REQUEST["PARAMS"]["template"]["companyID"]);
            foreach ($cmpids as $cmpid) {
                if ( self::printTable($cmpid)["count"] == 0 ) {
                    $emptyDocs[] = self::printTable($cmpid)["html"];
                }
                else {
                    $docs[] = self::printTable($cmpid)["html"];
                }
            }
        }
        else {
            echo self::printTable($cId)["html"];
        }

        foreach ($docs as $doc) {
            echo $doc;
        }

        foreach ($emptyDocs as $edoc) {
            echo $edoc;
        }

    }

    public static function searchByInn() {
        $inn = $_REQUEST["inn"];

        \Bitrix\Main\Loader::includeModule('crm');

        $res = \Bitrix\Crm\RequisiteTable::query()
            ->setSelect(['ID',"ENTITY_ID"])
            ->setFilter(["RQ_INN" => $inn])
            ->exec();
        $cId = $res->fetch()["ENTITY_ID"];

        $dbCompany = \CCrmCompany::GetListEx(
            [
                'ID' => 'DESC'
            ],
            [
                "ID" => $cId,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            ['COMPANY_TYPE',CRM_SETTINGS["company"]["kppcompany"],CRM_SETTINGS["company"]["companyBrand"],CRM_SETTINGS["company"]["deliveryAddress"],CRM_SETTINGS["company"]["legalAddress"]]
        );
        if ($cRes = $dbCompany->fetch()) {
            $fields = [
                "kpp" => $cRes[CRM_SETTINGS["company"]["kppcompany"]],
                "type" => $cRes["COMPANY_TYPE"],
                "brand" => $cRes[CRM_SETTINGS["company"]["companyBrand"]],
                "daddress" => $cRes[CRM_SETTINGS["company"]["deliveryAddress"]],
                "laddress" => $cRes[CRM_SETTINGS["company"]["legalAddress"]]
            ];
            echo json_encode($fields);
        }
    
    }
    public static function searchByInnKppGetComment() {
        $arfields = [
            CRM_SETTINGS['lead']['companyInn'] => $_REQUEST["inn"],
            CRM_SETTINGS['lead']['companyKpp'] => $_REQUEST["kpp"],
        ];
            $res = \Dbbo\Event\LeadEvent::searchCompanyByRequisite($arfields);
            echo json_encode(["comment" => $res["comment"]]);
        
    }

    private static function printTable($cId) {
        //start table
        $show = true;
        $cache = \Bitrix\Main\Data\Cache::createInstance(); 
        $hashInn = "CustomDocsForDealINN_".$cId;
        $hashCname = "CRMCustomDocsForDealCNAME_".$cId;
        
        if ($cache->initCache(86400, $hashInn, "CRMCustomDocsForDealINN")) { 
            $reqINN = $cache->getVars()[$hashInn];
        }
        elseif ($cache->startDataCache()) {
            //получим инн из реквизита компании
            $query = \Bitrix\Crm\RequisiteTable::query()
            ->setSelect(["RQ_INN","NAME"])
            ->where("ENTITY_TYPE_ID", 4)
            ->where("ENTITY_ID",$cId)
            ->exec();
            $reqINN = $query->fetch()["RQ_INN"];
            
            if (!empty($reqINN)) {
                $cache->endDataCache(
                    array(
                        $hashInn => $reqINN
                    )
                ); 
            }
        }

        if ($cache->initCache(86400, $hashCname, "CRMCustomDocsForDealCNAME")) { 
            $cName = $cache->getVars()[$hashCname];
        }
        elseif ($cache->startDataCache()) {
            //получим название компании
            $res = \Bitrix\Crm\CompanyTable::query()
            ->setSelect(['TITLE'])
            ->setFilter(["ID" => $cId])
            ->exec();
            $cName = $res->fetchAll()[0]["TITLE"]; 
                        
            $cache->endDataCache(
                array(
                    $hashCname => $cName
                )
            ); 
        }

        $url = 'http://192.168.25.166/1C_UT/hs/osn_exchange/POSTSalesINN';
        $username = 'support1C_Bitrix';
        $password = '123';
        $data['INN'] = $reqINN;

        $hash = "CustomDocsForDeal_".$reqINN;
        
        if ($cache->initCache(3600, $hash, "CRMCustomDocsForDeal")) { 
            $curlResult = $cache->getVars()[$hash];
        }
        elseif ($cache->startDataCache()) {
                
            // Инициализация cURL
            $curlInit = curl_init($url);
            // Установка параметров запроса
            curl_setopt($curlInit, CURLOPT_POST, true);
            curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curlInit, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curlInit, CURLOPT_USERPWD, "$username:$password");
            // Получение ответа
            $response = curl_exec($curlInit);
            // закрываем CURL
            curl_close($curlInit);

            $curlResult = json_decode($response, true);

            if (!empty($curlResult)) {
                $cache->endDataCache(
                    array(
                        $hash => $curlResult
                    )
                ); 
            }
        } 

        $lcur = \CCurrency::GetList(($by="name"), ($order="asc"), 'ru');
        while($lcur_res = $lcur->Fetch())
        {
            $currencyList[$lcur_res["NUMCODE"]] = $lcur_res;
        }

        foreach ($curlResult['result'][0]["Sales"] as $value) {
            $docs[$value["SaleNumber"]." ".$value["SaleDate"]]["items"][] = $value;
        }

        //d($docs);
        if (!empty($curlResult['result'][0]["Sales"])) {
            ob_start();
        }
        else {
            if ($curlResult['status'] != "ok"){
                $show = false;
                $html = '<div style="margin-bottom:15px">'.$cName.' <span style="color:red;font-weight:bold">Нет ответа от сервера</span></div>';
                return [
                    "html" => $html,
                    "count" => 0
                ];
            }
            else if ($curlResult['status'] == "ok" && empty($curlResult['result'][0]["Sales"])) {
                $html = '<div style="margin-bottom:15px">'.$cName.' <span style="color:red;font-weight:bold">Заказы не найдены</span></div>';
                $show = false;
                return [
                    "html" => $html,
                    "count" => 0
                ];
            }
        }
?>

<table class="simpleTable">
    <thead>
        <tr>
            <th colspan="7" class="left"><?=$cName;?> <? if ($show):?><span style="color:blue;cursor:pointer" onclick="toggleTable(this);">Свернуть</span> <?endif;?> <? if ($error) {echo '('.$error.')';}?></th>
        </tr>
<? if (!$show):?>
    </thead></table>
<?endif;?>
<? if ($show):?>
    <tbody>
        <tr>
            <th colspan="7" class="left">№ Заказа   Дата Заказа</th>
        </tr>
    </thead>

    <thead>
        <tr>
            <th colspan="1"  class="w2">п.п.</th>
            <th colspan="1"  class="w20">Артикул</th>
            <th colspan="1"  class="w30">Название</th>
            <th colspan="1"  class="w10">Кол-во</th>
            <th colspan="1"  class="w10">Цена за ед.</th>
            <th colspan="1"  class="w10">Сумма</th>
            <th colspan="1"  class="w10">Валюта</th>
        </tr>
    </thead>
    <?foreach($docs as $doc):?>
        <tr style="background-color:#c0d4df">
            <td colspan="4" class="bold"><?=current($doc["items"])["SaleNumber"];?>             <?
            $objDateTime = new \DateTime(current($doc["items"])["SaleDate"]);
            $dateformate = $objDateTime->format("d.m.Y H:m");
            echo $dateformate;
            ?></td>
            <td colspan="1"></td>
            <td colspan="1" class="bold">
                <? $summ = 0;
                foreach($doc["items"] as $item){
                    $summ += $item["Amount"];
                }
                ?>
                <?=CurrencyFormatNumber($summ, 'RUB');?>
            </td>
            <td colspan="1"></td>
        </tr>
        <?foreach($doc["items"] as $key => $item):?>
            <?$key++;?>
            <tr>
                <td colspan="1" class="center w2"><?=$key;?></td>
                <td colspan="1" class="left w20"><?=$item["Sku"];?></td>
                <td colspan="1" class="left w30"><?=$item["Name"];?></td>
                <td colspan="1" class="center w10"><?=$item["Quantity"];?></td>
                <td colspan="1" class="center w10"><?=CurrencyFormatNumber($item["Price"], 'RUB');?></td>
                <td colspan="1" class="center w10"><?=CurrencyFormatNumber($item["Amount"], 'RUB');?></td>
                <td colspan="1" class="center w10"><?=$currencyList[$item["Curency"]]["FULL_NAME"];?></td>
            </tr>
        <?endforeach;?>
    <?endforeach;?>
    </tbody>
</table>
<link href="/local/css/tables.css" type="text/css"  rel="stylesheet" >
<script>
    $.fn.extend({
        toggleText: function(a, b){
            return this.text(this.text() == b ? a : b);
        }
    });
    function toggleTable(e){
        $(e).toggleText('Свернуть', 'Развернуть');
        $(e).closest('table').find('tbody').slideToggle();
    }
</script>
<?endif;?>
<?
    $html = ob_get_clean();
    return [
        "result" => 1,
        "html" => $html,
        "count" =>count($curlResult['result'][0]["Sales"])
    ];
    
    //end table
    }
}
?>