<?php

use Bitrix\Main\Loader;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\LayoutDto;
use Bitrix\Crm\ItemIdentifier;

Loader::includeModule('crm');

class WhatsAppActivity
{
    private $ownerId;
    private $ownerTypeId;
    private $messageTemplate;
    private $phoneNumber;
    private $restClientId;
    private $contactId;
    private $userId;
    private $fileName;
    private $fileLink;
    private $isConsentGiven;
    private $errors = [];

    public function __construct(
        int $ownerId,
        string $ownerType,
        string $messageTemplate,
        int $contactId,
        int $userId,
        string $fileName = null,
        string $fileLink = null,
        int $restClientId = 1,
        bool $isConsentGiven = false
    ) {
        $this->ownerId = $ownerId;
        $this->ownerTypeId = $this->getOwnerTypeId($ownerType);
        $this->messageTemplate = $messageTemplate;
        $this->phoneNumber = $this->getContactPhoneNumber($contactId);
        $this->contactId = $contactId;
        $this->userId = $userId;
        $this->fileName = $fileName;
        $this->fileLink = $fileLink;
        $this->restClientId = $restClientId;
        $this->isConsentGiven = $isConsentGiven;
    }

    private function getOwnerTypeId(string $ownerType): int
    {
        switch (strtolower($ownerType)) {
            case 'lead':
                return \CCrmOwnerType::Lead;
            case 'deal':
                return \CCrmOwnerType::Deal;
            case 'contact':
                return \CCrmOwnerType::Contact;
            default:
                throw new \InvalidArgumentException("Неизвестный тип владельца: $ownerType");
        }
    }

    private function getContactPhoneNumber(int $contactId): ?string
    {
        $contactInfo = new ContactInfo();
        return $contactInfo->getContactCustomFieldById($contactId, 'UF_CRM_671E7C6C5611A') ?? 'Не указан номер';
    }

    private function getContactNameAndPhone(int $contactId): string
    {
        $contactInfo = new ContactInfo();
        return $contactInfo->getContactNameAndPhoneById($contactId);
    }

    private function getManagerName(int $userId): string
    {
        $employeeInfo = new EmployeeInfo();
        return $employeeInfo->getEmployeeNameById($userId) ?? 'Неизвестный менеджер';
    }

    public function createActivity(bool $isCompleted = false, string $status = 'sent')
    {
        // Определение type и title на основе статуса
        $statusType = 'secondary';
        $statusTitle = 'Отправлено';

        switch ($status) {
            case 'sent':
                $statusType = 'secondary';
                $statusTitle = 'Отправлено';
                break;
            case 'delivered':
                $statusType = 'primary';
                $statusTitle = 'Доставлено';
                break;
            case 'read':
                $statusType = 'success';
                $statusTitle = 'Прочитано';
                break;
            case 'undelivered':
                $statusType = 'warning';
                $statusTitle = 'Не доставлено';
                break;
            case 'cancelled':
                $statusType = 'failure';
                $statusTitle = 'Отправка отменена';
                break;
            case 'expired':
                $statusType = 'failure';
                $statusTitle = 'Не удалось получить';
                break;
            case 'failed':
                $statusType = 'failure';
                $statusTitle = 'Ошибка обработки сообщения';
                break;
            default:
                $statusType = 'secondary';
                $statusTitle = 'Отправлено';
        }

        $layout = [
            "icon" => [
                "code" => "whatsapp"
            ],
            "header" => [
                "title" => "Отправлено сообщение WhatsApp",
                "tags" => [
                    "status" => [
                        "type" => $statusType,
                        "title" => $statusTitle
                    ]
                ]
            ],
            "body" => [
                "logo" => [
                    "code" => "comment"
                ],
                "blocks" => [
                    "client" => [
                        "type" => "withTitle",
                        "properties" => [
                            "title" => "Клиент",
                            "inline" => true,
                            "block" => [
                                "type" => "link",
                                "properties" => [
                                    "text" => $this->getContactNameAndPhone($this->contactId),
                                    "bold" => true,
                                    "action" => [
                                        "type" => "redirect",
                                        "uri" => "/crm/contact/details/{$this->contactId}/"
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "manager" => [
                        "type" => "withTitle",
                        "properties" => [
                            "title" => "Менеджер",
                            "inline" => true,
                            "block" => [
                                "type" => "link",
                                "properties" => [
                                    "text" => $this->getManagerName($this->userId),
                                    "bold" => true,
                                    "action" => [
                                        "type" => "redirect",
                                        "uri" => "/company/personal/user/{$this->userId}/"
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "block-description" => [
                        "type" => "largeText",
                        "properties" => [
                            "value" => $this->messageTemplate
                        ]
                    ]
                ]
            ]
        ];

        if ($this->fileName && $this->fileLink) {
            $layout["body"]["blocks"]["link"] = [
                "type" => "link",
                "properties" => [
                    "text" => "📎 {$this->fileName}",
                    "bold" => true,
                    "action" => [
                        "type" => "redirect",
                        "uri" => $this->fileLink
                    ]
                ]
            ];
        }

        $layoutDto = new LayoutDto($layout);

        if (!$layoutDto->hasValidationErrors()) {
            $activity = new ConfigurableRestApp(
                new ItemIdentifier($this->ownerTypeId, $this->ownerId),
                [
                    'COMPLETED' => $isCompleted ? 'Y' : 'N'
                ]
            );
            $activity->setLayoutDto($layoutDto);
            $activity->setRestClientId($this->restClientId);

            $saveResult = $activity->save();

            if ($saveResult->isSuccess()) {
                return $saveResult->getData()['id'];
            } else {
                foreach ($saveResult->getErrors() as $error) {
                    $this->errors[] = $error->getMessage();
                }
            }
        } else {
            foreach ($layoutDto->getValidationErrors()->toArray() as $error) {
                $this->errors[] = $error->getMessage();
            }
        }
        return null;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

// Удаление дела
class WhatsAppActivityDeleter
{
    public function deleteActivity(int $activityId): bool
    {
        // Удаление активности
        return \CCrmActivity::Delete($activityId);
    }
}

// Получить данные из дела
class WhatsAppActivityInfo
{
    public function getActivityInfo(int $activityId): ?array
    {
        // Получение данных активности
        $activityData = \CCrmActivity::GetByID($activityId);

        if (!$activityData) {
            return null;  // Активность не найдена
        }

        // Декодирование поля PROVIDER_DATA из JSON в массив
        $providerData = json_decode($activityData['PROVIDER_DATA'], true);

        if (!$providerData) {
            return null;  // Данные PROVIDER_DATA отсутствуют или не удалось декодировать
        }

        // Извлечение нужных параметров из PROVIDER_DATA
        return [
            'headerTitle' => $providerData['header']['title'] ?? null,
            'statusTitle' => $providerData['header']['tags']['status']['title'] ?? null,
            'statusType' => $providerData['header']['tags']['status']['type'] ?? null,
            'blockTitleText' => $providerData['body']['blocks']['block-title']['properties']['value'] ?? null,
            'blockDescriptionText' => $providerData['body']['blocks']['block-description']['properties']['value'] ?? null,
        ];
    }
}

// Класс для получения информации о сотруднике
class EmployeeInfo
{
    public function getEmployeeNameById(int $userId): ?string
    {
        $user = \CUser::GetByID($userId)->Fetch();
        return $user ? $user['NAME'] . ' ' . $user['LAST_NAME'] : null;
    }

    public function getEmployeeLinkById(int $userId): string
    {
        return "/company/personal/user/{$userId}/";
    }
}

class ContactInfo
{
    /**
     * Получает полное имя клиента по его ID.
     */
    public function getContactNameById(int $contactId): ?string
    {
        $contactData = ContactTable::getList([
            'filter' => ['=ID' => $contactId],
            'select' => ['ID', 'NAME', 'LAST_NAME'],
            'limit' => 1
        ])->fetch();

        return $contactData ? trim($contactData['NAME'] . ' ' . $contactData['LAST_NAME']) : null;
    }

    /**
     * Получает значение пользовательского поля контакта по его ID и коду поля.
     */
    public function getContactCustomFieldById(int $contactId, string $fieldCode): ?string
    {
        $contactData = ContactTable::getList([
            'filter' => ['=ID' => $contactId],
            'select' => ['ID', $fieldCode],
            'limit' => 1
        ])->fetch();

        return $contactData[$fieldCode] ?? null;
    }

    /**
     * Получает полное имя клиента и телефон из пользовательского поля.
     */
    public function getContactNameAndPhoneById(int $contactId): string
    {
        // Получаем имя и номер телефона из пользовательского поля
        $contactData = ContactTable::getList([
            'filter' => ['=ID' => $contactId],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'UF_CRM_671E7C6C5611A'],
            'limit' => 1
        ])->fetch();

        $name = $contactData ? trim($contactData['NAME'] . ' ' . $contactData['LAST_NAME']) : 'Неизвестный контакт';
        $phone = $contactData['UF_CRM_671E7C6C5611A'] ?? null;

        // Объединяем имя и телефон, если телефон указан
        return $phone ? "{$name} {$phone}" : $name;
    }

    /**
     * Возвращает ссылку на страницу контакта по его ID.
     */
    public function getContactLinkById(int $contactId): string
    {
        return "/crm/contact/details/{$contactId}/";
    }
}

