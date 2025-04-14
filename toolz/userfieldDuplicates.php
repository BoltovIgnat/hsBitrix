<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$arFilter = [
    'ENTITY_ID' => ["CRM_LEAD","CRM_DEAL"],
];

$rsData = \CUserTypeEntity::GetList(array($by=>$order), $arFilter);
while($arRes = $rsData->Fetch()) {
    if (in_array($arRes["ENTITY_ID"],["CRM_LEAD","CRM_DEAL","CRM_COMPANY","CRM_CONTACT"])) {
        $query = \Bitrix\Main\UserFieldLangTable::query()
        ->where('USER_FIELD_ID',$arRes["ID"])
        ->where('LANGUAGE_ID','ru')
        ->addSelect("USER_FIELD_ID")
        ->addSelect("EDIT_FORM_LABEL")
        ->exec();
        $ruName = $query->fetch();
        $arRes["NAME"]= $ruName["EDIT_FORM_LABEL"];

        $arfields[$arRes["ENTITY_ID"]][] = $arRes;
        
    } 
}
foreach ($arfields as $key => $field) {
    
    foreach($field as $k => $v) {
//        echo '<pre>'; print_r($v); echo '</pre>';
        $arNames[$v["NAME"]]["FIELD_CODE"][] = $v["FIELD_NAME"];
        $arNames[$v["NAME"]]["NAME"][] = $v["NAME"];
        $arNames[$v["NAME"]]["CRM_TYPE"][] = $v["ENTITY_ID"];
    }

}


//echo '<pre>'; print_r($arNames); echo '</pre>';
foreach ($arNames as $k => $v) {
    foreach ($v["CRM_TYPE"] as $key => $type) {
        $fields[$k][$type][] = $v["FIELD_CODE"][$key];
    }

}

//echo '<pre>'; print_r($fields); echo '</pre>';
?>
<table class="table">
    <tbody>
    <tr>
        <td>Название поля</td>
        <td>ID_LEAD</td>
        <td>ID_DEAL</td>
        <td>ID_CONTACT</td>
        <td>ID_COMPANY</td>
    </tr>
<?foreach ($fields as $key => $value):?>
    <?foreach ($value["CRM_LEAD"] as $code):?>
        <tr>
            <td><?=$key;?></td>
            <td><?=$code;?></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    <?endforeach;?>
    <?foreach ($value["CRM_DEAL"] as $code):?>
        <tr>
            <td><?=$key;?></td>
            <td></td>
            <td><?=$code;?></td>
            <td></td>
            <td></td>
        </tr>
    <?endforeach;?>
    <?foreach ($value["CRM_CONTACT"] as $code):?>
        <tr>
            <td><?=$key;?></td>
            <td></td>
            <td></td>
            <td><?=$code;?></td>
            <td></td>
        </tr>
    <?endforeach;?>
    <?foreach ($value["CRM_COMPANY"] as $code):?>
        <tr>
            <td><?=$key;?></td>
            <td></td>
            <td></td>
            <td></td>
            <td><?=$code;?></td>
        </tr>
    <?endforeach;?>
<?endforeach;?>
</tbody>
</table>

<style>
    .table,.table td, .table tr{
        border: 1px solid black;
    }
    .table {
        border-collapse: collapse;
    }
</style>