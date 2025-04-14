<?php

use Bitrix\Mail\Internals\MailMessageAttachmentTable;
use Bitrix\Mail\MailMessageTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Mail\Event;

define("STOP_STATISTICS", true);
define("NOT_CHECK_PERMISSIONS", true);
global $USER;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

Loader::includeModule('mail');

if (!function_exists('sendJson')) {
    function sendJson($result)
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        header('Content-Type: text/json; charset=' . LANG_CHARSET);

        echo json_encode($result);
        die();
    }
}
if (!function_exists('sendJsonError')) {
    function sendJsonError($error)
    {
        $result = [
            'success' => false,
            'error' => $error
        ];

        sendJson($result);
    }
}

$attachment = [];

$id = (int)$_REQUEST['id'];

if (0 >= $id) {
    sendJsonError('Неверный id ' . $id);
}

if (!$message = MailMessageTable::getById($id)->fetch()) {
    sendJsonError('Письмо не найдено! id ' . $id);
}

$body = $message['BODY_HTML'] ?? $message['BODY'];

if ($message['BODY_HTML']) {
    $body = '<div>---------- Пересылаемое сообщение ----------</div>'
        . '<div>От: ' . $message['FIELD_FROM'] . '</div>'
        . '<div style="padding-bottom: 25px;">Получено: ' . ($message['FIELD_DATE'])->toString() . '</div>'
        . $body;
} else {
    $body = '---------- Пересылаемое сообщение ----------' . "/r/n"
        . 'От: ' . $message['FIELD_FROM'] . "/r/n"
        . 'Получено: ' . ($message['FIELD_DATE'])->toString() . "/r/n" . "/r/n"
        . $body;
}


if (!empty($_REQUEST["files"])) {
 /*   $attachment = MailMessageAttachmentTable::getList([
        'filter' => ['MESSAGE_ID' => $id]
    ])->fetchAll();
    $attachment = array_map(function ($item) {
        return $item['ID'];
    }, $attachment);
    $mailFields['FILE'] = $attachment;*/
    foreach ($_REQUEST["files"] as $file) {
        $body .= '<br><a href="https://crm.highsystem.ru'.$file['URL'].'">'.$file['NAME'].'</a>';
    }
}

$mailFields = [
    'EVENT_NAME' => 'ACTIVITY_RESEND',
    'LID' => 's1',
        'RECEIVER' => $USER->GetEmail(),
        'SENDER' => $USER->GetEmail(),
        'MESSAGE' => $body,
        'REPLY_TO' => '',
        'TITLE' => $message['SUBJECT']
];

CEvent::SendImmediate("ACTIVITY_RESEND", "s1", $mailFields,"N",190);

sendJson(['success' => true, 'message' => $message]);
