<?php

namespace IIT\MailSyncModule;

use Bitrix\Mail\Helper\Mailbox;
use Bitrix\Main\Loader;
use Bitrix\Main\Diag\Debug; // Импорт класса Debug
use Bitrix\Main\Diag\Helper;


class MailSync
{
    public static function syncMailbox($mailbox)
    {

        // Логируем запуск Агента
        $logFilePath = "/local/logs/mail/mailAgentStart_" . date("Y-m-d") . ".log";
        $logData = date('Y-m-d H:i:s') . ': Запущен агент';
        $massage = $mailbox;

        Debug::writeToFile($massage, $logData, $logFilePath);

        Loader::includeModule('mail');
		if(!$mailbox) {
			return;
		}
        $result = [];
		$mailboxHelper = Mailbox::createInstance($mailbox);
 		$mailboxHelper->setSyncParams(array(
			'full' => true,
		));
        $count = $mailboxHelper->sync();
        self::logdb($mailbox, 'MAILSYNC',"Новых сообщений:" .$count);

        //Логируем вызовы
        $logFilePath = "/local/logs/mail/mailAgentTrace_" . date("Y-m-d") . ".log";
        $massage = Helper::getBackTrace($limit = 0, $options = null);
        $logData = date('Y-m-d H:i:s') . ': Получены методы';
        Debug::writeToFile($massage, $logData, $logFilePath);

        return 'IIT\MailSyncModule\MailSync::syncMailbox('. $mailbox .');';
    }

    public static function syncAllMailboxes()
    {
        Loader::includeModule('mail');

        $mailBoxes = self::getMailBoxes();
        $result = [];
        foreach ($mailBoxes as $mailbox) {
            $mailboxHelper = Mailbox::createInstance($mailbox["ID"]);
            $mailboxHelper->setSyncParams(array(
                'full' => true,
            ));
            $count = $mailboxHelper->sync();
            self::logdb($mailbox["ID"], 'MAILSYNC', "Новых сообщений:" .$count);
            
        }
		return 'IIT\MailSyncModule\MailSync::syncAllMailboxes();';
    }

    public static function logdb($var, $type = 'IIT_LOG',$descr="") {
        \CEventLog::Add([
            "SEVERITY" => "INFO",
            "AUDIT_TYPE_ID" => $type,
            "MODULE_ID" => "crm",
            "ITEM_ID" => $var ?: 0,
            "DESCRIPTION" => $descr,
        ]);
    }

    private static function getMailBoxes() {
        \Bitrix\Main\Loader::includeModule('mail');
        $selectResult = \CMailbox::getList([], ["ACTIVE" => "Y"]);
        while ($mailbox = $selectResult->fetch()) {
            $mailboxes[] = $mailbox;
        }

        foreach ($mailboxes as $key => $mailbox) {
            $query = \Bitrix\Mail\MailFilterTable::query()
            ->setSelect(["MAILBOX_ID","ACTION_PHP"])
            ->setFilter([
                "MAILBOX_ID" => $mailbox["ID"],
            ])
            ->exec();
            $action = $query->fetch()["ACTION_PHP"];

            if (strstr($action,"activity.php")) {
                $mailboxes[$key]["FILTER"] = "Y";
            }
            else unset($mailboxes[$key]);
        }
        return $mailboxes;
    }
}
