<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$dateNow = (new \Bitrix\Main\Type\DateTime())->format("d.m.Y");

$rsUsers = \CUser::GetList(($by="ID"), ($order="desc"), ["GROUPS_ID" => [11]],["SELECT" =>["UF_DEPARTMENT"],"FIELDS" =>["ID","NAME","LAST_NAME","LAST_ACTIVITY_DATE"]]);
while($userRes = $rsUsers->GetNext()) {
   $users[] = $userRes;
}

foreach ($users as $key => $user) {
	$selectdesult = \CMailbox::getList([], ["USER_ID" => $user["ID"]]);
	while ($mailbox = $selectdesult->fetch()) {
		$users[$key]["MAIL"] = $mailbox;
		$query = \Bitrix\Mail\MailFilterTable::query()
		->setSelect(["MAILBOX_ID","ACTION_PHP"])
		->setFilter([
			"MAILBOX_ID" => $mailbox["ID"],
		])
		->exec();
		$action = $query->fetch()["ACTION_PHP"];
		$users[$key]["MAIL"]["ACTION"] = $action;

		$query = \Bitrix\Mail\MailLogTable::query()
		->setSelect(["DATE_INSERT"])
		->setOrder(["DATE_INSERT" => "DESC"])
		->setFilter([
			"MAILBOX_ID" => $mailbox["ID"],
			"LOG_TYPE" => "NEW_MESSAGE"
		])
		->exec();
		$lastMessage = $query->fetch()["DATE_INSERT"];
			if (is_object($lastMessage)) {
				$messageDateTime = $lastMessage->format("d.m.Y H:i:s");
 				$messageDateCheck = $lastMessage->format("d.m.Y");
				if ($dateNow == $messageDateCheck) {
					$users[$key]["MAIL"]["AUTH"] = "Yes";
				}
				else {
					$users[$key]["MAIL"]["AUTH"] = "check";
				}
				$users[$key]["MAIL"]["LAST_MESSAGE"] = $messageDateTime; 
			}
			else {
				$users[$key]["MAIL"]["AUTH"] = "No";
			}
/* 			if (!empty($mailbox["PASSWORD"]) && $mailbox["ACTIVE"] == "Y") {
				$resConnect = imap_open("{mail01.highsystem.ru:993}", $mailbox["LOGIN"], $mailbox["PASSWORD"]);
				if ($resConnect !== false) {
					$users[$key]["MAIL"]["AUTH"] = "Y";
				}
			} */
		
	}
}
//echo '<pre>'; print_r($users); echo '</pre>';

?>

<table class="simpleTable">
	<thead>
		<th>UserID</th>
		<th>Фамилия</th>
		<th>Имя</th>
		<th>MailBox ID</th>
		<th>MailBox User</th>
		<th>addr. server</th>
		<th>Authorization</th>
		<th>Mail rule</th>
		<th>Last upload</th>
	</thead>
	<?foreach ($users as $user):?>
		<tr>
			<td><?=$user["ID"];?></td>
			<td><?=$user["LAST_NAME"];?></td>
			<td><?=$user["NAME"];?></td>
			<td><?=$user["MAIL"]["ID"];?></td>
			<td><?=$user["MAIL"]["LOGIN"];?></td>
			<td><?=$user["MAIL"]["SERVER"];?></td>
			<td><?=$user["MAIL"]["AUTH"]?></td>
			<td><?=(!empty($user["MAIL"]["ACTION"]))?"Yes":"No";?></td>
			<td><?=(!empty($user["MAIL"]["LAST_MESSAGE"]))?$user["MAIL"]["LAST_MESSAGE"]:"";?></td>
		</tr>
	<?endforeach;?>
</table>

<style>
	<?require_once($_SERVER["DOCUMENT_ROOT"]."/local/css/tables.css");?>
</style>