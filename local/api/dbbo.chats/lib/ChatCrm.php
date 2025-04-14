<?php

namespace Dbbo\Chat;

use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\Binding\ContactCompanyTable;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\Timeline\CommentEntry;
use Bitrix\Main\ArgumentException;
use CCrmBizProcEventType;
use CCrmBizProcHelper;
use CCrmOwnerType;
use Dbbo\Crm\Company;
use Dbbo\Crm\CrmSearch;
use Exception;
use function NormalizePhone;

class ChatCrm implements CrmInterface
{
    private int $assignedBy;

    /**
     * Ищем ответственного по email
     *
     * @param string $email
     * @return void
     */
    public function setAssignedByEmail(string $email): void
    {
        if (!$email) {
            return;
        }
        if ($user = User::GetList(['EMAIL' => $email], ['ID'])) {
            $this->assignedBy = $user['ID'];
        }
    }

    /**
     * Устанавливаем ответственного по ID пользователя
     *
     * @param int $userId
     * @return void
     */
    public function setAssignedBy(int $userId): void
    {
        $this->assignedBy = $userId;
    }

    /**
     * Имя пользователя по ID
     *
     * @param int $userId
     * @return string|null
     */
    public function getUserNameByID(int $userId): ?string
    {
        if ($user = User::GetList(['ID' => $userId], ['ID', 'NAME', 'LAST_NAME', 'LOGIN'])) {
            $assignedBy = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
            return $assignedBy ?? $user['LOGIN'];
        }

        return null;
    }

    /**
     * Поиск сделки по фильтру от коннектора
     *
     * @param array $dealFilter
     * @param bool $onlyOpen
     * @return array
     */
    public function findDeal(array $dealFilter, bool $onlyOpen = true): array
    {
        $dealFilter['CHECK_PERMISSIONS'] = 'N';
        if ($onlyOpen) {
            $dealFilter['STAGE_SEMANTIC_ID'] = 'P';
        }

        return Deal::getList(['ID' => 'DESC'], $dealFilter);
    }

    public function updateDealChat(array $deal, array $chatData): bool
    {
        if (!is_array($chatData['messages'])) {
            return false;
        }
        if ($chatData['assignedEmail']) {
            $this->setAssignedByEmail($chatData['assignedEmail']);
        }

        foreach ($chatData['messages'] as $dayMessages) {
            $text = implode("\r\n", $dayMessages);
            try {
                $this->ProcessAddMessage($deal['ID'], 'deal', $text);
                if ($deal['CONTACT_ID']) {
                    $this->ProcessAddMessage($deal['CONTACT_ID'], 'contact', $text);
                }
//            if ($deal['COMPANY_ID']) {
//                $this->ProcessAddMessage($deal['COMPANY_ID'], 'company', $text);
//            }
            } catch (Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Обновляет данные контакта для сделки
     *
     * @param array $deal
     * @param array $contactData
     * @return array
     */
    public function updateDealContact(array $deal, array $contactData): array
    {
        $result = [
            'updated' => false
        ];
        if ($deal['CONTACT_ID']) {
            if ($deal['ASSIGNED_BY_ID']) {
                $this->setAssignedBy($deal['ASSIGNED_BY_ID']);
            }
            try {
                return $this->ProcessContactUpdate(
                    $deal['CONTACT_ID'], $contactData, 'deal', $deal['ID']
                );
            } catch (Exception $e) {
                return $result;
            }
        }

        return $result;
    }

    /**
     * Обновляет данные контакта для лида
     *
     * @param array $lead
     * @param array $contactData
     * @return array
     */
    public function updateLeadContact(array $lead, array $contactData): array
    {
        $result = [
            'updated' => false
        ];
        if ($lead['CONTACT_ID']) {
            try {
                return $this->ProcessContactUpdate(
                    $lead['CONTACT_ID'], $contactData, 'lead', $lead['ID']
                );
            } catch (Exception $e) {
                return $result;
            }
        }

        $update = [];
        if ($contactData['email']) {
            $update['EMAIL'] = $contactData['email'];
        }
        if ($contactData['phone']) {
            $update['PHONE'] = $contactData['phone'];
        }

        if ($search = Contact::Search([], [
            'ENTITY_ID' => 'LEAD',
            'ELEMENT_ID' => $lead['ID']
        ])) {
            foreach ($search as $item) {
                if (isset($update['EMAIL']) && $item['TYPE_ID'] == 'EMAIL'
                    && $item['VALUE'] == $update['EMAIL']
                ) {
                    unset($update['EMAIL']);
                }

                if (isset($update['PHONE']) && $item['TYPE_ID'] == 'PHONE'
                    && NormalizePhone($item['VALUE']) == NormalizePhone($update['PHONE'])
                ) {
                    unset($update['PHONE']);
                }
            }
        }

        $fields = [];
        if (!empty($update)) {
            if ($update['PHONE']) {
                $fields['FM']['PHONE']['n0'] = [
                    'VALUE_TYPE' => 'WORK',
                    'VALUE' => $update['PHONE']
                ];
            }

            if ($update['EMAIL']) {
                $fields['FM']['EMAIL']['n0'] = [
                    'VALUE_TYPE' => 'WORK',
                    'VALUE' => $update['EMAIL']
                ];
            }
        }

        if ($contactData['name'] && !$lead['NAME']) {
            $fields['NAME'] = $contactData['name'];
        }

        if (!empty($fields)) {
            $text = 'Обновлены контактные данные ' . "\r\n";
            $text .= isset($update['PHONE']) ? 'номер телефона - ' . $update['PHONE'] . "\r\n" : '';
            $text .= isset($update['EMAIL']) ? 'почта - ' . $update['EMAIL'] . "\r\n" : '';
            $text .= isset($fields['NAME']) ? 'название - ' . $fields['NAME'] : '';
            try {
                $this->ProcessAddMessage($lead['ID'], 'lead', $text);
            } catch (Exception $e) {

            }

            $result['updated'] = Lead::Update($lead['ID'], $fields);
        }

        return $result;
    }

    /**
     * Ищем лиды по фильтру от коннектора
     *
     * @param array $leadFilter
     * @param bool $onlyOpen
     * @return array
     */
    public function findLead(array $leadFilter, bool $onlyOpen = true): array
    {
        $leadFilter['CHECK_PERMISSIONS'] = 'N';
        if ($onlyOpen) {
            $leadFilter['STATUS_SEMANTIC_ID'] = 'P';
        }

        return Lead::GetList([], $leadFilter);
    }

    public function updateLeadChat(array $lead, array $chatData): bool
    {
        if (!is_array($chatData['messages'])) {
            return false;
        }
        if ($chatData['assignedEmail']) {
            $this->setAssignedByEmail($chatData['assignedEmail']);
        }

        foreach ($chatData['messages'] as $dayMessages) {
            $text = implode("\r\n", $dayMessages);
            try {
                $this->ProcessAddMessage($lead['ID'], 'lead', $text);
                if ($lead['CONTACT_ID']) {
                    $this->ProcessAddMessage($lead['CONTACT_ID'], 'contact', $text);
                }
                if ($lead['COMPANY_ID']) {
                    $this->ProcessAddMessage($lead['COMPANY_ID'], 'company', $text);
                }
            } catch (Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Поиск лида по контактной информации
     *
     * @param array $contactData
     * @return array
     */
    public function findLeadByContactData(array $contactData): array
    {
        $leadIds = [];
        $searchResult = CrmSearch::byPhonesAndEmails([$contactData['phone']], [$contactData['email']]);

        // Найдено по телефону и email
        if (isset($searchResult['byPhonesAndEmails']) && !empty($searchResult['byPhonesAndEmails']['LEAD'])) {
            $leadIds = $searchResult['byPhonesAndEmails']['LEAD'];
        }

        if (empty($leadIds)) {
            // Найдено по телефону
            if (isset($searchResult['byPhones']) && !empty($searchResult['byPhones']['LEAD'])) {
                $leadIds = $searchResult['byPhones']['LEAD'];
            }

            // Найдено по email
            if (isset($searchResult['byEmails']) && !empty($searchResult['byEmails']['LEAD'])) {
                $leadIds = array_merge($searchResult['byEmails']['LEAD']);
            }
            $leadIds = array_unique($leadIds);
        }

        return $leadIds ? Lead::GetList([], [
            'ID' => $leadIds,
            'STATUS_SEMANTIC_ID' => 'P',
            'CHECK_PERMISSIONS' => 'N'
        ]) : [];
    }

    /**
     * Создание лида из данных
     *  $leadData [
     *      'contact' array [name, phone, email]
     *      'leadFilter' array
     *      'sourceId' int
     *      'chatId' int
     *      'geoIp' array ['country', 'region', 'city']
     *  ]
     *
     * @param array $leadData
     * @param array $chat
     * @param int|null $contactId Id контакта
     * @param int|null $companyId
     * @return array|null
     */
    public function createLead(array $leadData, array $chat, ?int $contactId = null, ?int $companyId = null): ?array
    {
        global $USER;
        if (!$chat['assignedEmail']) {
            return null;
        }
        $this->setAssignedByEmail($chat['assignedEmail']);
        $USER->Authorize($this->assignedBy);

        $add_title = $leadData['ChatTitle'];
        $add_title .= $leadData['contact']['phone'] ? ' - ' . $leadData['contact']['phone'] : '';
        $add_title .= ' - ' . $leadData['chatId'];

        $fields = array_merge([
            'TITLE' => ($leadData['contact']['name']) ?: 'Запрос ' . $add_title,
            'NAME' => ($leadData['contact']['name']) ?: '',
            'OPENED' => 'Y',
            'ASSIGNED_BY_ID' => $this->assignedBy,
            'SOURCE_ID' => $leadData['sourceId']
        ], $leadData['leadFilter']);

        if ($contactId) {
            $fields['CONTACT_ID'] = $contactId;
        } else {
            if ($leadData['contact']['email']) {
                $fields['FM']['EMAIL'] = [
                    'n0' => [
                        'VALUE_TYPE' => 'WORK',
                        'VALUE' => trim($leadData['contact']['email'])
                    ]
                ];
            }

            if ($leadData['contact']['phone']) {
                $fields['FM']['PHONE'] = [
                    'n0' => [
                        'VALUE_TYPE' => 'WORK',
                        'VALUE' => $leadData['contact']['phone']
                    ]
                ];
            }
        }
        if ($companyId) {
            $fields['COMPANY_ID'] = $companyId;
        }

        if (!$leadId = Lead::Add($fields)) {
            return null;
        }
        $fields['ID'] = $leadId;
        $result['leadId'] = $fields['ID'];

        $text = 'Менеджер начал диалог с клиентом по чату ' . $leadData['chatId'] . '.' . "\r\n";
        $text .= ($leadData['contact']['name']) ? 'Посетитель - ' . $leadData['contact']['name'] . "\r\n" : '';
        $text .= ($leadData['contact']['phone']) ? 'Телефон - ' . $leadData['contact']['phone'] . "\r\n" : '';
        $text .= ($leadData['contact']['email']) ? 'Email - ' . $leadData['contact']['email'] . "\r\n" : '';

        if ($leadData['geoIp']) {
            $text .= ' Страна - ' . $leadData['geoIp']['country'] . "\r\n";
            $text .= 'Регион - ' . $leadData['geoIp']['region'] . "\r\n";
            $text .= 'Город - ' . $leadData['geoIp']['city'] . "\r\n";
        }
        try {
            $this->ProcessAddMessage($leadId, 'lead', $text);
        } catch (Exception $e) {

        }

        try {
            CCrmBizProcHelper::AutoStartWorkflows(
                CCrmOwnerType::Lead,
                $leadId,
                CCrmBizProcEventType::Create,
                $errors
            );

            Factory::runOnAdd(CCrmOwnerType::Lead, $leadId);
        } catch (Exception $e) {

        }

        $result = ['url' => "/crm/lead/details/{$leadId}/"];
        if (!empty($chat)) {
            $result['chatUpdated'] = $this->updateLeadChat($fields, $chat);
        }

        if ($assignedBy = $this->getUserNameByID($fields['ASSIGNED_BY_ID'])) {
            $result['Admin'] = $assignedBy;
        }

        return $result;
    }

    /**
     * Возвращает контакты по контактной информации
     *
     * @param array $contactData
     * @return array
     */
    public function findContact(array $contactData): array
    {
        $contacts = [];
        $searchResult = CrmSearch::byPhonesAndEmails([$contactData['phone']], [$contactData['email']]);

        // Найдено по телефону и email
        if (isset($searchResult['byPhonesAndEmails']) && !empty($searchResult['byPhonesAndEmails']['CONTACT'])) {
            $contacts = $searchResult['byPhonesAndEmails']['CONTACT'];
        }

        if (empty($contacts)) {
            // Найдено по телефону
            if (isset($searchResult['byPhones']) && !empty($searchResult['byPhones']['CONTACT'])) {
                $contacts = $searchResult['byPhones']['CONTACT'];
            }

            // Найдено по email
            if (isset($searchResult['byEmails']) && !empty($searchResult['byEmails']['CONTACT'])) {
                $contacts = array_merge($searchResult['byEmails']['CONTACT']);
            }
            $contacts = array_unique($contacts);
        }

        // TODO: Первым нужен контакт, привязанный к сделке или лиду с последней активностью
        return Contact::getList(['ID' => 'DESC'], ['ID' => $contacts, 'CHECK_PERMISSIONS' => 'N']) ?? [];
    }

    /**
     * Поиск лида по контакту
     *
     * @param int $contactId
     * @return array
     */
    public function findLeadByContact(int $contactId): array
    {
        return Lead::getList(['ID' => 'DESC'], [
            'CONTACT_ID' => $contactId,
            'STATUS_SEMANTIC_ID' => 'P',
            'CHECK_PERMISSIONS' => 'N'
        ]);
    }

    /**
     * Запись chatId в лид
     *
     * @param array $lead
     * @param array $leadFilter
     * @return string[]|null
     */
    public function bindLead(array $lead, array $leadFilter): ?array
    {
        if (!Lead::Update($lead['ID'], $leadFilter)) {
            return null;
        }
        $result = ['url' => "/crm/lead/details/{$lead['ID']}/"];
        if ($assignedBy = $this->getUserNameByID($lead['ASSIGNED_BY_ID'])) {
            $result['Admin'] = $assignedBy;
        }

        return $result;
    }

    /**
     * Запись chatId в сделку
     *
     * @param array $deal
     * @param array $dealFilter
     * @return string[]|null
     */
    public function bindDeal(array $deal, array $dealFilter): ?array
    {
        if (!Deal::Update($deal['ID'], $dealFilter)) {
            return null;
        }
        $result = ['url' => "/crm/deal/details/{$deal['ID']}/"];
        if ($assignedBy = $this->getUserNameByID($deal['ASSIGNED_BY_ID'])) {
            $result['Admin'] = $assignedBy;
        }

        return $result;
    }

    /**
     * Поиск сделки по контакту
     *
     * @param int $contactId
     * @return array
     */
    public function findDealByContact(int $contactId): array
    {
        return Deal::getList(["ID" => "DESC"], [
            'CONTACT_ID' => $contactId,
            'STAGE_SEMANTIC_ID' => 'P',
            'CHECK_PERMISSIONS' => 'N'
        ]);
    }

    public function getCompanyData(int $companyId): array
    {
        $result = [];
        $company = \Dbbo\Crm\Company::GetList(
            ['ID' => 'ASC'],
            [
                'CHECK_PERMISSIONS' => 'N',
                'ID' => $companyId
            ]);

        if ($company[0]) {
            $result = [
                'name' => $company[0]['TITLE'],
                'inn' => $company[0][CRM_SETTINGS['company']['inn']]
            ];
        }

        return $result;
    }

    /**
     * Обновляет данные контакта для сущности
     *
     * @param int $contactId
     * @param array $contactData
     * @param string $entityType
     * @param int $entityId
     * @return array
     * @throws ArgumentException
     */
    private function ProcessContactUpdate(
        int $contactId, array $contactData, string $entityType, int $entityId
    ): array
    {
        $update = [];
        $result = [
            'updated' => false
        ];

        if ($contactData['email']) {
            $result['oldEmail'] = $update['EMAIL'] = $contactData['email'];
        }
        if ($contactData['phone']) {
            $result['oldPhone'] = $update['PHONE'] = $contactData['phone'];
        }

        if ($search = Contact::Search([], [
            'ENTITY_ID' => 'CONTACT',
            'ELEMENT_ID' => $contactId
        ])) {
            foreach ($search as $item) {
                if (isset($update['EMAIL']) && $item['TYPE_ID'] == 'EMAIL'
                    && $item['VALUE'] == $update['EMAIL']
                ) {
                    unset($update['EMAIL']);
                } elseif ($item['TYPE_ID'] == 'EMAIL') {
                    $result['email'][] = $item['VALUE'];
                }

                if (isset($update['PHONE']) && $item['TYPE_ID'] == 'PHONE'
                    && NormalizePhone($item['VALUE']) == NormalizePhone($update['PHONE'])
                ) {
                    unset($update['PHONE']);
                } elseif ($item['TYPE_ID'] == 'PHONE') {
                    $result['phone'][] = $item['VALUE'];
                }
            }
        }

        $fields = [];
        if (!empty($update)) {
            if ($update['PHONE']) {
                $fields['FM']['PHONE']['n0'] = [
                    'VALUE_TYPE' => 'WORK',
                    'VALUE' => $update['PHONE']
                ];
            }

            if ($update['EMAIL']) {
                $fields['FM']['EMAIL']['n0'] = [
                    'VALUE_TYPE' => 'WORK',
                    'VALUE' => $update['EMAIL']
                ];
            }
        }

        $contact = Contact::getList([
            'ID' => 'DESC'
        ], [
            'ID' => $contactId,
            'CHECK_PERMISSIONS' => 'N'
        ]);

//        if ($contactData['name']) {
//            if (!$contact['NAME']) {
//                $fields['NAME'] = $contactData['name'];
//            }
//        }

        if (!empty($fields)) {
            $text = 'Обновлены контактные данные ' . "\r\n";
            $text .= isset($update['PHONE']) ? 'номер телефона - ' . $update['PHONE'] . "\r\n" : '';
            $text .= isset($update['EMAIL']) ? 'почта - ' . $update['EMAIL'] . "\r\n" : '';
//            $text .= isset($fields['NAME']) ? 'название - ' . $fields['NAME'] : '';

            $this->ProcessAddMessage($contactId, 'contact', $text);
//            $this->ProcessAddMessage($entityId, $entityType, $text);

            $result['updated'] = Contact::Update($contactId, $fields);
        }

        return $result;
    }

    /**
     * Добавляет $text в timeline указанной сущности
     *
     * @param $entityId
     * @param $entityType
     * @param $text
     * @return void
     * @throws ArgumentException
     */
    private function ProcessAddMessage($entityId, $entityType, $text): void
    {
        $type = '';

        switch ($entityType) {
            case 'deal':
                $type = CCrmOwnerType::Deal;
                break;
            case 'lead':
                $type = CCrmOwnerType::Lead;
                break;
            case 'contact':
                $type = CCrmOwnerType::Contact;
                break;
            case 'company':
                $type = CCrmOwnerType::Company;
                break;
            default:
                break;
        }

        if ($type) {
            CommentEntry::create([
                'TEXT' => $text,
                'SETTINGS' => [],
                'AUTHOR_ID' => $this->assignedBy,
                'BINDINGS' => [['ENTITY_TYPE_ID' => $type, 'ENTITY_ID' => $entityId]]
            ]);
        }
    }

    public function getContactCompanies(int $contactId): array
    {
        $ids = [];
        $companies = [];

        $res = ContactCompanyTable::getList([
            'filter' => ['CONTACT_ID' => $contactId]
        ]);
        while($contactCompany = $res->fetch()) {
            $ids[] = $contactCompany['COMPANY_ID'];
        }
        if (empty($ids)) {
            return [];
        }

        $res = CompanyTable::getList([
            'filter' => ['ID' => $ids]
        ]);
        while ($company = $res->fetch()) {
            $companies[] = $company;
        }

        return $companies;
    }
}