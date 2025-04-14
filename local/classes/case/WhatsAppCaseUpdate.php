<?php

define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;

class MessageProcessor
{
    private $messageId;
    private $statusMessage;

    const LIST_ID = 58; // ID инфоблока, где нужно искать
    const LOG_DIR = '/home/bitrix/ext_www/crm.highsystem.ru/upload/wa/log/';

    public function __construct(string $messageId, string $statusMessage)
    {
        global $USER;

        $this->messageId = $messageId;
        $this->statusMessage = $statusMessage;

        $this->log("Создание объекта MessageProcessor. messageId: {$messageId}, statusMessage: {$statusMessage}");

        // Подключение необходимых модулей
        $this->loadModules();

        // Авторизация пользователя
        $this->authorizeUser(128); // Укажите ID пользователя

        // Логируем информацию о текущем пользователе
        $this->logUserInfo();

        $this->log("Подготовка завершена. Обработка началась.");
    }

    /**
     * Основной метод обработки сообщения.
     */
    public function processMessage(): void
    {
        $this->log("Вызов метода processMessage.");

        $element = $this->findElementByMessageId();
        if (!$element) {
            $this->log("Элемент с messageId {$this->messageId} не найден.");
            return;
        }

        $elementList = $element["ID"];
        $caseId = $element["PROPERTY_ID_CASE_VALUE"];
        $ownerId = $element["PROPERTY_DEAL_VALUE"];
        $contactId = $element["PROPERTY_CONTACT_VALUE"];
        $userId = $element["PROPERTY_INITIATOR_MESSAGE_VALUE"];
        $fileName = $element["PROPERTY_NAME_FILE_VALUE"];
        $fileLink = $element["PROPERTY_LINK_FILE_VALUE"];

        // Обработка дела
        $messageText = $this->processCase($caseId);

        // Создание нового дела
        $this->log("Формирование нового дела.");
        $newCase = new WhatsAppActivity($ownerId, 'Deal', $messageText, $contactId, $userId, $fileName, $fileLink, 1);
        $newCaseId = $newCase->createActivity(false, $this->statusMessage);

        // Обновляем свойства элемента
        \CIBlockElement::SetPropertyValuesEx($elementList, self::LIST_ID, [
            "ID_CASE" => $newCaseId,
            "STATUS_MESSAGE_EDNA" => $this->statusMessage,
        ]);

        $this->log("Новое дело создано: {$newCaseId}. Элемент списка обновлён: {$elementList}.");
    }

    /**
     * Поиск элемента по messageId.
     */
    private function findElementByMessageId(): ?array
    {
        $this->log("Поиск элемента с messageId: {$this->messageId}.");
        $res = CIBlockElement::GetList([], [
            'IBLOCK_ID' => self::LIST_ID,
            'PROPERTY_ID_MESSAGE_EDNA' => $this->messageId
        ], false, false, [
            "ID",
            "PROPERTY_DEAL",
            "PROPERTY_ID_MESSAGE_EDNA",
            "PROPERTY_CONTACT",
            "PROPERTY_INITIATOR_MESSAGE",
            "PROPERTY_NAME_FILE",
            "PROPERTY_LINK_FILE",
            "PROPERTY_STATUS_MESSAGE_EDNA",
            "PROPERTY_ID_CASE"
        ]);

        $element = $res->Fetch();
        if ($element) {
            $this->log("Элемент найден: ID {$element['ID']}.");
        } else {
            $this->log("Элемент с messageId {$this->messageId} не найден.");
        }

        return $element;
    }

    /**
     * Обработка дела (получение текста сообщения и удаление старого дела).
     */
    private function processCase(?int $caseId): ?string
    {
        $this->log("Вызов метода processCase для caseId: {$caseId}");

        if (!$caseId) {
            $this->log('ID дела не задан.');
            return null;
        }

        $res = \CCrmActivity::GetByID($caseId);
        if (!$res) {
            $this->log("Дело с ID {$caseId} не найдено.");
            return null;
        }

        // Получение текста сообщения
        $decodeMessageValue = json_decode($res['PROVIDER_DATA'] ?? '', true);
        $messageText = $decodeMessageValue['body']['blocks']['block-description']['properties']['value'] ?? '';

        // Удаление дела
        \CCrmActivity::Delete($caseId);
        $this->log("Дело с ID {$caseId} успешно удалено.");

        return $messageText;
    }

    /**
     * Логирование сообщений.
     */
    private function log(string $message): void
    {
        $logFile = self::LOG_DIR . date("Y_m_d") . "_EdnaProcessLog.txt";
        $logEntry = date("Y-m-d H:i:s") . " - " . $message . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Авторизация пользователя.
     */
    private function authorizeUser(int $userId): void
    {
        global $USER;
        $USER = new \CUser();
        $USER->Authorize($userId);

        if ($USER->IsAuthorized()) {
            $this->log("Пользователь с ID {$userId} успешно авторизован.");
        } else {
            $this->log("Ошибка авторизации пользователя с ID {$userId}.");
            die("Ошибка авторизации пользователя.");
        }
    }

    /**
     * Логируем информацию о текущем пользователе.
     */
    private function logUserInfo(): void
    {
        global $USER;

        if ($USER && $USER->IsAuthorized()) {
            $this->log("Текущий пользователь ID: " . $USER->GetID());
        } else {
            $this->log("Скрипт выполняется без авторизации пользователя.");
        }
    }

    /**
     * Подключение модулей.
     */
    private function loadModules(): void
    {
        if (!Loader::includeModule('lists')) {
            die('Не удалось подключить модуль списков.');
        }

        if (!Loader::includeModule('crm')) {
            die('Не удалось подключить модуль CRM.');
        }

        require_once '/home/bitrix/ext_www/crm.highsystem.ru/local/classes/case/WhatsAppCase.php';
    }
}
