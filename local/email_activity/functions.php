<?
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;


if(!function_exists('AddActivityDbbo')) {
	function AddActivityDbbo($message, $data) {
		$date = new \Bitrix\Main\Type\DateTime();
		$logPath = '/local/email_activity/logs/email_' . $date->format("d_m_Y"). '.log';

		$dbRes = CCrmActivity::GetList(
			array(),
			array(
				'UF_MAIL_MESSAGE' => $message['ID'],
				'TYPE_ID' => \CCrmActivityType::Email,
				'OWNER_ID' => $data['ownerId']
			),
			false, false,
			array(
				'OWNER_ID',
				'UF_MAIL_MESSAGE'
			)
		)->Fetch();
		
		if($dbRes) {
			\Bitrix\Main\Diag\Debug::dumpToFile($dbRes, 'Письмо уже прикреплено', $logPath);
			return true;
		}
		
		\Bitrix\Main\Diag\Debug::dumpToFile($data, '$data', $logPath);
		
		//variable
		$isUnseen = empty($message['IS_SEEN']);
		$completed = $isUnseen ? 'N' : 'Y';
		$isIncome = empty($message['IS_OUTCOME']);

		$denyNewContact = false;
		$direction = ($isIncome) ? \CCrmActivityDirection::Incoming : \CCrmActivityDirection::Outgoing;

		$storageTypeId = \CCrmActivity::getDefaultStorageTypeID();
		$from = isset($message['FIELD_FROM']) ? $message['FIELD_FROM'] : '';
		$replyTo = isset($message['FIELD_REPLY_TO']) ? $message['FIELD_REPLY_TO'] : '';
		$to = isset($message['FIELD_TO']) ? $message['FIELD_TO'] : '';
		$cc = isset($message['FIELD_CC']) ? $message['FIELD_CC'] : '';
		$bcc = isset($message['FIELD_BCC']) ? $message['FIELD_BCC'] : '';
	
		$msgId = isset($message['MSG_ID']) ? $message['MSG_ID'] : '';
		$inReplyTo = isset($message['IN_REPLY_TO']) ? $message['IN_REPLY_TO'] : '';
	
		$senderAddress = array();
		$sender = array();
		foreach (array_merge(explode(',', $replyTo), explode(',', $from)) as $item)
		{
			if (trim($item))
			{
				$address = new \Bitrix\Main\Mail\Address($item);
				if ($address->validate() && !in_array($address->getEmail(), $sender))
				{
					$senderAddress[] = $address;
					$sender[] = $address->getEmail();
				}
			}
		}
	
		$rcptAddress = array();
		$rcpt = array();
		foreach (array_merge(explode(',', $to), explode(',', $cc), explode(',', $bcc)) as $item)
		{
			if (trim($item))
			{
				$address = new \Bitrix\Main\Mail\Address($item);
				if ($address->validate() && !in_array($address->getEmail(), $rcpt))
				{
					$rcptAddress[] = $address;
					$rcpt[] = $address->getEmail();
				}
			}
		}
	
		$mailbox = \CMailBox::getById($message['MAILBOX_ID'])->fetch();
		$userId = $mailbox['USER_ID'];
	
		$siteId = \Bitrix\Crm\Integration\Main\Site::getPortalSiteId();
	
		$currentUserOffset = \CTimeZone::getOffset();
		$userOffset = \CTimeZone::getOffset($userId);
		$subject = trim($message['SUBJECT']);
		$datetime = $message['FIELD_DATE'];
	
		$deadlineTimestamp = strtotime('tomorrow') + $currentUserOffset - $userOffset;
		$deadline = convertTimeStamp($deadlineTimestamp, 'FULL', $siteId);
	
		$body = isset($message['BODY']) ? $message['BODY'] : '';
		$body_bb = isset($message['BODY_BB']) ? $message['BODY_BB'] : '';
		$body_html = isset($message['BODY_HTML']) ? $message['BODY_HTML'] : '';
	
		$filesData = array();
		$bannedAttachments = array();
		$res = \CMailAttachment::getList(array(), array('MESSAGE_ID' => $message['ID']));
		while ($attachment = $res->fetch())
		{
			if (getFileExtension(mb_strtolower($attachment['FILE_NAME'])) == 'vcf' && !$denyNewContact)
			{
				if ($attachment['FILE_ID'])
					$attachment['FILE_DATA'] = \CMailAttachment::getContents($attachment);
				\CCrmEMail::tryImportVCard($attachment['FILE_DATA'], $userId);
			}
	
			$fileSize = isset($attachment['FILE_SIZE']) ? intval($attachment['FILE_SIZE']) : 0;
			if ($fileSize <= 0)
				continue;
	
			if ($attachmentMaxSize > 0 && $fileSize > $attachmentMaxSize)
			{
				$bannedAttachments[] = array(
					'name' => $attachment['FILE_NAME'],
					'size' => $fileSize
				);
	
				continue;
			}
	
			if ($attachment['FILE_ID'] && empty($attachment['FILE_DATA']))
				$attachment['FILE_DATA'] = \CMailAttachment::getContents($attachment);
	
			$filesData[] = array(
				'name' => $attachment['FILE_NAME'],
				'type' => $attachment['CONTENT_TYPE'],
				'content' => $attachment['FILE_DATA'],
				'MODULE_ID' => 'crm',
				'attachment_id' => $attachment['ID'],
			);
		}
	
		if (!empty($body_html))
		{
			$checkInlineFiles = true;
			$descr = $body_html;
		}
		else if (!empty($body_bb))
		{
			$bbCodeParser = new \CTextParser();
			$descr = $bbCodeParser->convertText($body_bb);
	
			foreach ($filesData as $item)
			{
				$descr = preg_replace(
					sprintf('/\[ATTACHMENT=attachment_%u\]/is', $item['attachment_id']),
					sprintf('<img src="aid:%u">', $item['attachment_id']),
					$descr, -1, $count
				);
	
				if ($count > 0)
					$checkInlineFiles = true;
			}
		}
		else
		{
			$descr = preg_replace('/\r\n|\n|\r/', '<br>', htmlspecialcharsbx($body));
		}
	
		$elementIds = array();
		foreach ($filesData as $i => $fileData)
		{
			$fileId = \CFile::saveFile($fileData, 'crm', true);
			if (!($fileId > 0))
				continue;
	
			$fileData = \CFile::getFileArray($fileId);
			if (empty($fileData))
				continue;
	
			if (trim($fileData['ORIGINAL_NAME']) == '')
				$fileData['ORIGINAL_NAME'] = $fileData['FILE_NAME'];
			$elementId = Bitrix\Crm\Integration\StorageManager::saveEmailAttachment(
				$fileData, $storageTypeId, '',
				array('USER_ID' => $userId)
			);
			if ($elementId > 0)
			{
				$elementIds[] = (int) $elementId;
				$filesData[$i]['element_id'] = (int) $elementId;
			}
		}
	
		$mailbox['__email'] = '';
		if (check_email($mailbox['NAME'], true))
			$mailbox['__email'] = $mailbox['NAME'];
		else if (check_email($mailbox['LOGIN'], true))
			$mailbox['__email'] = $mailbox['LOGIN'];
	
		//================================================
		//emailFacility
		$emailFacility = new \Bitrix\Crm\Activity\EmailFacility();
	
		$trace = \Bitrix\Crm\Tracking\Trace::create()->addChannel(
			new \Bitrix\Crm\Tracking\Channel\Mail(!empty($rcptAddress) ?
				reset($rcptAddress)->getEmail() : null
			)
		);
	
		$facility = new \Bitrix\Crm\EntityManageFacility($selector);
		$facility->setDirection($facility::DIRECTION_INCOMING);
		$facility->setTrace($trace);
		$emailFacility->setBindings($facility->getActivityBindings());
	
		$targetActivity = \Bitrix\Crm\Activity\Provider\Email::getParentByEmail($message);
		if (!empty($targetActivity)) {
			$parentId = $targetActivity['ID'];
		}
	
		//add activity
		$activityFields = array(
			'OWNER_ID' => $data['ownerId'],
			'OWNER_TYPE_ID' => $data['ownerTypeId'],
			'BINDINGS' => $emailFacility->getBindings(),
			'TYPE_ID' => \CCrmActivityType::Email,
			'ASSOCIATED_ENTITY_ID' => 0,
			'PARENT_ID' => $parentId,
			'SUBJECT' => \Bitrix\Main\Text\Emoji::encode($subject),
			'START_TIME' => (string) $datetime,
			'END_TIME' => (string) $deadline,
			'COMPLETED' => 'Y',
			'AUTHOR_ID' => $userId,
			'RESPONSIBLE_ID' => $userId,
			'PRIORITY' => \CCrmActivityPriority::Medium,
			'DESCRIPTION' => \Bitrix\Main\Text\Emoji::encode($descr),
			'DESCRIPTION_TYPE' => \CCrmContentType::Html,
			'DIRECTION' => $direction,
			'LOCATION' => '',
			'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
			'STORAGE_TYPE_ID' => $storageTypeId,
			'STORAGE_ELEMENT_IDS' => $elementIds,
			'SETTINGS' => array(
				'EMAIL_META' => array(
					'__email' => $mailbox['__email'],
					'from' => $from,
					'replyTo' => $replyTo,
					'to' => $to,
					'cc' => $cc,
					'bcc' => $bcc,
				),
			),
			'UF_MAIL_MESSAGE' => $message['ID'],
		);
	
		if (!empty($isIncome ? $sender : $rcpt))
		{
			$subfilter = array(
				'LOGIC' => 'OR',
			);
	
			foreach ($activityFields['BINDINGS'] as $item)
			{
				$subfilter[] = array(
					'=ENTITY_ID' => \CCrmOwnerType::resolveName($item['OWNER_TYPE_ID']),
					'=ELEMENT_ID' => $item['OWNER_ID'],
				);
			}
	
			$res = \Bitrix\Crm\FieldMultiTable::getList(array(
				'select' => array('ENTITY_ID', 'ELEMENT_ID', 'VALUE'),
				'group' => array('ENTITY_ID', 'ELEMENT_ID', 'VALUE'),
				'filter' => array(
					$subfilter,
					'=TYPE_ID' => 'EMAIL',
					'@VALUE' => $isIncome ? $sender : $rcpt,
				),
			));
	
			while ($item = $res->fetch())
			{
				$activityFields['COMMUNICATIONS'][] = array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::resolveId($item['ENTITY_ID']),
					'ENTITY_ID' => $item['ELEMENT_ID'],
					'VALUE' => $item['VALUE'],
					'TYPE' => 'EMAIL',
				);
			}
		}
	
		$activityId = \CCrmActivity::add($activityFields, false, false, array('REGISTER_SONET_EVENT' => true));

		if ($activityId > 0)
		{
			if (!empty($checkInlineFiles))
			{
				foreach ($filesData as $item)
				{
					$info = \Bitrix\Crm\Integration\DiskManager::getFileInfo(
						$item['element_id'], false,
						array('OWNER_TYPE_ID' => \CCrmOwnerType::Activity, 'OWNER_ID' => $activityId)
					);
	
					$descr = preg_replace(
						sprintf('/<img([^>]+)src\s*=\s*(\'|\")?\s*(aid:%u)\s*\2([^>]*)>/is', $item['attachment_id']),
						sprintf('<img\1src="%s"\4>', $info['VIEW_URL']),
						$descr, -1, $count
					);
	
					if ($count > 0)
						$descrUpdated = true;
				}
	
				if (!empty($descrUpdated))
				{
					\CCrmActivity::update($activityId, array(
						'DESCRIPTION' => $descr,
					), false, false);
				}
			}
	
			\Bitrix\Crm\Activity\MailMetaTable::add(array(
				'ACTIVITY_ID' => $activityId,
				'MSG_ID_HASH' => !empty($msgId) ? md5(mb_strtolower($msgId)) : '',
				'MSG_INREPLY_HASH' => !empty($inReplyTo) ? md5(mb_strtolower($inReplyTo)) : '',
				'MSG_HEADER_HASH' => $message['MSG_HASH'],
			));
	
			$res = \Bitrix\Crm\Activity\MailMetaTable::getList(array(
				'select' => array('ACTIVITY_ID'),
				'filter' => array(
					'=MSG_INREPLY_HASH' => md5(mb_strtolower($msgId)),
				),
			));
			while ($mailMeta = $res->fetch())
			{
				\CCrmActivity::update($mailMeta['ACTIVITY_ID'], array(
					'PARENT_ID' => $activityId,
				), false, false);
			}
	
			if ($isIncome)
			{
				\Bitrix\Crm\Automation\Trigger\EmailTrigger::execute($activityFields['BINDINGS'], $activityFields);
			}

			\Bitrix\Crm\Integration\Channel\EmailTracker::getInstance()->registerActivity($activityId, array('ORIGIN_ID' => sprintf('%u|%u', $mailbox['USER_ID'], $mailbox['ID'])));
		}

		/* if ($data['ownerTypeId'] == 1 || $data['ownerTypeId'] == 2) {
			if ($isIncome) {
				resendEmail($message,$userId,$from,$data['ownerId'],$data['ownerTypeId']);
			}
		} */

	}
}


if(!function_exists('SendEmailBP')) {
	function SendEmailBP($messageId, $bpId, $bpEntityType, $bpEntityId, $params = []) {
		$messageFile = [];

		$files = \Bitrix\Mail\Internals\MailMessageAttachmentTable::getList(array(
			'filter' => array(
				'=MESSAGE_ID' => $messageId,
			)
		))->fetchAll();
	
		if($files) {
			foreach($files as $file) {
				$diskFile = \Bitrix\Disk\File::load(['FILE_ID'=>$file['FILE_ID']]);
				$name = $diskFile->getName();
				$arFile = \CFile::GetFileArray($file['FILE_ID']);
				$fileIo = new Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT']. $arFile['SRC']);
				$content = $fileIo->getContents();
				$fileName = $_SERVER['DOCUMENT_ROOT'].'/upload/mail_tmp/'.$name;
				file_put_contents($fileName, $content);
				$uploadFile = \CFile::MakeFileArray(
					$fileName,
					false,
					false,
					''
				);
				$fileSave = \CFile::SaveFile($uploadFile, "/mail", false, false);
				$messageFile[] = $fileSave;
				unlink($fileName);
			}
		}

		$bpParams = array(
			'ID_Letter' => $messageId,
			'ID_Files' => $messageFile
		);

		if($params) {
			$bpParams = array_merge($bpParams, $params);
		}

		CBPDocument::StartWorkflow(
			  $bpId,
			  array("crm", $bpEntityType, $bpEntityId),
			  $bpParams,
			  $arErrorsTmp
		);
	}
}

if(!function_exists('AddCompanyEntity')) {
	function AddCompanyEntity($contactId, $entity, $entityId, $authorId = 1) {
		$date = new \Bitrix\Main\Type\DateTime();
		$logPath = '/local/email_activity/logs_manager/add_company_' . $date->format("d_m_Y"). '.log';
		$company = \Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($contactId);

		if($company) {
			if(count($company) == 1) {
				if($entity == 'deal') {
					$entity = new \CCrmDeal(false);
				} elseif($entity == 'lead') {
					$entity = new \CCrmLead(false);
				}

				$update = [
					'COMPANY_ID' => $company[0]
				];
				\Bitrix\Main\Diag\Debug::dumpToFile('Прикреплена компания к лиду ' . $entityId . '. ID компании - '. $company[0], 'addCompany', $logPath);
				$entity->Update($entityId, $update, true, true, []);
			} else {
				$text_comment = 'У Контакта есть связь со следующими компания:' . "\r\n";
				foreach($company as $companyId) {
					$text_comment .= '<a href="https://crm.highsystem.ru/crm/company/details/'. $companyId .'/">https://crm.highsystem.ru/crm/company/details/'. $companyId .'/</a>' . "\r\n";
				}
				$text_comment .= 'Необходимо в ручном режиме определить к какой компании относится данный запрос!';
				
				$type = CCrmOwnerType::Deal;
				
				if($entity == 'lead') {
					$type = CCrmOwnerType::Lead;
				}
				
				$resId = \Bitrix\Crm\Timeline\CommentEntry::create(
					array(
					'TEXT' => $text_comment,
					'SETTINGS' => array(), 
					'AUTHOR_ID' => $authorId,
					'BINDINGS' => array(array('ENTITY_TYPE_ID' => $type, 'ENTITY_ID' => $entityId))
				));
			}
		}
	}
}

if(!function_exists('addLeadFromEmail')) {
	function addLeadFromEmail($message,$assignedId,$email_check) {
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
			]
		];
		
		$entity = new \CCrmLead(false);
        $leadId = $entity->add($fields);
		
		$data['ownerId'] = $leadId;
		$data['ownerTypeId'] = \CCrmOwnerType::Lead;
		AddActivityDbbo($message, $data);
        
		if($leadId) {
			/*	SendEmailBP($message["ID"], 114, 'CCrmDocumentLead', 'LEAD_'. $leadId, [
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

			//resendEmail($message,$assignedId,$email_check,$leadId,\CCrmOwnerType::Lead);
		}
		return $leadId;
	}
}
if(!function_exists('resendEmail')) {
	function resendEmail($message,$assignedId,$email_check,$eid,$type=1) {
		if (strstr($email_check, "highsystem.ru") === false && strstr($email_check, "scanberry") === false) {

			$entityLink = match ($type) {
				\CCrmOwnerType::Lead => "По лиду № ".$eid."<a href='https://crm.highsystem.ru/crm/lead/details/".$eid."/'>Ссылка на лид</a> ",
				\CCrmOwnerType::Deal => "По сделке № ".$eid."<a href='https://crm.highsystem.ru/crm/deal/details/".$eid."/'>Ссылка на сделку</a> ",
			};

			$entityName = match ($type) {
				\CCrmOwnerType::Lead => "По лиду № ".$eid,
				\CCrmOwnerType::Deal => "По сделке № ".$eid
			};
			
			$managerEmail = CUser::GetList(($by="ID"), ($order="desc"), ["ID" => $assignedId],["SELECT" =>["EMAIL"]])->fetch()["EMAIL"];
			$subject = "Поступил запрос от ".$email_check."в " .$message["FIELD_DATE"];
			$preText = "<div>".$subject." <br> ".$entityLink."</div>";
			$messageBody = $preText."<br>".$message["BODY_HTML"];
			$headers = "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
			
/* 			$ifResended = (\CIblockElement::GetList(["ID" => "ASC"], ["IBLOCK_ID" => 53, "=NAME" => $entityName, "PROPERTY_DATE_VALUE" => $message["FIELD_DATE"]], false, false, ["ID","NAME"])->Fetch()["ID"] > 0);
			$ifResended = false;
			
			if (!$ifResended) { */
				//$res = mail($managerEmail,$subject,$messageBody,$headers);
				include $_SERVER['DOCUMENT_ROOT'].'/local/api/Mail.php';
				$res = \Dbbo\Mail::Send($managerEmail,$subject,$messageBody,$headers,"activity_manager");
				$el = new \CIBlockElement;
				if ($res) {
					$arFields = [
						'IBLOCK_ID' => 51,
						'NAME' => $entityName,
						"PROPERTY_VALUES" => [
							'FROM' => $email_check,
							'TO' => $managerEmail,
							'ENTITY' => $entityName,
							'DATE' => $message["FIELD_DATE"]
						]
					];
				}
				else {
					$arFields = [
						'IBLOCK_ID' => 51,
						'NAME' => $entityName,
						"PROPERTY_VALUES" => [
							'FROM' => $email_check,
							'TO' => $managerEmail,
							'ENTITY' => "Не удалось отправить",
							'DATE' => $message["FIELD_DATE"]
						]
					];
				}
				$el->Add($arFields);
			//}
		}
	}
}
