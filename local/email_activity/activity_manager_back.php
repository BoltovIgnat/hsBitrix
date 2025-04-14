<?
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;

\Bitrix\Main\Loader::includeModule('mail');
\Bitrix\Main\Loader::includeModule('crm');
\Bitrix\Main\Loader::includeModule('disk');
\Bitrix\Main\Loader::includeModule('highloadblock');

require 'functions.php';

$date = new \Bitrix\Main\Type\DateTime();

$logPath = '/local/email_activity/logs_manager/email_manager_' . $date->format("d_m_Y"). '.log';

\Bitrix\Main\Diag\Debug::dumpToFile($date->format("d.m.Y H:i:s"), '==================== START ====================', $logPath);

$start_date = ($arParams['START_DATE']) ? new \Bitrix\Main\Type\DateTime($arParams['START_DATE']) : '';
$isIncome = !$arMessageFields['IS_OUTCOME'];
$isDraft = $arMessageFields['IS_DRAFT'];
$isTrash = $arMessageFields['IS_TRASH'];
$isSpam = $arMessageFields['IS_SPAM'];

$assignedId = 16;

if($start_date) {
	$message_date = new \Bitrix\Main\Type\DateTime($arMessageFields["FIELD_DATE"]);
	if($message_date->getTimestamp() < $start_date->getTimestamp()) {
		return true;
	}
}

\Bitrix\Main\Diag\Debug::dumpToFile($message_date->format("d.m.Y H:i:s"), '$message_date', $logPath);

\Bitrix\Main\Diag\Debug::dumpToFile($isIncome, '$isIncome', $logPath);

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
//$hlblock = HLBT::getById($HlBlockId)->fetch();
$entity = HLBT::compileEntity($HlBlockId);
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
			$dbDeal = \CCrmDeal::GetListEx(
				[
					'ID' => 'DESC'
				],
				[
					'ID' => intval($dealId),
					'CHECK_PERMISSIONS' => 'N',
					'STAGE_ID' => DEALOPENEDSTAGES
				],
				false,
				false,
				['ID','LEAD_ID', "ASSIGNED_BY_ID", 'UF_CRM_6318FC326D01B', 'UF_CRM_6318FC334D52D']
			);
			if($res = $dbDeal->Fetch()) {
				$arDeals[$res["ID"]] = $res;
				$arOpenOldDealsForLead[] = $res["ID"];
			}		
			
			$dealDateTime = new \Bitrix\Main\Type\DateTime($res["DATE_CREATE"]);
			$dealDate = new \Bitrix\Main\Type\DateTime($dealDateTime->format('d.m.Y'));
			$messageDateTime = new \Bitrix\Main\Type\DateTime($message["FIELD_DATE"]);
			$messageDate = new \Bitrix\Main\Type\DateTime($messageDateTime->format('d.m.Y'));

			if ($dealDate == $messageDate) {
				resendEmail($message,$res["ASSIGNED_BY_ID"],$email_check,$res["ID"],\CCrmOwnerType::Deal);
			}
			
/* 			if(!$arDeals) {
				$dbDeal = \CCrmDeal::GetListEx(
					[
						'ID' => 'DESC'
					],
					[
						'ID' => intval($dealId),
						'CHECK_PERMISSIONS' => 'N',
						'STAGE_SEMANTIC_ID' => 'F'
					],
					false,
					false,
					['ID','LEAD_ID', 'UF_CRM_6318FC326D01B', 'UF_CRM_6318FC334D52D']
				);
				if($res = $dbDeal->Fetch()) {
					if($res["UF_CRM_6318FC326D01B"] && $res['UF_CRM_6318FC334D52D']) {
						$dealDateTime = new \Bitrix\Main\Type\DateTime($res["UF_CRM_6318FC326D01B"]);
						$dealTimeStart = new \Bitrix\Main\Type\DateTime($dealDateTime->format('d.m.Y'));
						$currentDateTime = new \Bitrix\Main\Type\DateTime();
						$currentTimeStart = new \Bitrix\Main\Type\DateTime($currentDateTime->format('d.m.Y'));
						if($currentTimeStart->getTimestamp() == $dealTimeStart->getTimestamp()) {
							$entity = new \CCrmDeal(false);
							$updateFields = [
								'STAGE_ID' => $res['UF_CRM_6318FC334D52D']
							];
							$update = $entity->Update($res['ID'], $updateFields, true, true, []);

							$arDeals[] = $res;
						}
					}
				}
			} */
		}

		//Если найдена открытая сделка не текущей даты, создаём лид
		if (!empty($arOpenOldDealsForLead)) {
			rsort($arOpenOldDealsForLead,SORT_REGULAR);
			$arOpenOldDealForLead = $arDeals[$arOpenOldDealsForLead[0]];
			$dateDeal = new \Bitrix\Main\Type\DateTime($arOpenOldDealForLead[0]["DATE_CREATE"]);
			if ($dateDeal < $date) {
				$assId = $arOpenOldDealForLead[0]["ASSIGNED_BY_ID"];
				addLeadFromEmail($message,$assId,$email_check);
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
				
/* 				if($isIncome) {
					SendEmailBP($messageId, 109, 'CCrmDocumentDeal', 'DEAL_'. $deal["ID"]);
				} */
			}
		} else {
			//lead
			//Есть открытый лид (Проверка при условии что Нет открытой сделки), крепим письмо к лиду + контакту + компании. Проверяем ответственного: Колганов, не отправляем письмо. Другой менеджер, отправляем письмо
				$findLead = false;
				
				$dbRes = \CCrmLead::GetListEx(
					[
						'ID' => 'ASC'
					],
					[
						'CHECK_PERMISSIONS' => 'N',
						'CONTACT_ID' => intval($arContact['ELEMENT_ID']),
						'STATUS_SEMANTIC_ID' => 'P'
					],
					false,
					false,
					['ID','ASSIGNED_BY_ID']
				);
				while($res = $dbRes->Fetch()) {
					\Bitrix\Main\Diag\Debug::dumpToFile($res["ID"], 'add into lead', $logPath);
					
					$data['ownerId'] = $res["ID"];
					$data['ownerTypeId'] = \CCrmOwnerType::Lead;
					AddActivityDbbo($message, $data);

					
					if ($res["ASSIGNED_BY_ID"] != 16) {
						resendEmail($message,$res["ASSIGNED_BY_ID"],$email_check,$res["ID"],\CCrmOwnerType::Lead);
					}
					
					$findLead = true;
				}
			//
			if (!$findLead)	{
			//Если контакт есть но он не привязан к лиду попробуем найти открытый лид по email адресу и привяжем к лиду контакт
				$dbRes = \CCrmLead::GetListEx(
					[
						'ID' => 'ASC'
					],
					[
						'CHECK_PERMISSIONS' => 'N',
						'ID' => $arLead['ELEMENT_ID'],
						'STATUS_SEMANTIC_ID' => 'P'
					],
					false,
					false,
					['ID','ASSIGNED_BY_ID']
				);
				while($res = $dbRes->Fetch()) {
					\Bitrix\Main\Diag\Debug::dumpToFile($res["ID"], 'add into lead', $logPath);

					$entity = new \CCrmLead(false);
					$updateFields = [
						'CONTACT_ID' => intval($arContact['ELEMENT_ID'])
					];
					$entity->Update($res['ID'], $updateFields, true, true, []);
					
					$data['ownerId'] = $res["ID"];
					$data['ownerTypeId'] = \CCrmOwnerType::Lead;
					AddActivityDbbo($message, $data);

					
					if ($res["ASSIGNED_BY_ID"] != 16) {
						resendEmail($message,$res["ASSIGNED_BY_ID"],$email_check,$res["ID"],\CCrmOwnerType::Lead);
					}
					
					$findLead = true;
				}
			}
			//
			
			if(!$findLead) {
				$dbRes = \CCrmLead::GetListEx(
					[
						'ID' => 'ASC'
					],
					[
						'CHECK_PERMISSIONS' => 'N',
						'CONTACT_ID' => intval($arContact['ELEMENT_ID']),
						'STATUS_SEMANTIC_ID' => 'F',
						'STATUS_ID' => 'JUNK'
					],
					false,
					false,
					['ID', 'UF_CRM_1680466157','DATE_CLOSED']
				);
				while($res = $dbRes->Fetch()) {
					if($res["UF_CRM_1680466157"] && $res['DATE_CLOSED']) {
						$dealDateTime = new \Bitrix\Main\Type\DateTime($res["DATE_CLOSED"]);
						$dealTimeStart = new \Bitrix\Main\Type\DateTime($dealDateTime->format('d.m.Y'));
						$currentDateTime = new \Bitrix\Main\Type\DateTime();
						$currentTimeStart = new \Bitrix\Main\Type\DateTime($currentDateTime->format('d.m.Y'));
						if($currentTimeStart->getTimestamp() == $dealTimeStart->getTimestamp()) {
							$entity = new \CCrmLead(false);
							$updateFields = [
								'STATUS_ID' => $res['UF_CRM_1662581531']
							];
							$entity->Update($res['ID'], $updateFields, true, true, []);
							
							$data['ownerId'] = $res["ID"];
							$data['ownerTypeId'] = \CCrmOwnerType::Lead;
							AddActivityDbbo($message, $data);

/* 							if($isIncome) {
								SendEmailBP($messageId, 108, 'CCrmDocumentLead', 'LEAD_'. $res["ID"]);
							} */
							
							$findLead = true;
							break;
						}
					}
				}
			}
			
			if(!$findLead) {
				//add lead
				$fields = [
					'TITLE' => 'Входящий запрос: ' . $message['SUBJECT'],
					'SOURCE_ID' => 'EMAIL',
					'NAME' => '',
					"ASSIGNED_BY_ID" => $assignedId,
					'STATUS' => 'NEW',
					'FM' => [
						'EMAIL' => [
							"n0" => Array(
								"VALUE" => $email_check,
								"VALUE_TYPE" => "WORK",
							),
						]
					],
					'CONTACT_ID' => $arContact['ELEMENT_ID']
				];
				
				$entity = new \CCrmLead(false);
				$leadId = $entity->add($fields);
				\Bitrix\Main\Diag\Debug::dumpToFile('Создан лид ' . $leadId, 'addLead', $logPath);
				if($leadId) {
					$data['ownerId'] = $leadId;
					$data['ownerTypeId'] = \CCrmOwnerType::Lead;
					AddActivityDbbo($message, $data);
/* 					SendEmailBP($messageId, 114, 'CCrmDocumentLead', 'LEAD_'. $leadId, [
						'launch_PHP_mail' => 1
					]); */

					\CCrmBizProcHelper::AutoStartWorkflows(
						\CCrmOwnerType::Lead,
						$leadId,
						\CCrmBizProcEventType::Create,
						$arErrors,
						null
					);

					\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Lead, $leadId);

					AddCompanyEntity($arContact['ELEMENT_ID'], 'lead', $leadId, $assignedId, true);
				}
			}
		}
		
	} elseif($arLead) {
		//lead
		\Bitrix\Main\Diag\Debug::dumpToFile($arLead, '$arLead', $logPath);
		$findLead = false;
		
		$dbRes = \CCrmLead::GetListEx(
			[
				'ID' => 'ASC'
			],
			[
				'CHECK_PERMISSIONS' => 'N',
				'ID' => $arLead['ELEMENT_ID'],
				'STATUS_SEMANTIC_ID' => 'P'
			],
			false,
			false,
			['ID','ASSIGNED_BY_ID']
		);
		while($res = $dbRes->Fetch()) {
			\Bitrix\Main\Diag\Debug::dumpToFile($res["ID"], 'add into lead', $logPath);
			
			$data['ownerId'] = $res["ID"];
			$data['ownerTypeId'] = \CCrmOwnerType::Lead;
			AddActivityDbbo($message, $data);
			
			// Есть открытый лид и контакта нет в базе и ответственный не Евгений Колганов

			if ($res["ASSIGNED_BY_ID"] != 16) {
				resendEmail($message,$res["ASSIGNED_BY_ID"],$email_check,$res["ID"],\CCrmOwnerType::Lead);
			}

/* 			if($isIncome) {
				SendEmailBP($messageId, 108, 'CCrmDocumentLead', 'LEAD_'. $res["ID"]);
			} */
			
			$findLead = true;
		}
		
		if(!$findLead) {
			//add lead
			addLeadFromEmail($message,$assignedId,$email_check);
		}
		
	} else {
		//add lead
		addLeadFromEmail($message,$assignedId,$email_check);
	}
}