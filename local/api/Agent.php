<?php
namespace Dbbo\Agent;

class AgentLead {
	public static function AgentLeadGenerator($workflowId, $userId, $targetUserId, $entityCode, $filename) {
		\Bitrix\Main\Loader::includeModule('bizproc');

		$path = \Bitrix\Main\Application::getDocumentRoot() . "/local/php_interface/logs/" . $filename;
		$content = file_get_contents($path);
		$entity = unserialize($content);

		if(is_array($entity)) {
			foreach($entity as $entityId) {
				if(!intval($entityId)) {
					continue;
				}
				\CBPDocument::StartWorkflow(
					$workflowId,
					array("crm", "CCrmDocument".$entityCode, strtoupper($entityCode) .'_'. $entityId),
					array(
						"User" => 'user_'.$userId,
						"TargetUser" => 'user_'.$targetUserId,
					),
					$arErrorsTmp
				);
			}
		}

		unlink($path);

		$agentName = "\Dbbo\Agent\AgentLead::AgentLeadGenerator($workflowId, $userId, $targetUserId, '".$entityCode."', '".$filename."');";
		\CAgent::RemoveAgent($agentName);
	}
    public static function checkStartBPForLeads(){

        $date_to = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime("-3 minutes"));
        $date_from = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime("-10 minutes"));

        $query = \Bitrix\Crm\LeadTable::query()
            ->setSelect(["ID"])
            ->whereBetween("DATE_CREATE", $date_from, $date_to)
            ->setFilter([ "SOURCE_ID" => "CALL", "STATUS_ID" => "NEW", "ASSIGNED_BY_ID" => 16 ])
            ->exec();
        $leads = $query->fetchAll();

        foreach ($leads as $lead) {
            $query = \Bitrix\Bizproc\Workflow\Entity\WorkflowStateTable::query()
                ->setSelect(["STATE","DOCUMENT_ID","WORKFLOW_TEMPLATE_ID"])
                ->setFilter([ "DOCUMENT_ID" => "LEAD_".$lead["ID"], "WORKFLOW_TEMPLATE_ID" => CRM_BP["leadAddBPStart"] ])
                ->exec();
            $notStartBPLeads = $query->fetch()["STATE"];

            if (empty($notStartBPLeads)) {
                $wfId = \CBPDocument::StartWorkflow(
                    CRM_BP["leadAddBPStart"],
                    [ "crm", "CCrmDocumentLead", "LEAD_".$lead["ID"] ],
                    [ "TargetUser" => "user_128" ],
                    $arErrorsTmp
                );
                $fields = [
                    "UF_CRM_1680466157" => "NEW"
                ];
                $res = \Bitrix\Crm\LeadTable::update($lead["ID"],$fields);
            }

        }

        $fh = fopen($_SERVER['DOCUMENT_ROOT'].'/agentsStarts.txt', 'a+');
        fwrite($fh, "Запуск Агента checkStartBPForLeads: " . (new \DateTime('now'))->format('d.m.Y H:i:s').PHP_EOL);
        fclose($fh);

        return "\Dbbo\Agent\AgentLead::checkStartBPForLeads();";

    }

    public static function checkMailPrice(){

        //AddMessage2Log("Произвольный текст сообщения", "my_module_id");
        $hostname = '{mail.highsystem.ru:993/imap/ssl}INBOX'; // Хост и порт IMAP
        $username = 'priceList@highsystem.ru'; // Ваш email
        $password = 'AWDP%Hc-KCz96vzn'; // Ваш пароль

        // Подключение к почтовому ящику
        $inbox = imap_open($hostname, $username, $password);

        if ($inbox) {
            //echo '<br>' . "Подключение успешно установлено." . '</br>';

            // Получаем текущую дату в формате, который понимает IMAP (например, "25-Oct-2023")
            $today = date("d-M-Y");

            // Поиск непрочитанных писем за текущий день
            $emails = imap_search($inbox, 'ON "' . $today . '" UNSEEN');

            if ($emails) {
                //echo '<br>' . 'Найдено непрочитанных писем за сегодня: ' . count($emails) . '</br>';

                // Перебор писем
                foreach ($emails as $email_number) {

                    // Получение заголовка письма
                    $header = imap_headerinfo($inbox, $email_number);

                    // Декодируем поле "Тема"
                    $subject = imap_utf8($header->subject);

                    // Декодируем поле "От"
                    $from = imap_utf8($header->fromaddress);

                    // Вывод информации о письме
                    echo '<br>' . 'Письмо #' . $email_number . ':';
                    echo '<br>' . '  Тема: ' . $subject;
                    echo '<br>' . '  От: ' . $from;
                    echo '<br>' . '  Дата: ' . $header->date;


                    // Обработка писем от разных поставщиков
                    if (str_contains($from, 'amiantova@geksagon.ru')) {
                        self::process_supplier_email($inbox, $email_number, 'geksagon', $subject, $from);
                    } elseif (str_contains($from, 'aaskvortsov@octron.ru')) {
                        self::process_supplier_email($inbox, $email_number, 'octron', $subject, $from);
                    } elseif (str_contains($from, 'v.demkin@compass-c.com')) {
                        self::process_supplier_email($inbox, $email_number, 'compass', $subject, $from);
                    } else {
                        //echo '<br>' . '  Не определен поставщик: ' . $from;
                    }

                    echo '<br> ' . '------------------------------------' . '</br>';

                }
            } else {
                echo '<br>' . 'Непрочитанных писем за сегодня нет.';
            }

            // Закрытие соединения
            imap_close($inbox);

        } else {
            echo '<br>' . 'Не удалось подключиться к почтовому ящику: ' . imap_last_error();
        }

        return "\Dbbo\Agent\AgentLead::checkMailPrice();";

    }

    /**
     * Обрабатывает письмо от поставщика.
     *
     * @param resource $inbox Ресурс IMAP.
     * @param int $email_number Номер письма.
     * @param string $supplier_name Название поставщика.
     * @param string $subject Тема письма.
     * @param string $from Отправитель письма.
     */
    public static function process_supplier_email($inbox, $email_number, $supplier_name, $subject, $from) {
        //echo '<br>' . 'Есть новое вхоляшее письмо от поставщика ' . $supplier_name;

        // Проверка по теме письма
        if (str_contains($subject, 'Прайс лист') || str_contains($subject, 'Прайс-лист') || str_contains($subject, 'Выгрузка остатков')) {
            //echo '<br>' . 'Есть прайс лист от поставщика, необходимо получить и сохранить файл';

            // Получаем структуру письма
            $structure = imap_fetchstructure($inbox, $email_number);
            $attachments = get_attachments($structure, $inbox, $email_number);

            if ($attachments) {
                //echo '<br>' . 'Найдено вложений: ';
                //echo '<pre>' . print_r($attachments, true) . '</pre>';

                //$body = imap_fetchbody($inbox, $email_number, 0);

                switch ($attachments[0]['encoding']) {
                    case 3: // BASE64
                        $body = base64_decode($attachments[0]['ibcattachments']);
                        break;
                    case 4: // QUOTED-PRINTABLE
                        $body = quoted_printable_decode($attachments['ibcattachments']);
                        break;
                    // Другие кодировки не требуют декодирования
                }

            }
            //echo '<br>' . $body;
            // Сохраняем только .xlsx файлы
            $file_path = self::save_xlsx_attachment($attachments[0]['filename'], $body, $supplier_name);

            if ($file_path) {
               // echo "Файл сохранен: $file_path\n";
            } else {
                //echo "Файл '" . $attachments['filename'] . "' не является .xlsx и был пропущен.\n";
            }

        }
    }
    /**
     * Сохраняет вложение в указанную директорию, если это файл с расширением .xlsx.
     * Имя файла будет преобразовано в формат "название_поставщика.xlsx".
     *
     * @param string $filename Имя файла.
     * @param string $content Содержимое файла.
     * @param string $supplier_name Название поставщика (например, "octron", "gaksagon").
     * @return string|null Возвращает путь к сохраненному файлу или null, если файл не .xlsx.
     */
    public static function save_xlsx_attachment($filename, $content, $supplier_name) {
        echo '<pre>' . print_r('Имя файла:', true) . '</pre>';
        echo '<pre>' . print_r($filename, true) . '</pre>';
        echo '<pre>' . print_r($supplier_name, true) . '</pre>';



        // Директория для сохранения файлов
        $directory = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/';
        $directory = $_SERVER['DOCUMENT_ROOT'] . '/upload/price/'.date('d.m.Y').'/';
        // Проверяем, что файл имеет расширение .xlsx
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (strtolower($extension) !== 'xlsx') {
            return null; // Пропускаем файлы с другим расширением
        }

        // Создаем новое имя файла: "название_поставщика.xlsx"
        $new_filename = strtolower($supplier_name) . '.xlsx';

        // Сохраняем файл
        $file_path = rtrim($directory, '/') . '/' . $new_filename;
        file_put_contents($file_path, $content);

        copy($filename, $file_path);

        return $file_path;
    }

    public static function get_attachments($part, $inbox, $email_number) {
        $attachments = [];

        foreach ($part->parts as $index => $subpart) {
            if ($subpart->ifdisposition && strtolower($subpart->disposition) === 'attachment') {

                // Получаем имя файла из dparameters
                if ($subpart->ifdparameters) {
                    foreach ($subpart->dparameters as $param) {
                        if (strtolower($param->attribute) === 'filename') {
                            $filename = $param->value;
                            $ibcattachments = imap_fetchbody($inbox, $email_number, $index+1);
                            break;
                        }
                    }
                }

                // Если имя файла закодировано, декодируем его
                if (preg_match('/^=\?.*?\?=/', $filename)) {
                    $filename = imap_utf8($filename);
                }


                $attachments[] = [
                    'filename' => $filename,
                    'index' => $index,
                    'encoding' => $subpart->encoding,
                    'ibcattachments' => $ibcattachments,
                ];
            }

        }

        return $attachments;
    }

}

class BPAgents
{
    public static function ProjectsStartBP362()
    {

        \Bitrix\Main\Loader::includeModule('crm');

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(174);

        $items = $factory->getItems([
            'filter' => ['STAGE_ID' => ["DT174_10:1", "DT174_10:2", "DT174_10:3", "DT174_10:CLIENT", "DT174_10:PREPARATION", "DT174_10:NEW"]],
            'select' => ['ID'],
            'order' => ['ID' => 'ASC'],
        ]);

        foreach ($items as $item) {
            $documentId = ["crm", "Bitrix\Crm\Integration\BizProc\Document\Dynamic", "DYNAMIC_174_" . $item["ID"]];
            $wfId = \CBPDocument::StartWorkflow(
                362,
                $documentId,
                [],
                $arErrorsTmp
            );
        }
        return '\Dbbo\Agent\BPAgents::ProjectsStartBP362();';
    }

    public static function openProjectsStartBP()
    {
        $date = (new \DateTime('now'))->format('d.m.Y');
        $hasStarted = (\CIblockElement::GetList(["ID" => "ASC"], ["IBLOCK_ID" => 52, "=NAME" => "openProjectsStartBP", "=PROPERTY_DATE" => $date ], false, false, ["ID","NAME"])->Fetch()["ID"] > 0);

        if (!$hasStarted) {

            \Bitrix\Main\Loader::includeModule('crm');

            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(174);

            $items = $factory->getItems([
                'filter' => ['STAGE_ID' => PROJECTOPENEDSTATUSES],
                'select' => ["ID"],
                'order' => ['ID' => 'ASC'],
            ]);
            
            foreach ($items as $item) {
                $documentId = ["crm", "Bitrix\Crm\Integration\BizProc\Document\Dynamic", "DYNAMIC_174_" . $item["ID"]];
                $wfId = \CBPDocument::StartWorkflow(
                    519,
                    $documentId,
                    [],
                    $arErrorsTmp
                );  
            }

            //Добавим в лог
            $el = new \CIBlockElement;
            $arFields = [
                'IBLOCK_ID' => 52,
                'NAME' => "openProjectsStartBP",
                "PROPERTY_VALUES" => [
                    'DATE' => (new \DateTime('now'))->format('d.m.Y'),
                ]
            ];
            $el->Add($arFields);

            $fh = fopen($_SERVER['DOCUMENT_ROOT'].'/agentsStarts.txt', 'a+');
            fwrite($fh, "Запуск Агента openProjectsStartBP: " . (new \DateTime('now'))->format('d.m.Y H:i:s').PHP_EOL);
            fclose($fh);
        //+\Kint::Dump($ps);
        }

        return '\Dbbo\Agent\BPAgents::openProjectsStartBP();';
    }
}

