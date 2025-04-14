<?php

namespace Dbbo\Event;

use Bitrix\Crm\StatusTable;
use Bitrix\Main\Diag\Debug;
use CEvent;
use Dbbo\Crm\Company;
use Dbbo\Crm\Contact;
use Dbbo\Crm\CrmSearch;
use Dbbo\Crm\Fields;
use Dbbo\Crm\Lead;
use Dbbo\Crm\Deal;

class LeadEvent
{
    protected static $handlerDisallow = false;
    protected static $fieldInn = CRM_SETTINGS['lead']['companyInn'];
    protected static $fieldKpp = CRM_SETTINGS['lead']['companyKpp'];
    protected static $companyBrendCode = CRM_SETTINGS['lead']['companyBrand'];
    protected static $leadTypeCode = CRM_SETTINGS['lead']['companyType'];
    protected static $leadBrendCode = CRM_SETTINGS['lead']['brand'];
    protected static $leadPersonTypeCode = CRM_SETTINGS['lead']['person'];
    protected static $leadPersonTypeValue = CRM_SETTINGS['lead']['personValue'];
    protected static $propertyUridAddress = CRM_SETTINGS['lead']['jurAddressIP'];
    protected static $propertyAddressJson = CRM_SETTINGS['lead']['jurAddressIPjson'];
    protected static $propertyAddressDelivery = CRM_SETTINGS['lead']['addressDelivery'];
    protected static $propertyAddressDeliveryJson = CRM_SETTINGS['lead']['addressDeliveryJson'];
    private static string $mailTo = 'a.kupets@highsystem.ru';

    public static $personType;
    public static $inn;
    public static $kpp;

    public static function onBeforeCrmLeadAdd(&$arFields)
    {
        // Форматируем контактные данные
        if (is_array($arFields['FM'])) {
            self::getCriterionByFM($arFields, true);
        }
    }
    public static function onBeforeCrmLeadUpdate(&$arFields)
    {
        if ($arFields['SKIP_EVENT']) {
            return true;
        }

        $leadInfo = Lead::GetItem($arFields['ID']); // Значения полей до изменения
        $searchResult = []; // Результат поиска
        $comment = ''; // Запись в таймлайн
        $oldContacts = []; // Уже привязанные к лиду контакты
        $emailBody = ''; // Текст письма

        // Если группа не позволяет - не может редактировать контактные данные, только добавлять
        if (ContactEvent::checkDenyContactDataEdit($arFields)) {
            return false;
        }

        //Заберем данные из события создания лида (багфикс)
        if (self::$personType > 0) {
            $arFields[self::$leadPersonTypeCode] = self::$personType;
        }
        if (self::$inn > 0) {
            $arFields[self::$fieldInn] = self::$inn;
        }
        if (self::$kpp > 0) {
            $arFields[self::$fieldKpp] = self::$kpp;
        }

        //Если указан ИНН это не физическое лицо
        if ( (isset($arFields[self::$fieldInn]) && $arFields[self::$fieldInn] > 0)  || (!isset($arFields[self::$fieldInn]) && $leadInfo[self::$fieldInn] > 0 ) ) {
            $arFields[self::$leadPersonTypeCode] = 673;
            // Если введён ИНН организации - зануляем адрес ИП и КПП
            if (isset($arFields[self::$fieldInn]) && strlen($arFields[self::$fieldInn]) != 12) {
                $arFields[self::$propertyUridAddress] = "0";
                $arFields[self::$propertyAddressDelivery] = "0";
                $arFields[self::$propertyAddressJson] = '';
                $arFields[self::$propertyAddressDeliveryJson] = '';
            } elseif (strlen($arFields[self::$fieldInn]) == 12) {
                $arFields[self::$fieldKpp] = "";
            }
        }
        else {
            //Иначе физическое лицо
            $arFields[self::$leadPersonTypeCode] = 672;
        }
        
        //Если физическое лицо установим поля в ноль для БП при смене статуса
        if ( $arFields[self::$leadPersonTypeCode] == 672) {
            $arFields[self::$fieldKpp] = "0";
            $arFields[self::$fieldInn] = "0";
            $arFields[self::$propertyUridAddress] = "0";
            $arFields[self::$propertyAddressDelivery] = "0";
            $arFields[self::$propertyAddressJson] = '';
            $arFields[self::$propertyAddressDeliveryJson] = '';
        }

        // Если физ-лицо
/*         if (
            (isset($arFields[self::$leadPersoneTypeCode]) && $arFields[self::$leadPersoneTypeCode] != self::$leadPersoneTypeValue)
            || (!isset($arFields[self::$leadPersoneTypeCode]) && $leadInfo[self::$leadPersoneTypeCode] != self::$leadPersoneTypeValue)
        ) {
            $arFields[self::$propertyUridAddress] = '0';
        } else {
            // Если введён ИНН организации - зануляем адрес ИП
            if (isset($arFields[self::$fieldInn]) && 12 != strlen($arFields[self::$fieldInn])) {
                $arFields[self::$propertyUridAddress] = '0';
            } elseif (12 === strlen($arFields[self::$fieldInn]) && !$arFields[self::$propertyUridAddress]) {
                $arFields[self::$propertyUridAddress] = '';
            }
            // Если не внесён ИНН
            if ((isset($arFields[self::$fieldInn]) && !$arFields[self::$fieldInn])
                ||(!isset($arFields[self::$fieldInn]) && !$leadInfo[self::$fieldInn])
            ) {
                $arFields[self::$propertyUridAddress] = '';
            }
        }
        // Обнуление json данных по адресу ИП и адресу доставки.
        if (isset($arFields[self::$propertyUridAddress])
            && (!$arFields[self::$propertyUridAddress] || '0' === $arFields[self::$propertyUridAddress])
        ) {
            $arFields[self::$propertyAddressJson] = '';
        }
        if (isset($arFields[self::$propertyAddressDelivery]) && !$arFields[self::$propertyAddressDelivery]) {
            $arFields[self::$propertyAddressDeliveryJson] = '';
        } */

        // Получаем текущие контакты лида
        $leadContacts = \Bitrix\Crm\Binding\LeadContactTable::getList([
            'filter' => ['LEAD_ID' => $arFields['ID']]
        ])->fetchAll();
        foreach ($leadContacts as $leadContact) {
            $oldContacts[] = $leadContact['CONTACT_ID'];
        }

        // Нельзя вносить контактные данные, если уже есть контакт, игнорируем введённое
        if (!empty($oldContacts) && is_array($arFields['FM'])) {
            unset($arFields['FM']);
        }

        // Есть новые контакты, обрабатываем
        if ($arFields['CONTACT_IDS'] && 1 === count($arFields['CONTACT_IDS'])) {
            $arFields['CONTACT_ID'] = $arFields['CONTACT_IDS'][0];
        }

        // Если нет добавленных сейчас или ранее контактов, но есть новые/изменённые контактные данные
        if (!$arFields['CONTACT_IDS'] && is_array($arFields['FM'])) {
            unset($arFields['CONTACT_IDS']);

            $searchCriterion = self::getCriterionByFM($arFields, true);
            $searchResult = self::searchByPhoneEmail($searchCriterion);

            if (is_array($searchResult['contacts']) && 1 === count($searchResult['contacts'])) {
                $arFields['CONTACT_ID'] = $searchResult['contacts'][0]['ID'];
            }

            if ($searchResult['comment']) {
                $comment .= $searchResult['comment'];
                if ($searchResult['sendEmail']) {
                    $emailBody .= $searchResult['comment'];
                }
                unset($searchResult['comment']);
            }
        }

        // Поиск компании по реквизитам
        if ($arFields[self::$fieldInn] || $arFields[self::$fieldKpp]) {

            $search = self::searchCompanyByRequisite($arFields, $leadInfo);

            if ($search['companyByRequisite']) {
                $searchResult['companyByRequisite'] = $search['companyByRequisite'];
                $searchResult['companies'] = $search['companyByRequisite'];
            }

            if ($search['comment']) {
                $comment .= $search['comment'];
                if ($search['sendEmail']) {
                    $emailBody .= $search['comment'];
                }
            }
        }

        // Если привязали контакт
        if ($arFields['CONTACT_ID']) {
            if (!empty($searchResult['companyByRequisite'])) {
                // Если по реквизитам найдена только одна компания - привязываем к лиду и привязываем контакт к компании
                if (1 === count($searchResult['companyByRequisite'])) {
                    $arFields['COMPANY_ID'] = $searchResult['companyByRequisite'][0];
                    // Привязываем контакт к найденной компании
                    \Bitrix\Crm\Binding\ContactCompanyTable::bindCompanyIDs($arFields['CONTACT_ID'], [$arFields['COMPANY_ID']]);
                    \Bitrix\Crm\Binding\LeadContactTable::bindContactIDs($arFields['ID'], [$arFields['CONTACT_ID']]);
                }
            } else {
                $searchResult['companies'] = Contact::getContactCompanyIDs($arFields['CONTACT_ID']);
                if (0 === count($searchResult['companies'])) {
                    $comment .= "\r\n" . 'Компании у контакта не найдены' . "\r\n";
                } else {
                    $companyComment = self::getCompanyComment($searchResult['companies'], 'По контакту');
                    $comment .= $companyComment['comment'];
                    if ($companyComment['sendEmail']) {
                        $emailBody .= $companyComment['comment'];
                    }
                }
                // Если найдена строго одна компания - привязываем к лиду
                if (1 === count($searchResult['companies'])) {
                    $arFields['COMPANY_ID'] = $searchResult['companies'][0];
                }
            }
        }
        // Если ввели ИНН, а контакты были ранее привязаны
        if (!$arFields['CONTACT_ID'] && !empty($searchResult['companyByRequisite']) && !empty($oldContacts)) {
            $oldContactId = false;
            if (1 === count($oldContacts)) {
                $oldContactId = $oldContacts[0];
            }
            if ($oldContactId && 1 === count($searchResult['companyByRequisite'])) {
                $arFields['COMPANY_ID'] = $searchResult['companyByRequisite'][0];
                // Привязываем контакт к найденной компании
                \Bitrix\Crm\Binding\ContactCompanyTable::bindCompanyIDs($oldContactId, [$arFields['COMPANY_ID']]);
                \Bitrix\Crm\Binding\LeadContactTable::bindContactIDs($arFields['ID'], [$oldContactId]);
            }
        }

        // Если компании не найдены, но есть email - пробуем найти по домену.
        // Было в старом обработчике, сейчас нет в схеме
        if (false &&
            (!isset($searchResult['byRequisites']) || empty($searchResult['byRequisites']['COMPANY']))
            && (!isset($searchResult['byContact']) || empty($searchResult['byContact']['COMPANY']))
            && isset($searchResult['byEmail']) && !empty($searchResult['byEmail']['criterion'])
        ) {
            $searchResult['byDomen']['COMPANY'] = Company::CompanyFindByEmail($searchResult['byEmail']['criterion']);
        }

        if (isset($searchResult['byDomen']) && !empty($searchResult['byDomen']['COMPANY'])) {
            $searchResult['COMPANY'] = $searchResult['byContact']['COMPANY'];

            $comment .= "\r\n" . "Найдены компании по поддомену";
            $comment .= '[TABLE][TR][TD]ID[/TD][TD]Сущность[/TD][TD]Название[/TD][TD]Ответственный[/TD][/TR]';

            $dataCompany = Company::GetList([
                'ID' => 'ASC'
            ], [
                'CHECK_PERMISSIONS' => 'N',
                'ID' => $searchResult['byDomen']['COMPANY']
            ]);
            foreach ($dataCompany as $itemCompany) {
                $comment .= '[TR][TD]' . $itemCompany['ID'] . '[/TD][TD]Компания[/TD][TD]' . $itemCompany['TITLE'] . '[/TD][TD]' . $itemCompany['CREATED_BY_LOGIN'] . '[/TD][/TR]';
            }
            $comment .= '[/TABLE]' . "\r\n";
        }

        // Сущности по контактным данным
        $leads = [];
        $title = '';
        // Найдено по телефону и email
        if (isset($searchResult['byPhonesAndEmails']) && !empty($searchResult['byPhonesAndEmails']['LEAD'])) {
            $leads = $searchResult['byPhonesAndEmails']['LEAD'];
            $leads = self::getActiveLeads($leads, $arFields['ID']);
            $searchResult['leads'] = true;

            $title = 'Есть активные лиды по телефонам ' . implode(', ', $searchCriterion['phones']);
            $title .= 'и email ' . implode(', ', $searchCriterion['emails']);
            $comment .= self::getLeadComment($leads, $title);
        }

        // Найдено по телефону
        if (empty($leads)) {

            if (isset($searchResult['byPhones']) && !empty($searchResult['byPhones']['LEAD'])) {
                $leads = $searchResult['byPhones']['LEAD'];
                $searchResult['leads'] = true;
                $leads = self::getActiveLeads($leads, $arFields['ID']);

                $title = 'Есть активные лиды по телефонам: ' . implode(', ', $searchCriterion['phones']);
                $comment .= self::getLeadComment($leads, $title);
            }

            // Найдено по email
            if (empty($leads) && isset($searchResult['byEmails']) && !empty($searchResult['byEmails']['LEAD'])) {
                $leads = $searchResult['byEmails']['LEAD'];
                $searchResult['leads'] = true;
                $leads = self::getActiveLeads($leads, $arFields['ID']);

                $title = 'Есть активные лиды по email: ' . implode(', ', $searchCriterion['emails']);
                $comment .= self::getLeadComment($leads, $title);
            }
        }

        if ($searchResult['leads'] && 0 === count($leads)) {
            $title = 'По';
            if (!empty($searchCriterion['phones'])) {
                $title .= ' телефонам ' . implode(', ', $searchCriterion['phones']);
            }
            if (!empty($searchCriterion['emails'])) {
                $title .= ' email ' . implode(', ', $searchCriterion['emails']);
            }
            $comment .= $title . ' не найдены' . "\r\n";
        }

        if ($arFields['COMPANY_ID']) {
            // Выбираем все контакты компании
            $contactIds = \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($arFields['COMPANY_ID']);
            // Лиды контактов компании
            $comment .= self::getContactLeadComment(
                $contactIds,
                $arFields['ID'],
                'Есть активные лиды по контактам компании: '
            );
            // Сделки контактов компании
            $comment .= self::getContactDealComment($contactIds, 'Есть активные сделки по контактам компании: ');

        } elseif ($arFields['CONTACT_ID']) {
            // Лиды контакта
            $comment .= self::getContactLeadComment(
                [$arFields['CONTACT_ID']],
                $arFields['ID'],
                'Есть активные лиды по контакту: '
            );
            // Сделки контакта
            $comment .= self::getContactDealComment([$arFields['CONTACT_ID']], 'Есть активные сделки по контакту: ');
        }

        // Записываем комментарий
        if ($comment) {
            \Bitrix\Crm\Timeline\CommentEntry::create(
                array(
                    'TEXT' => $comment,
                    'SETTINGS' => array(),
                    'AUTHOR_ID' => CRM_SETTINGS['deal']['systemUserId'],
                    'BINDINGS' => array(array('ENTITY_TYPE_ID' => \CCrmOwnerType::Lead, 'ENTITY_ID' => $arFields['ID']))
                ));
            $comment = '';
        }

        // Записываем данные компании
        if ($arFields['COMPANY_ID']) {
            $dataCompanyLast = Company::GetList([
                'ID' => 'ASC'
            ], [
                'CHECK_PERMISSIONS' => 'N',
                'ID' => $arFields['COMPANY_ID']
            ]);
            // Записываем бренд и тип
            $arFields[self::$leadTypeCode] = $dataCompanyLast[0]['COMPANY_TYPE'];
            $arFields[self::$leadBrendCode] = $dataCompanyLast[0][self::$companyBrendCode];

            $companyReqData = Company::GetRequisite($arFields['COMPANY_ID'])[0];
            if ($companyReqData) {
                if (!$arFields[self::$fieldInn] && $companyReqData['RQ_INN']) {
                    $arFields[self::$fieldInn] = $companyReqData['RQ_INN'];
                }

                if (!$arFields[self::$fieldKpp] && $companyReqData['RQ_KPP']) {
                    $arFields[self::$fieldKpp] = $companyReqData['RQ_KPP'];
                }

                $arFields[self::$leadPersonTypeCode] = self::$leadPersonTypeValue;
            }
        }

        if ($emailBody) {
            self::sendEmail($arFields['ID'], $emailBody);
        }
    }

    public static function onAfterCrmLeadAdd($arFields): void
    {
        $searchResult = [];
        $comment = '';
        $emailBody = '';

        if (!$arFields['ID']) {
            return;
        }

        

        // Получаем текущие контакты лида
        $leadContacts = \Bitrix\Crm\Binding\LeadContactTable::getList([
            'filter' => ['LEAD_ID' => $arFields['ID']]
        ])->fetchAll();
        foreach ($leadContacts as $leadContact) {
            $oldContacts[] = $leadContact['CONTACT_ID'];
        }
        if (isset($oldContacts) && 1 === count($oldContacts)) {
            $arFields['CONTACT_ID'] = $oldContacts[0];
        }

        // Приоритет сейчас у контактных данных, при успешном поиске выбранный руками контакт заменится

        // Если есть новые/изменённые контактные данные
        if (is_array($arFields['FM'])) {
            $copyFields = $arFields;
            $searchCriterion = self::getCriterionByFM($copyFields);
            $searchResult = self::searchByPhoneEmail($searchCriterion);

            if (is_array($searchResult['contacts']) && 1 === count($searchResult['contacts'])) {
                $arFields['CONTACT_ID'] = $searchResult['contacts'][0]['ID'];
            }

            if ($searchResult['comment']) {
                $comment .= $searchResult['comment'];
                if ($searchResult['sendEmail']) {
                    $emailBody .= $searchResult['comment'];
                }
                unset($searchResult['comment']);
            }
        }

        
        //Если указан ИНН это не физическое лицо
        if ( (isset($arFields[self::$fieldInn]) && $arFields[self::$fieldInn] > 0) /* || (isset($leadInfo[self::$fieldInn]) && $leadInfo[self::$fieldInn] > 0 ) */) {
            $arFields[self::$leadPersonTypeCode] = 673;

            // Если введён ИНН организации - зануляем адрес ИП и КПП
            if (isset($arFields[self::$fieldInn]) && 12 != strlen($arFields[self::$fieldInn])) {
                $arFields[self::$propertyUridAddress] = "0";

            } elseif (12 === strlen($arFields[self::$fieldInn]) && !$arFields[self::$propertyUridAddress]) {
                $arFields[self::$propertyUridAddress] = '0';
                $arFields[self::$fieldKpp] = "";
            }
        }
        else {
            //Иначе физическое лицо
            $arFields[self::$leadPersonTypeCode] = 672;
        }

        //Зафиксим данные компании (багфикс)
        self::$personType = $arFields[self::$leadPersonTypeCode];
        self::$inn = $arFields[self::$fieldInn];
        self::$kpp = $arFields[self::$fieldKpp];


        //Если физическое лицо установим адрес в ноль для БП при смене статуса
        if ( $arFields[self::$leadPersonTypeCode] == 672) {
            $arFields[self::$fieldKpp] = "0";
            $arFields[self::$fieldInn] = "0";
            $arFields[self::$propertyUridAddress] = "0";
        }

        // Поиск компании по реквизитам, если внесён ИНН
        if ($arFields[self::$fieldInn]) {

            $search = self::searchCompanyByRequisite($arFields);

            if ($search['companyByRequisite']) {
                $searchResult['companyByRequisite'] = $search['companyByRequisite'];
                $searchResult['companies'] = $search['companyByRequisite'];
            }

            if ($search['comment']) {
                $comment .= $search['comment'];
                if ($search['sendEmail']) {
                    $emailBody .= $search['comment'];
                }
            }
        }

        // Если привязали контакт
        if ($arFields['CONTACT_ID']) {
            if (isset($searchResult['companyByRequisite'])) {
                // Если по реквизитам найдена только одна компания - привязываем к лиду и привязываем контакт к компании
                if (1 === count($searchResult['companyByRequisite'])) {
                    $arFields['COMPANY_ID'] = $searchResult['companyByRequisite'][0];
                    // Привязываем контакт к найденной компании
                    \Bitrix\Crm\Binding\ContactCompanyTable::bindCompanyIDs($arFields['CONTACT_ID'], [$arFields['COMPANY_ID']]);
                    \Bitrix\Crm\Binding\LeadContactTable::bindContactIDs($arFields['ID'], [$arFields['CONTACT_ID']]);
                }
            } else {
                $searchResult['companies'] = Contact::getContactCompanyIDs($arFields['CONTACT_ID']);
                if (0 === count($searchResult['companies'])) {
                    $comment .= "\r\n" . 'Компании у контакта не найдены' . "\r\n";
                } else {
                    $companyComment = self::getCompanyComment($searchResult['companies'], 'По контакту');
                    $comment .= $companyComment['comment'];
                    if ($companyComment['sendEmail']) {
                        $emailBody .= $companyComment['comment'];
                    }
                }
                // Если найдена строго одна компания - привязываем к лиду
                if (1 === count($searchResult['companies'])) {
                    $arFields['COMPANY_ID'] = $searchResult['companies'][0];
                }
            }
            $arFields['SKIP_EVENT'] = true;
        }

        // Сущности по контактным данным
        $leads = [];
        $title = '';
        // Найдено по телефону и email
        if (isset($searchResult['byPhonesAndEmails']) && !empty($searchResult['byPhonesAndEmails']['LEAD'])) {
            $leads = $searchResult['byPhonesAndEmails']['LEAD'];
            $leads = self::getActiveLeads($leads, $arFields['ID']);
            $searchResult['leads'] = true;

            $title = 'Есть активные лиды по телефонам: ';
            foreach ($searchCriterion['phones'] as $item) {
                $title .= $item . '; ';
            }
            $title .= 'и email: ';
            foreach ($searchCriterion['emails'] as $item) {
                $title .= $item . '; ';
            }
            $title .= "\r\n";

            $comment .= self::getLeadComment($leads, $title);
        }

        // Найдено по телефону
        if (empty($leads) && isset($searchResult['byPhones']) && !empty($searchResult['byPhones']['LEAD'])) {
            $leads = $searchResult['byPhones']['LEAD'];
            $searchResult['leads'] = true;
            $leads = self::getActiveLeads($leads, $arFields['ID']);

            $title = 'Есть активные лиды по телефонам: ';
            foreach ($searchCriterion['phones'] as $item) {
                $title .= $item . '; ';
            }

            $comment .= self::getLeadComment($leads, $title);
        }

        // Найдено по email
        if (empty($leads) && isset($searchResult['byEmails']) && !empty($searchResult['byEmails']['LEAD'])) {
            $leads = $searchResult['byEmails']['LEAD'];
            $searchResult['leads'] = true;
            $leads = self::getActiveLeads($leads, $arFields['ID']);

            $title = 'Есть активные лиды по email: ';
            foreach ($searchCriterion['emails'] as $item) {
                $title .= $item . '; ';
            }

            $comment .= self::getLeadComment($leads, $title);
        }

        if ($searchResult['leads'] && 0 === count($leads)) {
            $comment .= 'По телефону и email лиды не найдены' . "\r\n";
        }

        if ($arFields['COMPANY_ID']) {
            // Выбираем все контакты компании
            $contactIds = \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($arFields['COMPANY_ID']);
            // Лиды контактов компании
            $comment .= self::getContactLeadComment(
                $contactIds,
                $arFields['ID'],
                'Есть активные лиды по контактам компании: '
            );
            // Сделки контактов компании
            $comment .= self::getContactDealComment($contactIds, 'Есть активные сделки по контактам компании: ');

        } elseif ($arFields['CONTACT_ID']) {
            // Лиды контакта
            $comment .= self::getContactLeadComment(
                [$arFields['CONTACT_ID']],
                $arFields['ID'],
                'Есть активные лиды по контакту: '
            );
            // Сделки контакта
            $comment .= self::getContactDealComment([$arFields['CONTACT_ID']], 'Есть активные сделки по контакту: ');
        }

        // Записываем комментарий по сущностям
        if ($comment) {
            \Bitrix\Crm\Timeline\CommentEntry::create(
                array(
                    'TEXT' => $comment,
                    'SETTINGS' => array(),
                    'AUTHOR_ID' => CRM_SETTINGS['deal']['systemUserId'],
                    'BINDINGS' => array(array('ENTITY_TYPE_ID' => \CCrmOwnerType::Lead, 'ENTITY_ID' => $arFields['ID']))
                ));
            $comment = '';
        }

        // Записываем данные компании
        if ($arFields['COMPANY_ID']) {
            $dataCompany = Company::GetList([], [
                'CHECK_PERMISSIONS' => 'N',
                'ID' => $arFields['COMPANY_ID']
            ]);

            if ($dataCompany[0]['COMPANY_TYPE']) {
                $arFields[self::$leadTypeCode] = $dataCompany[0]['COMPANY_TYPE'];
            }
            if ($dataCompany[0][self::$companyBrendCode]) {
                $arFields[self::$leadBrendCode] = $dataCompany[0][self::$companyBrendCode];
            }

            $companyReqData = Company::GetRequisite($arFields['COMPANY_ID'])[0];
            if ($companyReqData) {
                if (!$arFields[self::$fieldInn] && $companyReqData['RQ_INN']) {
                    $arFields[self::$fieldInn] = $companyReqData['RQ_INN'];
                }

                if (!$arFields[self::$fieldKpp] && $companyReqData['RQ_KPP']) {
                    $arFields[self::$fieldKpp] = $companyReqData['RQ_KPP'];
                }

                $arFields[self::$leadPersonTypeCode] = self::$leadPersonTypeValue;
            }
            $arFields['SKIP_EVENT'] = true;
        }

        if ($arFields['SKIP_EVENT']) {
            Lead::Update($arFields['ID'], $arFields);
        }

        if ($emailBody) {
            self::sendEmail($arFields['ID'], $emailBody);
        }

        //Правки для Jivo
        if ($arFields["CONTACT_ID"] > 0) {
            $arFieldsContactUpdate = [
                "UF_CRM_1723786599" => $arFields["ID"],
                "UF_CRM_1723786616" => $arFields["STATUS_ID"],
                "UF_CRM_1723786631" => $arFields["ASSIGNED_BY_ID"], 
            ];
            Contact::Update($arFields["CONTACT_ID"], $arFieldsContactUpdate);
        }
    }

    private static function getCriterionByFM(array &$arFields, bool $getPrevFM = false): array
    {
        $result = [
            'phones' => [],
            'emails' => []
        ];
        $searchPhoneEmail = false;

        if ($arFields['FM']['PHONE']) {
            foreach ($arFields['FM']['PHONE'] as &$phone) {
                $phone['VALUE'] = CrmSearch::formatPhone($phone['VALUE']);
                if ($phone['VALUE']) {
                    $searchPhoneEmail = true;
                    $result['phones'][] = $phone['VALUE'];
                }
            }
            unset($phone);
        }

        if ($arFields['FM']['EMAIL']) {
            foreach ($arFields['FM']['EMAIL'] as &$email) {
                $email['VALUE'] = trim($email['VALUE']);
                if ($email['VALUE']) {
                    $searchPhoneEmail = true;
                    $result['emails'][] = $email['VALUE'];
                }
            }
            unset($email);
        }

        if ($getPrevFM && $searchPhoneEmail && !$result['emails']) {
            $fieldsEmail = Fields::GetList([], [
                'ENTITY_ID' => 'LEAD',
                'ELEMENT_ID' => $arFields['ID'],
                'TYPE_ID' => 'EMAIL',
                'CHECK_PERMISSION' => 'N'
            ]);
            if ($fieldsEmail) {
                foreach ($fieldsEmail as $email) {
                    if (!in_array($email['VALUE'], $result['emails'])) {
                        $result['emails'][] = $email['VALUE'];
                    }
                }
            }
        }

        if ($getPrevFM && $searchPhoneEmail && !$result['phones']) {
            $fieldsPhone = Fields::GetList([], [
                'ENTITY_ID' => 'LEAD',
                'ELEMENT_ID' => $arFields['ID'],
                'TYPE_ID' => 'PHONE',
                'CHECK_PERMISSION' => 'N'
            ]);
            if ($fieldsPhone) {
                foreach ($fieldsPhone as $phone) {
                    if (!in_array($phone['VALUE'], $result['phones'])) {
                        $result['phones'][] = $phone['VALUE'];
                    }
                }
            }
        }

        return $result;
    }

    private static function getCompanyComment(array $companies, string $title, bool $companyCountError = false): array
    {
        $sendEmail = false;

        $dataCompany = Company::GetList([
            'ID' => 'ASC'
        ], [
            'CHECK_PERMISSIONS' => 'N',
            'ID' => $companies
        ]);

        if (1 < count($dataCompany)) {
            if ($companyCountError) {
                $title .= "\r\n" . '[B][COLOR=#ff0000]' . $title
                    . ' В CRM найдены дубли![/COLOR][/B]' . "\r\n";
                $sendEmail = true;
            } else {
                $title .= "\r\n" . '[B][COLOR=#ff0000]' . $title
                    . ' В CRM найдено несколько компаний![/COLOR][/B]' . "\r\n";
            }
        } else {
            $title .= "\r\n" . ' найдена компания:' . "\r\n";
        }
        $comment = "\r\n" . $title . "\r\n";
        $table = '[TABLE][TR][TD]№[/TD][TD]ID[/TD][TD]Название[/TD][TD]ИНН[/TD][TD]КПП[/TD][/TR]';
        $reqError = '';
        $i = 1;
        foreach ($dataCompany as $itemCompany) {
            $requisite = Company::GetRequisite($itemCompany['ID']);
            if (1 < count($requisite)) {
                $comment .= '[B][COLOR=#ff0000]Есть компании с несколькими реквизитами![/COLOR][/B]' . "\r\n";
                $sendEmail = true;
            }
            $itemCompany['INN'] = $itemCompany['KPP'] = [];
            foreach ($requisite as $item) {
                $itemCompany['INN'][] = $item['RQ_INN'];
                $itemCompany['KPP'][] = $item['RQ_KPP'];
            }
            $table .= '[TR][TD]' . $i++ . '[/TD][TD]<a href="/crm/company/details/' . $itemCompany['ID']
                . '/">' . $itemCompany['ID'] . '</a>[/TD][TD]'
                . $itemCompany['TITLE'] . '[/TD][TD]'
                . implode(', ', $itemCompany['INN']) . '[/TD][TD]'
                . implode(', ', $itemCompany['KPP']) . '[/TD][/TR]';
        }
        $comment .= $reqError . $table . '[/TABLE]' . "\r\n";

        return ['comment' => $comment, 'sendEmail' => $sendEmail];
    }

    private static function getLeadComment(array $leads, string $title): string
    {
        $comment = '';
        $arStatus = [];
        foreach ($leads as $lead) {
            $arStatus[$lead['STATUS_ID']] = $lead['STATUS_ID'];
        }
        $res = StatusTable::getList(
            [
                'select' => ['STATUS_ID', 'NAME'],
                'filter' => ['ENTITY_ID' => 'STATUS', 'STATUS_ID' => $arStatus]
            ]
        );
        while ($status = $res->fetch()) {
            $arStatus[$lead['STATUS_ID']] = $status['NAME'];
        }

        if (!empty($leads)) {
            $comment .= "\r\n" . $title . "\r\n";
            $comment .= '[TABLE][TR][TD]№[/TD][TD]ID[/TD][TD]Название[/TD][TD]Статус[/TD][TD]Ответственный[/TD][/TR]';
            $i = 1;
            foreach ($leads as $lead) {
                $comment .= '[TR][TD]' . $i++ . '[/TD][TD]<a href="/crm/lead/details/' . $lead['ID'] . '/">'
                    . $lead['ID'] . '</a>[/TD][TD]' . $lead['TITLE'] . '[/TD][TD]'
                    . $arStatus[$lead['STATUS_ID']] . '[/TD][TD]'
                    . $lead['ASSIGNED_BY_LAST_NAME'] . ' ' . $lead['ASSIGNED_BY_NAME'] . '[/TD][/TR]';
            }
            $comment .= '[/TABLE]' . "\r\n";
        }

        return $comment;
    }

    private static function getActiveLeads(array $leads, int $ID): array
    {
        // убираем текущий лид
        if (($key = array_search($ID, $leads)) !== false) {
            unset($leads[$key]);
        }
        if ($leads) {
            $leads = Lead::GetList(['ID' => 'DESC'], [
                'CHECK_PERMISSIONS' => 'N',
                'ID' => $leads,
                'STATUS_SEMANTIC_ID' => 'P'
            ], false, false, [
                'ID', 'TITLE', 'STATUS_ID',
                'ASSIGNED_BY_LAST_NAME',
                'ASSIGNED_BY_NAME'
            ]);
        }

        return $leads;
    }

    private static function getContactDealComment(array $contactIds, string $title): string
    {
        $dealIds = [];
        $comment = '';
        foreach ($contactIds as $contactId) {
            $searchDeal = \Bitrix\Crm\Binding\DealContactTable::getContactDealIDs($contactId);
            if ($searchDeal) {
                $dealIds = array_merge($dealIds, $searchDeal);
            }
        }

        if ($dealIds) {
            $arDeal = Deal::GetList([], [
                'CHECK_PERMISSIONS' => 'N',
                'ID' => $dealIds,
                'STAGE_SEMANTIC_ID' => 'P'
            ], false, false, [
                'ID', 'TITLE', 'STAGE_ID',
                'ASSIGNED_BY_LAST_NAME',
                'ASSIGNED_BY_NAME'
            ]);
            if ($arDeal) {
                $arStatus = [];
                foreach ($arDeal as $deal) {
                    $arStatus[$deal['STAGE_ID']] = $deal['STAGE_ID'];
                }
                $res = StatusTable::getList(
                    [
                        'select' => ['STATUS_ID', 'NAME'],
                        'filter' => ['ENTITY_ID' => 'DEAL_STAGE', 'STATUS_ID' => $arStatus]
                    ]
                );
                while ($status = $res->fetch()) {
                    $arStatus[$deal['STAGE_ID']] = $status['NAME'];
                }

                $comment .= "\r\n" . $title . "\r\n";
                $comment .= '[TABLE][TR][TD]№[/TD][TD]ID[/TD][TD]Название[/TD][TD]Статус[/TD][TD]Ответственный[/TD][/TR]';
                $i = 1;
                foreach ($arDeal as $deal) {
                    $comment .= '[TR][TD]' . $i++ . '[/TD][TD]<a href="/crm/deal/details/' . $deal['ID'] . '/">'
                        . $deal['ID'] . '</a>[/TD][TD]' . $deal['TITLE'] . '[/TD][TD]'
                        . $arStatus[$deal['STAGE_ID']] . '[/TD][TD]'
                        . $deal['ASSIGNED_BY_LAST_NAME'] . ' ' . $deal['ASSIGNED_BY_NAME'] . '[/TD][/TR]';
                }
                $comment .= '[/TABLE]' . "\r\n";
            }
        }

        return $comment;
    }

    private static function getContactLeadComment(array $contactIds, int $leadId, string $title): string
    {
        $leadIds = [];
        $comment = '';
        foreach ($contactIds as $contactId) {
            $searchLead = \Bitrix\Crm\Binding\LeadContactTable::getContactLeadIDs($contactId);
            if ($searchLead) {
                $leadIds = array_merge($leadIds, $searchLead);
            }
        }
        if ($leadIds) {
            // убираем текущий лид
            if (($key = array_search($leadId, $leadIds)) !== false) {
                unset($leadIds[$key]);
            }
        }
        if ($leadIds) {
            $arLeads = Lead::GetList([], [
                'CHECK_PERMISSIONS' => 'N',
                'ID' => $leadIds,
                'STATUS_SEMANTIC_ID' => 'P'
            ], false, false, [
                'ID', 'TITLE', 'STATUS_ID',
                'ASSIGNED_BY_LAST_NAME',
                'ASSIGNED_BY_NAME'
            ]);

            if ($arLeads) {
                $comment .= self::getLeadComment($arLeads, $title);
            }
        }

        return $comment;
    }

    /**
     * Поиск контактов по email и телефонам
     *
     * @param array $searchCriterion
     * @return array
     */
    private static function searchByPhoneEmail(array $searchCriterion): array
    {
        $contacts = [];
        $comment = '';
        $title = 'По контактным данным: ';

        $searchResult = CrmSearch::byPhonesAndEmails($searchCriterion['phones'], $searchCriterion['emails']);

        // Найдено по телефону и email
        if (isset($searchResult['byPhonesAndEmails']) && !empty($searchResult['byPhonesAndEmails']['CONTACT'])) {
            $contacts = $searchResult['byPhonesAndEmails']['CONTACT'];
            $title .= ' телефон ' . implode(', ', $searchCriterion['phones']);
            $title .= ' и email ' . implode(', ', $searchCriterion['emails']);
        }

        if (empty($contacts)) {
            // Найдено по телефону
            if (isset($searchResult['byPhones']) && !empty($searchResult['byPhones']['CONTACT'])) {
                $contacts = $searchResult['byPhones']['CONTACT'];
                $title .= ' телефон ' . implode(', ', $searchCriterion['phones']);
            }

            // Найдено по email
            if (isset($searchResult['byEmails']) && !empty($searchResult['byEmails']['CONTACT'])) {
                $contacts = array_merge($searchResult['byEmails']['CONTACT']);
                $title .= ' email ' . implode(', ', $searchCriterion['emails']);
            }
            $contacts = array_unique($contacts);
        }

        if (0 === count($contacts)) {
            if (!empty($searchCriterion['phones'])) {
                $title .= ' телефон ' . implode(', ', $searchCriterion['phones']);
            }
            if (!empty($searchCriterion['emails'])) {
                $title .= ' email ' . implode(', ', $searchCriterion['emails']);
            }
            $comment = "\r\n" . $title . ' контакты не найдены' . "\r\n";
        } else {

            $contacts = Contact::GetList(
                [],
                [
                    'CHECK_PERMISSIONS' => 'N',
                    'ID' => $contacts
                ],
                ['ID', 'FULL_NAME', 'NAME', 'LAST_NAME']
            );
            if (1 < count($contacts)) {
                $comment = "\r\n" . '[B][COLOR=#ff0000]' . $title . ' в CRM найдены дубли![/COLOR][/B]' . "\r\n";
                $searchResult['sendEmail'] = true;
            } else {
                $comment = "\r\n" . $title . ' найден контакт:' . "\r\n";
            }
            $comment .= '[TABLE][TR][TD]№[/TD][TD]ID[/TD][TD]Телефон[/TD][TD]E-mail[/TD][TD]Имя[/TD][/TR]';
            $i = 1;
            foreach ($contacts as $item) {
                $name = $item['FULL_NAME'] ?? trim($item['NAME'] . ' ' . $item['LAST_NAME']);

                $fields = Fields::GetList([], [
                    'ENTITY_ID' => 'CONTACT',
                    'ELEMENT_ID' => $item['ID'],
                    'CHECK_PERMISSION' => 'N'
                ]);

                $item['PHONES'] = $item['EMAILS'] = [];
                foreach ($fields as $field) {
                    if ($field['TYPE_ID'] == 'PHONE') {
                        $item['PHONES'][] = $field['VALUE'];
                    }
                    if ($field['TYPE_ID'] == 'EMAIL') {
                        $item['EMAILS'][] = $field['VALUE'];
                    }
                }
                $comment .= "[TR][TD]" . $i++ . '[/TD][TD]<a href="/crm/contact/details/' . $item['ID'] . '/">'
                    . $item['ID'] . "</a>[/TD][TD]" . implode(', ', $item['PHONES'])
                    . "[/TD][TD]" . implode(', ', $item['EMAILS']) . "[/TD][TD]"
                    . $name . "[/TD][/TR]";
            }
            $comment .= '[/TABLE]' . "\r\n";
        }

        if ($comment) {
            $searchResult['comment'] = $comment;
        }
        if ($contacts) {
            $searchResult['contacts'] = $contacts;
        }

        return $searchResult;
    }

    private static function searchCompanyByRequisite(array $arFields, ?array $leadInfo = null): array
    {
        $searchResult = [];
        $fieldInn = $arFields[self::$fieldInn] ?? '';
        $fieldKpp = $arFields[self::$fieldKpp] ?? '';

        if (!$leadInfo && !$arFields[self::$fieldInn]) {
            return $searchResult;
        }
        if ($leadInfo) {
            if (!$arFields[self::$fieldInn] && !$arFields[self::$fieldKpp]) {
                return $searchResult;
            }
            if (!$fieldInn && $leadInfo[self::$fieldInn]) {
                $fieldInn = $leadInfo[self::$fieldInn];
            }
            if (!$fieldKpp && $leadInfo[self::$fieldKpp]) {
                $fieldKpp = $leadInfo[self::$fieldKpp];
            }
        }
        $searchResult['comment'] = 'По реквизитам поиска: ИНН ' . $fieldInn
            . ($fieldKpp ? ' и КПП ' . $fieldKpp : '') . ' ';

        $companySearch = Company::FindByRequisite(trim($fieldInn), trim($fieldKpp));
        if (!$companySearch) {
            $searchResult['comment'] = "\r\n" . $searchResult['comment'] . ' компаний не найдено' . "\r\n";
        } else {
            $searchResult['companyByRequisite'] = $companySearch;
            $comment = self::getCompanyComment($companySearch, $searchResult['comment'], true);
            $searchResult['comment'] = $comment['comment'];
            $searchResult['sendEmail'] = $comment['sendEmail'];
        }

        return $searchResult;
    }

    private static function sendEmail(int $ID, string $emailBody): void
    {
        if (!self::$mailTo) {
            return;
        }
        $emailBody = str_replace(
            ["/r/n", '[', ']', '=#ff0000', 'COLOR'],
            ['<br/>', '<', '>', ' style="color:#ff0000;"', 'span'],
            $emailBody
        );
        $emailBody = 'Лид № <a href="/crm/lead/details/' . $ID . '/">' . $ID . '</a><br/>' . $emailBody;
        CEvent::SendImmediate("BIZPROC_HTML_MAIL_TEMPLATE", 's1', [
            'RECEIVER' => self::$mailTo,
            'SENDER' => 'nouser@highsystem.ru',
            'MESSAGE' => $emailBody,
            'REPLY_TO' => '',
            'TITLE' => 'Найдены дубли в лиде ' . $ID
        ], "N", "");
    }
}
