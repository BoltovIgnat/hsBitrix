<?php

namespace Dbbo\Chat;

use CEvent;
use \Exception;

class ChatFacade
{
    private ConnectorInterface $chatConnector;
    private CrmInterface $crmConnector;
    private int $logLevel = 0;
    private string $fileName;
    private string $url = CRM_SETTINGS['carrotChat']['url'];
    private array $response = [];
    private string $mailTo = 'a.kupets@highsystem.ru';

    public function __construct(ConnectorInterface $chatConnector, CrmInterface $crmConnector)
    {
        $this->fileName = __DIR__ . '/../logs/chat_main_' . date('Ymd') . '.log';
        $this->chatConnector = $chatConnector;
        $this->crmConnector = $crmConnector;
    }

    /**
     * Обработка входящего запроса от чата
     *
     * @return void
     */
    public function doAction(): void
    {
        $this->log(null, "\nstart");
        try {
            // Обрабатываем входящий запрос
            $this->response = $this->chatConnector->getData();
        } catch (\Exception $e) {
            // ошибка в данных, например, неверный токен
            $this->chatConnector->errorResponse($e);
            return;
        }
        $this->log($this->response, 'getData');

        if ($this->response['process']) {
            $this->crmConnector->setAssignedBy($this->chatConnector->getDefaultAssigned());
            $this->findDeal();
        }

        $this->log($this->response, 'BeforeEnd');

        // обрабатываем коннектором и возвращаем чату ответ
        $this->chatConnector->sendResponse($this->response);
    }

    /**
     * Запись обновлений диалогов Carrot Quest
     *
     * @param bool $onlyOpen
     * @param string|null $dialogId - Id диалога для обработки
     * @return void
     */
    public function getDialogs(bool $onlyOpen = true, ?string $dialogId = null): void
    {
        if (!$onlyOpen) {
            echo 'При обработке закрытых диалогов изменения данных пользователя иницируют повторные запросы и создание новых лидов!
                Добавьте проверку на непустой диалог в стандартный обработчик.';
            $this->log(null, "Обработка закрытых диалогов запрещена без доработки!");
            return;
        }
        set_time_limit(0);
        $this->log(null, "\nProcess dialog list");
        $filename = __DIR__ . '/../dialog.lock';
        if (file_exists($filename)) {
            $this->sendEmail();
            $this->log(null, "Locked, terminated");
            return;
        }
        file_put_contents($filename, '');
        try {
            $this->crmConnector->setAssignedBy($this->chatConnector->getDefaultAssigned());

            $dialogs = $this->chatConnector->getDialogs($onlyOpen);
            $dealField = $this->chatConnector->getDealField();
            $leadField = $this->chatConnector->getLeadField();

            foreach ($dialogs as $dialog) {
                // обработка только одного диалога
                if ($dialogId && $dialogId != $dialog['id']) {
                    continue;
                }

                try {
                    $this->log(null, 'Set Data');
                    $this->response = $this->chatConnector->setData([
                        'userId' => $dialog['user']['id'],
                        'user' => $dialog['user']
                    ]);
                } catch (\Exception $e) {
                    $this->log($e->getMessage(), 'Error set data', 2);
                }

                $this->log($dialog, "\nDialog {$dialog['id']} ");

                $deals = $this->crmConnector->findDeal([$dealField => $dialog['user']['id']], $onlyOpen);
                $leads = $this->crmConnector->findLead([$leadField => $dialog['user']['id']], $onlyOpen);

                if (!empty($leads)) {
                    foreach ($leads as $lead) {
                        $this->processLead($lead, false);
                        // обрабатываем коннектором и возвращаем чату ответ
                        $this->chatConnector->sendResponse($this->response);
                        $this->response = ['process' => true];
                    }
                }
                if (!empty($deals)) {
                    foreach ($deals as $deal) {
                        $this->processDeal($deal, false);
                        // обрабатываем коннектором и возвращаем чату ответ
                        $this->chatConnector->sendResponse($this->response);
                        $this->response = ['process' => true];
                    }
                }

                $lastMessage = $this->chatConnector->getLastMessage($dialog);
                // если последнее сообщение совпадает - переходим к следующему диалогу
                if (
                    $lastMessage
                    && $dialog['part_last']['created'] == ($lastMessage['UF_LAST_PART'])->getTimestamp()
                    && (string)$dialog['part_last']['id'] == $lastMessage['UF_PART_ID']
                ) {
                    $this->log(null, "No new messages");
                    continue;
                }

                if (empty($deals) && empty($leads)) {
                    // Нет сущностей, связанных с этим пользователем Carrot.
                    // Только если обрабатываем открытые диалоги, чтобы не создавать лиды для отработанных сущностей.
                    // Запускаем стандартную обработку

                    if ($this->response['process']) {
                        $this->log(null, 'Begin standard process');
                        $this->crmConnector->setAssignedBy($this->chatConnector->getDefaultAssigned());
                        $this->findDeal();
                    }

                    // обрабатываем коннектором и возвращаем чату ответ
                    $this->chatConnector->sendResponse($this->response);

                    // Переходим к следующему диалогу
                    continue;
                }

                $chat = $this->chatConnector->getChatMessages($dialog);
                if (empty($chat)) {
                    continue;
                }
                $updateChat = false;
                foreach ($leads as $lead) {
                    if ($this->crmConnector->updateLeadChat($lead, $chat)) {
                        $updateChat = true;
                    }
                }
                foreach ($deals as $deal) {
                    if ($this->crmConnector->updateDealChat($deal, $chat)) {
                        $updateChat = true;
                    }
                }
                if ($updateChat) {
                    $this->chatConnector->saveLastMessage($dialog);
                }
            }
            unlink($filename);
            $this->log(null, "\nEnd process dialog list");
        } catch (Exception $e) {
            unlink($filename);
            $this->log($e->getMessage(), "\nERROR, dialog list terminated");
        }
    }

    private function findDeal(): void
    {
        // ищем сделку
        $deals = $this->crmConnector->findDeal($this->chatConnector->getDealFilter());

        $this->log($deals, 'findDeals');
        $this->response['history'][] = 'Поиск сделок по номеру чата';
        foreach ($deals as $deal) {
            $this->response['history'][] = $deal['ID'] . ' ' . $deal['NAME'] . ' ' . $this->url
                . "/crm/deal/details/{$deal['ID']}/";
        }

        switch (count($deals)) {
            case 0:
                // Сделка не найдена
                $this->findLead();
                break;
            case 1:
                // Только одна активная
            default:
                // Несколько сделок - нет в блок-схеме, берём первую
                $this->processDeal($deals[0]);
                break;
        }

    }

    private function processDeal(array $deal, bool $getChat = true): void
    {
        $this->response['bindDeal']['url'] = $this->url . "/crm/deal/details/{$deal['ID']}/";
        $this->response['bindDeal']['Admin'] = $this->crmConnector->getUserNameByID($deal['ASSIGNED_BY_ID']);
        $this->response['history'][] = 'Обработка сделки ' . $deal['ID'] . ' ' . $deal['NAME'] . ' '
            . $this->url . "/crm/deal/details/{$deal['ID']}/";

        $this->getDealData($deal);

        if ($getChat) {
            $chat = $this->chatConnector->getChat();
            if (!empty($chat)) {
                $this->response['dealChatUpdated'] = $this->crmConnector->updateDealChat($deal, $chat);
                $this->response['history'][] = 'Обновлен диалог';
            }
        }
        $this->log($this->response, 'processDeal');
    }

    private function findLead(): void
    {
        // ищем лид
        $leads = $this->crmConnector->findLead($this->chatConnector->getLeadFilter());

        $this->log($leads, 'findLead');
        $this->response['history'][] = 'Поиск лидов по номеру чата';
        foreach ($leads as $lead) {
            $this->response['history'][] = $lead['ID'] . ' ' . $lead['NAME'] . ' '
                . $this->url . "/crm/lead/details/{$lead['ID']}/";
        }

        switch (count($leads)) {
            case 0:
                // Лид не найден
                $this->findContact();
                break;
            case 1:
                // Только один лид
            default:
                // Несколько лидов - нет в блок-схеме, берём первую
                $this->processLead($leads[0]);
                break;
        }
    }

    private function processLead(array $lead, bool $getChat = true ): void
    {
        $this->response['bindLead']['url'] = $this->url . "/crm/lead/details/{$lead['ID']}/";
        $this->response['bindLead']['Admin'] = $this->crmConnector->getUserNameByID($lead['ASSIGNED_BY_ID']);
        $this->response['history'][] = 'Обработка лида ' . $lead['ID'] . ' ' . $lead['NAME'] . ' '
            . $this->url . "/crm/lead/details/{$lead['ID']}/";

        $this->getLeadData($lead);

        if ($getChat) {
            $chat = $this->chatConnector->getChat();
            if (!empty($chat)) {
                $this->response['leadChatUpdated'] = $this->crmConnector->updateLeadChat($lead, $chat);
                $this->response['history'][] = 'Обновлен диалог';
            }
        }
        $this->log($this->response, 'processLead');
    }


    private function findLeadByContactData(array $contactData): void
    {
        $this->response['history'][] = 'Поиск лидов по контактным данным';
        if (!$this->checkContact($contactData)) {
            return;
        }
        // ищем лид
        $leads = $this->crmConnector->findLeadByContactData($contactData);
        $this->log($leads, 'findLeadByContactData');

        foreach ($leads as $lead) {
            $this->response['history'][] = $lead['ID'] . ' ' . $lead['NAME'] . ' '
                . $this->url . "/crm/lead/details/{$lead['ID']}/";
        }

        switch (count($leads)) {
            case 0:
                // Лид не найден
                $this->createLead();
                break;
            case 1:
                // Только один лид
            default:
                // Несколько лидов - берём первого
                $this->processLead($leads[0]);
                break;
        }
    }

    /**
     * Создание лида
     *
     * @param int|null $contactId
     * @param int|null $companyId
     * @return void
     */
    private function createLead(?int $contactId = null, ?int $companyId = null): void
    {
        $this->log(null, 'begin create lead');
        $chat = $this->chatConnector->getChat();
        $this->log($chat, 'lead chat');
        $leadData = $this->chatConnector->getCreateLeadData();
        $this->log($leadData, 'lead Data');

        $this->response['leadCreated'] = $this->crmConnector->createLead($leadData, $chat, $contactId, $companyId);
        $this->log($this->response['leadCreated'], 'response LeadCreated');

        if (isset($this->response['leadCreated']['url']) && $this->response['leadCreated']['url']) {
            $this->response['leadCreated']['url'] = $this->url . $this->response['leadCreated']['url'];
            if ($contactId && $this->response['leadCreated']['leadId']) {
                $this->getLeadData([
                    'ID' => $this->response['leadCreated']['leadId'],
                    'CONTACT_ID' => $contactId,
                    'COMPANY_ID' => $companyId
                ]);
            }
        }

        if ($this->response['leadCreated']['chatUpdated']) {
            $this->response['leadChatUpdated'] = $this->response['leadCreated']['chatUpdated'];
            $this->response['history'][] = 'Обновлен диалог';
            unset($this->response['leadCreated']['chatUpdated']);
        }

        $this->log($this->response, 'createLead');
    }

    /**
     * Ищем контакты по контактным данным (email и телефон)
     *
     * @return void
     */
    private function findContact(): void
    {
        $this->response['history'][] = 'Поиск контактов';
        $contactData = $this->chatConnector->getContactData();
        if (!$this->checkContact($contactData)) {
            // нет контактных данных, действий не надо
            $this->log($contactData, 'No contactData, return');
            return;
        }

        $this->log($contactData, 'contactData');

        // ищем контакты
        $contacts = $this->crmConnector->findContact($contactData);
        foreach ($contacts as $contact) {
            $this->response['history'][] = $contact['ID'] . ' ' . $contact['NAME'] . ' ' . $contact['LAST_NAME']
                . ' ' . $this->url . "/crm/contact/details/{$contact['ID']}/";
        }

        $this->log(array_map(function ($item) {
            return ['ID' => $item['ID']];
        }, $contacts), 'findContact');

        switch (count($contacts)) {
            case 0:
                // Контактов не найдено
                $this->findLeadByContactData($contactData);
                break;
            case 1:
            default:
                // Один или более - запускаем обработку
                $this->processContact($contacts[0]);
                break;
        }
    }

    private function processContact(array $contact): void
    {
        $this->response['history'][] = 'Обработка контакта ' . $contact['ID'] . ' ' . $contact['NAME'] . ' '
            . $contact['LAST_NAME'] . ' ' . $this->url . "/crm/contact/details/{$contact['ID']}/";

        $deals = $this->crmConnector->findDealByContact($contact['ID']);

        $this->log(array_map(function ($item) { return $item['ID']; }, $deals), 'processContact deals');
        $this->response['history'][] = 'Поиск сделок по контакту';
        foreach ($deals as $deal) {
            $this->response['history'][] = $deal['ID'] . ' ' . $deal['NAME'] . ' '
                . $this->url . "/crm/deal/details/{$deal['ID']}/";
        }

        if ($deals) {
            $this->response['bindDeal'] = $this->crmConnector
                ->bindDeal($deals[0], $this->chatConnector->getDealFilter());
            if (isset($this->response['bindDeal']['url']) && $this->response['bindDeal']['url']) {
                $this->response['bindDeal']['url'] = $this->url . $this->response['bindDeal']['url'];
            }
            $this->log($this->response, 'bindDeal response');

            $this->getDealData($deals[0]);

            $chat = $this->chatConnector->getChat();
            if ($chat['messages']) {
                $this->response['dealChatUpdated'] = $this->crmConnector->updateDealChat($deals[0], $chat);
                $this->response['history'][] = 'Обновлен диалог';
            }
            $this->log($this->response, 'processContact response');
            return;
        }

        $leads = $this->crmConnector->findLeadByContact($contact['ID']);

        $this->log(array_map(function ($item) { return $item['ID']; }, $leads), 'processContact leads IDs');
        $this->response['history'][] = 'Поиск лидов по контакту';
        foreach ($leads as $lead) {
            $this->response['history'][] = $lead['ID'] . ' ' . $lead['NAME'] . ' '
                . $this->url . "/crm/lead/details/{$lead['ID']}/";
        }

        if ($leads) {
            $response['bindLead'] = $this->crmConnector
                ->bindLead($leads[0], $this->chatConnector->getLeadFilter());
            if (isset($this->response['bindLead']['url']) && $this->response['bindLead']['url']) {
                $this->response['bindLead']['url'] = $this->url . $this->response['bindDeal']['url'];
            }
            $this->log($this->response, 'bindDeal response');

            $this->getLeadData($leads[0]);

            $chat = $this->chatConnector->getChat();
            if ($chat['messages']) {
                $response['leadChatUpdated'] = $this->crmConnector->updateLeadChat($leads[0], $chat);
                $this->response['history'][] = 'Обновлен диалог';
            }
            $this->log($response, 'processContact response');
            return;
        }

        $companies = $this->crmConnector->getContactCompanies($contact['ID']);
        $this->response['history'][] = 'Компании контакта';
        foreach ($companies as $company) {
            $this->response['history'][] = $company['ID'] . ' ' . $company['TITLE'] . ' '
                . $this->url . "/crm/company/details/{$company['ID']}/";
        }

        $this->createLead($contact['ID'], $companies[0] ? $companies[0]['ID'] : null);
    }

    /**
     * Проверка на наличие контактной информации в массиве
     *
     * @param array $contactData
     * @return bool
     */
    private function checkContact(array $contactData): bool
    {
        return $contactData['email'] || $contactData['phone'];
    }

    /**
     * Записывает в журнал
     *
     * @param null|mixed $data
     * @param string $action
     * @param int $level
     * @return void
     */
    private function log(mixed $data = null, string $action = '', int $level = 0): void
    {
        if ($level < $this->logLevel) {
            return;
        }
        if ($fp = fopen($this->fileName, 'a+')) {
            fwrite($fp, $action . date(' d-m-Y H:i:s') . "\n");
            if ($data) {
                fwrite($fp, print_r($data, true));
                fwrite($fp, date(str_repeat('-', 30) . "\n"));
            }
            fclose($fp);
        }
    }

    /**
     * Обновляет контактные данные.
     * Возвращает найденные данные контакта и компании
     *
     * @param array $deal
     * @return void
     */
    private function getDealData(array $deal): void
    {
        $contactData = $this->chatConnector->getContactData();
        $this->response['dealContactUpdated'] = $this->crmConnector->updateDealContact($deal, $contactData);

        if ($this->response['dealContactUpdated']['updated']) {
            $this->response['history'][] = 'Обновлены данные контакта';
        }

        if ($deal['COMPANY_ID']) {
            $this->log($deal['COMPANY_ID'], 'Get deal company');
            $this->response['companyData'] = $this->crmConnector->getCompanyData($deal['COMPANY_ID']);
        }
    }

    /**
     * Обновляет контактные данные.
     * Возвращает найденные данные контакта и компании
     *
     * @param array $lead
     * @return void
     */
    private function getLeadData(array $lead): void
    {
        $contactData = $this->chatConnector->getContactData();
        $this->response['leadContactUpdated'] = $this->crmConnector->updateLeadContact($lead, $contactData);

        if ($this->response['leadContactUpdated']['updated']) {
            $this->response['history'][] = 'Обновлены контактные данные';
        }
        if ($lead['COMPANY_ID']) {
            $this->log($lead['COMPANY_ID'], 'Get lead company');
            $this->response['companyData'] = $this->crmConnector->getCompanyData($lead['COMPANY_ID']);
        }
    }

    /**
     * Отправка письма о том, что .lock файл обнаружен
     *
     * @return void
     */
    private function sendEmail(): void
    {
        if (!$this->mailTo) {
            return;
        }
        $emailBody = 'Скрипт получения диалогов Carrot Quest не запущен, т.к. не завершено предыдущее выполнение '
            . '(есть файл /local/api/dbbo.chats/dialog.lock)';
        CEvent::SendImmediate("BIZPROC_HTML_MAIL_TEMPLATE", 's1', [
            'RECEIVER' => $this->mailTo,
            'SENDER' => 'nouser@highsystem.ru',
            'MESSAGE' => $emailBody,
            'REPLY_TO' => '',
            'TITLE' => 'Carrot Quest - ОШИБКА СКРИПТА!'
        ], "N", "");
    }
}