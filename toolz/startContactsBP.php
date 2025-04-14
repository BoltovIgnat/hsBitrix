<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$date = new \Bitrix\Main\Type\DateTime();

$bpID = $_REQUEST["BP_ID"];
$assID = $_REQUEST["ASSIGNED_BY_ID"];
$targetUser = $_REQUEST["TARGET_USER"];
$newUser = $_REQUEST["NEW_USER"];


?>

<form>
    ID бизнес-процесса:
    <input name="BP_ID" placeholder="ID бизнес-процесса" value="<?=$bpID;?>">
    Пользователь для фильтрации:
    <input name="ASSIGNED_BY_ID" placeholder="Пользователь для фильтрации" value="<?=$assID;?>">
    Под кем запускаем процесс:
    <input name="TARGET_USER" placeholder="Под кем запускаем процесс" value="<?=$targetUser;?>">
    Новый ответственный:
    <input name="NEW_USER" placeholder="Новый ответственный" value="<?=$newUser;?>">
    Посмотреть/Обработать
    <input type="checkbox" name="RUN" value="Y" <?=($_REQUEST["RUN"])?"checked":""?>>
    <button type="submit" >Отправить</button>
</form>
<? if ($_REQUEST["completed"] == "Y"):?><span style="color:green; font-size:20px">Обработка была выполнена</span><br><?endif;?>
<?

if (empty($bpID)) {
    die("Не указан ID бизнес процесса");
};

if ($_REQUEST["completed"] == "Y"):?>
<span style='color:green; font-size:20px'>Выполнено</span>
<?
endif;

$filter = [
    "ASSIGNED_BY_ID" => $assID,
];

$query = \Bitrix\Crm\ContactTable::query()
->addSelect("ID")
->setFilter($filter)
->exec();
$res = $query->fetchAll();

//+Kint::Dump($contactsRes);

foreach($res as $element) {
    $ids[] = $element["ID"];
}

+Kint::Dump($ids);

if ($_REQUEST["RUN"] == "Y") {
    foreach ($ids as $id) {
         $wfId = CBPDocument::StartWorkflow(
            $bpID,
             [ "crm", "CCrmDocumentContact", "CONTACT_".$id ],
             [ "TargetUser" => "user_".$targetUser, "Users" =>  "user_".$newUser],
             $arErrorsTmp
         );    
    }
    echo "<span style='color:green; font-size:20px'>Выполнено</span>";
    sleep(3);
    LocalRedirect('/toolz/startContactsBP.php?completed=Y');
}
?>




