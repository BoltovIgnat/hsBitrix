<?
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;

\Bitrix\Main\Loader::includeModule('mail');
\Bitrix\Main\Loader::includeModule('crm');
\Bitrix\Main\Loader::includeModule('highloadblock');

require 'functions.php';

$date = new \Bitrix\Main\Type\DateTime();

$logPath = '/local/email_activity/logs/email_' . $date->format("d_m_Y"). '.log';

\Bitrix\Main\Diag\Debug::dumpToFile($date->format("d.m.Y H:i:s"), '==================== START ====================', $logPath);

$start_date = ($arParams['START_DATE']) ? new \Bitrix\Main\Type\DateTime($arParams['START_DATE']) : '';
$isIncome = empty($arMessageFields['IS_OUTCOME']);
$isDraft = !empty($arMessageFields['IS_DRAFT']);
$isTrash = !empty($arMessageFields['IS_TRASH']);
$isSpam = !empty($arMessageFields['IS_SPAM']);

if($start_date) {
	$message_date = new \Bitrix\Main\Type\DateTime($arMessageFields["FIELD_DATE"]);
	if($message_date->getTimestamp() < $start_date->getTimestamp()) {
		return true;
	}
}

\Bitrix\Main\Diag\Debug::dumpToFile($message_date->format("d.m.Y H:i:s"), '$message_date', $logPath);

if($isIncome) {	
    foreach (explode(',', $arMessageFields["FIELD_TO"]) as $item)
    {
		if (trim($item))
		{
			$address = new \Bitrix\Main\Mail\Address($item);
			if ($address->validate())
			{
				if(strstr($address->getEmail(), "highsystem.ru") === false && strstr($address->getEmail(), "scanberry") === false) {
					$isIncome = false;
					$arMessageFields['IS_OUTCOME'] = true;
					break;
				}
			}
		}
    }
}

$field_email = ($isIncome) ? $arMessageFields["FIELD_FROM"] : $arMessageFields["FIELD_TO"];

$messageId = $arMessageFields["ID"];
$message = $arMessageFields;

\Bitrix\Main\Diag\Debug::dumpToFile($messageId, '$messageId', $logPath);

\Bitrix\Main\Diag\Debug::dumpToFile($arMessageFields['MSG_ID'], 'MSG_ID', $logPath);

$replyTo = isset($message['FIELD_REPLY_TO']) ? $message['FIELD_REPLY_TO'] : '';
$sender = [];

\Bitrix\Main\Diag\Debug::dumpToFile($field_email, '$field_email', $logPath);
\Bitrix\Main\Diag\Debug::dumpToFile($replyTo, '$replyTo', $logPath);

foreach (array_merge(explode(',', $field_email), explode(',', $replyTo)) as $item)
{
	if (trim($item))
	{
		$address = new \Bitrix\Main\Mail\Address($item);
		if ($address->validate() && !in_array($address->getEmail(), $sender) && strstr($address->getEmail(), "highsystem.ru") === false && strstr($address->getEmail(), "scanberry") === false)
		{
			$sender[] = $address->getEmail();
		}
	}
}

if(!$sender) {
	return true;
}

$HlBlockId = 3;
$hlblock = HLBT::getById($HlBlockId)->fetch();
$entity = HLBT::compileEntity($hlblock);
$entity_data_class = $entity->getDataClass();
$existsMsgId = false;

$checkLog = $entity_data_class::getList([
	'filter' => [
		'UF_MSG_ID' => $arMessageFields['MSG_ID'],
		'UF_MAILBOX' => $arMessageFields['MAILBOX_ID']
	],
	'select' => ['*']
]);
while($res = $checkLog->fetch()) {
	\Bitrix\Main\Diag\Debug::dumpToFile($res, '$res - checkLog', $logPath);
	if($res) {
		$existsMsgId = true;
		break;
	}
}

if(!$existsMsgId) {
	$entity_data_class::add([
		'UF_MSG_ID' => $arMessageFields['MSG_ID'],
		'UF_MAILBOX' =>  $arMessageFields['MAILBOX_ID'],
		'UF_MESSAGE_ID' => $arMessageFields["ID"]
	]);
}

if($existsMsgId) {
	$existsMsg = [
		'MSG_ID' => $arMessageFields['MSG_ID'],
		'MAILBOX' =>  $arMessageFields['MAILBOX_ID'],
		'MESSAGE_ID' => $arMessageFields["ID"]
	];
	\Bitrix\Main\Diag\Debug::dumpToFile($existsMsg, 'Найдено сообщение с таким MSG_ID', $logPath);
	return true;
}

foreach($sender as $k => $email) {
    if(strstr($email, "highsystem.ru") !== false || strstr($email, "scanberry") !== false) {
            unset($sender[$k]);
    }
}
$sender = array_values($sender);

\Bitrix\Main\Diag\Debug::dumpToFile($sender, '$sender', $logPath);

foreach($sender as $email_check) {
	if(!$isDraft && !$isTrash && !$isSpam && strstr($email_check, "highsystem.ru") !== false && strstr($email_check, "scanberry") !== false) {
		continue;
	}

	\Bitrix\Mail\Helper\Message::prepare($message);

	$message['IS_SEEN'] = in_array($message['IS_SEEN'], array('Y', 'S'));

	//================================================
	//company and contact
	$arCompany = [];
	$arContact = [];
	$arLead = [];

	\Bitrix\Main\Diag\Debug::dumpToFile($email_check, '$email_check', $logPath);

	$items = \CCrmFieldMulti::GetListEx([], [
		'VALUE' => $email_check,
		'TYPE_ID' => 'EMAIL'
	], false, false, [], []);

	while($item = $items->Fetch()) {	
		if($item["ENTITY_ID"] == "COMPANY") {
			$arCompany = $item;
		}
		if($item["ENTITY_ID"] == "CONTACT") {
			$arContact = $item;
		}
		if($item["ENTITY_ID"] == "LEAD") {
			$arLead = $item;
		}
	}

	\Bitrix\Main\Diag\Debug::dumpToFile($arCompany, '$arCompany', $logPath);

	if($arCompany) {
		$data['ownerId'] = $arCompany["ELEMENT_ID"];
		$data['ownerTypeId'] = \CCrmOwnerType::Company;
		AddActivityDbbo($message, $data);
	}

	\Bitrix\Main\Diag\Debug::dumpToFile($arContact, '$arContact', $logPath);

	if($arContact) {
		$data['ownerId'] = $arContact["ELEMENT_ID"];
		$data['ownerTypeId'] = \CCrmOwnerType::Contact;
		AddActivityDbbo($message, $data);
	}

	//================================================

	if($arContact) {
		//deal
		$arDeals = [];
		
		$dealContact = \Bitrix\Crm\Binding\DealContactTable::getContactDealIDs(intval($arContact['ELEMENT_ID']));
		foreach($dealContact as $dealId) {
			$res = \CCrmDeal::GetListEx(
				[],
				[
					'ID' => intval($dealId),
					'CHECK_PERMISSIONS' => 'N'
				],
				false,
				false,
				['*', 'UF_*']
			)->Fetch();

			if($res["STAGE_SEMANTIC_ID"] == "P") {
				$arDeals[] = $res;
			} else {
				$currentDateTime = new \Bitrix\Main\Type\DateTime();
				$dealDateTime = new \Bitrix\Main\Type\DateTime($res["DATE_MODIFY"]);
				$dealDateTime->add("5 minutes");
				if($currentDateTime->getTimestamp() <= $dealDateTime->getTimestamp()) {
					$arDeals[] = $res;
				}
			}
		}
		
		if($arDeals) {
			foreach($arDeals as $deal) {
				\Bitrix\Main\Diag\Debug::dumpToFile($deal["ID"], 'add into deal', $logPath);
				
				$data['ownerId'] = $deal["ID"];
				$data['ownerTypeId'] = \CCrmOwnerType::Deal;
				AddActivityDbbo($message, $data);
				
				if($deal['LEAD_ID']) {
					\Bitrix\Main\Diag\Debug::dumpToFile($deal['LEAD_ID'], 'add into lead of deal', $logPath);
					
					$data['ownerId'] = $deal['LEAD_ID'];
					$data['ownerTypeId'] = \CCrmOwnerType::Lead;
					AddActivityDbbo($message, $data);
				}
			}
		} else {
			//lead
			$dbRes = \CCrmLead::GetListEx(
				[
					'ID' => 'ASC'
				],
				[
					'CHECK_PERMISSIONS' => 'N',
					'CONTACT_ID' => intval($arContact['ELEMENT_ID']),
					'STATUS_SEMANTIC_ID' => 'P'
				]
			);
			while($res = $dbRes->Fetch()) {
				\Bitrix\Main\Diag\Debug::dumpToFile($deal['LEAD_ID'], 'add into lead', $logPath);
				
				$data['ownerId'] = $res["ID"];
				$data['ownerTypeId'] = \CCrmOwnerType::Lead;
				AddActivityDbbo($message, $data);
			}
		}
	} else {
		//lead
		\Bitrix\Main\Diag\Debug::dumpToFile($arLead, '$arLead', $logPath);
		
		$dbRes = \CCrmLead::GetListEx(
			[
				'ID' => 'ASC'
			],
			[
				'CHECK_PERMISSIONS' => 'N',
				'ID' => $arLead['ELEMENT_ID'],
				'STATUS_SEMANTIC_ID' => 'P'
			]
		);
		while($res = $dbRes->Fetch()) {
			\Bitrix\Main\Diag\Debug::dumpToFile($res["ID"], 'add into lead', $logPath);
			
			$data['ownerId'] = $res["ID"];
			$data['ownerTypeId'] = \CCrmOwnerType::Lead;
			AddActivityDbbo($message, $data);
		}
	}
}