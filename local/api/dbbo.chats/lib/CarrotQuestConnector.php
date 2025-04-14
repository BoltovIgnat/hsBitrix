<?php

namespace Dbbo\Chat;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Main\Application;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Exception;

class CarrotQuestConnector implements ConnectorInterface
{
    private string $token;
    private string $authToken;
    private string $webApiUrl;
    private string $appId;
    private string $fileName;
    private mixed $hlClass;
    private array $data = [];
    private array $chat = [];
    private int $logLevel = 0;

    private string $opLead = 'Ссылка на лид';
    private string $opDeal = 'Ссылка на сделку';
    private string $opCompany = 'Компания';


    /**
     * @throws LoaderException
     * @throws Exception
     */
    public function __construct()
    {
        Loader::includeModule('highloadblock');
        $this->getOptions();
        $this->data['crmFields'] = $this->getCrmCodes();
    }

    /**
     * Обработка входящих данных CarrotQuest
     *
     * @return array{process: boolean} $response
     * @throws Exception
     */
    public function getData(): array
    {
        $this->data['carrot'] = $this->getCarrotDataFromPost();
        $this->log($this->data, 'Get Data');

        if (!$this->checkNeedProcess()) {
            return ['process' => false];
        }

        return ['process' => true];
    }

    /**
     * Обработка входящих данных CarrotQuest
     *
     * @return array{process: boolean} $response
     */
    public function setData(array $data): array
    {
        $this->data['carrot'] = [
            'carrotId' => $data['userId'],
            'carrotUser' => $data['user']
        ];
        if ($data['userId']) {
            $params = [
                'props' => true,
                'props_custom' => true
            ];
            $carrotUser = $this->webApi('GET', "users/{$data['userId']}", $params);
            if ($carrotUser['data']) {
                $this->data['carrot']['carrotUser'] = $carrotUser['data'];
            }
        }

        $this->log($this->data, 'Set Data');

        if (!$this->checkNeedProcess()) {
            return ['process' => false];
        }

        return ['process' => true];
    }

    /**
     * Ответ Carrot Quest при ошибке
     *
     * @param Exception $e
     * @return void
     */
    public function errorResponse(Exception $e): void
    {
        $this->log(null, $e->getMessage(), 2);
        // Возвращаем 200 во избежание повторных запросов
        $this->responseOk();
    }

    /**
     * Ответ Carrot Quest об успехе
     * Возможны необходимые обработки по ключам $response [
     *      'dealContactUpdated' array обновлены данные контакта сделки
     *      'leadContactUpdated' array обновлены данные контакта лида
     *          ['updated' - факт обновления
     *           'oldEmail' - переданный email
     *           'oldPhone' - переданный телефон
     *           'email' - массив email контакта
     *           'phone' - массив номеров контакта ]
     *      'companyData' - [name, inn] данные о компании
     *      'dealChatUpdated' bool в сделку записан диалог
     *      'leadChatUpdated' bool в лид записан диалог
     *      'bindDeal' ?array сделка найдена и связана с ID CQ
     *      'bindLead' ?array лид найден и связан с ID CQ
     *      'leadCreated' ?array создан новый лид
     *          null вместо массива - операция не удалась, в массиве данные о сущности
     * ]
     *
     * @param array $response
     * @return void
     */
    public function sendResponse(array $response): void
    {
        $this->log($response, 'sendResponse');
        $options = [];
        $id = $this->data['carrot']['carrotId'];

        if (($response['dealChatUpdated'] || $response['leadChatUpdated']) && $this->chat['dialog']) {
            $this->saveLastMessage($this->chat['dialog']);
        }

        if ($response['bindDeal']) {
            if (
                !isset($this->data['carrot']['carrotUser']['props_custom'])
                || !isset($this->data['carrot']['carrotUser']['props_custom'][$this->opDeal])
                || $response['bindDeal']['url'] !== $this->data['carrot']['carrotUser']['props_custom'][$this->opDeal]
            ) {
                if (is_array($response['history'])) {
                    foreach ($response['history'] as $key => $item) {
                        $response['bindDeal']['История ' . $key] = $item;
                    }
                }
                $params = [
                    'event' => 'Битрикс24: Привязана сделка ',
                    'params' => json_encode($response['bindDeal'])
                ];
                $this->webApi('POST', "users/{$id}/events", $params);

                $options = [
                    json_encode(['op' => 'update_or_create', 'key' => $this->opDeal, 'value' => $response['bindDeal']['url']]),
                    json_encode(['op' => 'delete', 'key' => $this->opLead, 'value' => ''])
                ];
            }
        }

        if ($response['bindLead']) {
            if (
                !isset($this->data['carrot']['carrotUser']['props_custom'])
                || !isset($this->data['carrot']['carrotUser']['props_custom'][$this->opLead])
                || $response['bindLead']['url'] !== $this->data['carrot']['carrotUser']['props_custom'][$this->opLead]
            ) {
                if (is_array($response['history'])) {
                    foreach ($response['history'] as $key => $item) {
                        $response['bindLead']['История ' . $key] = $item;
                    }
                }
                $params = [
                    'event' => 'Битрикс24: Привязан лид ',
                    'params' => json_encode($response['bindLead'])
                ];
                $this->webApi('POST', "users/{$id}/events", $params);

                $options = [
                    json_encode(['op' => 'update_or_create', 'key' => $this->opLead, 'value' => $response['bindLead']['url']]),
                    json_encode(['op' => 'delete', 'key' => $this->opDeal, 'value' => ''])
                ];
            }
        }

        if ($response['leadCreated']) {
            if (
                !isset($this->data['carrot']['carrotUser']['props_custom'])
                || !isset($this->data['carrot']['carrotUser']['props_custom'][$this->opLead])
                || $response['leadCreated']['url'] !== $this->data['carrot']['carrotUser']['props_custom'][$this->opLead]
            ) {
                if (is_array($response['history'])) {
                    foreach ($response['history'] as $key => $item) {
                        $response['leadCreated']['История ' . $key] = $item;
                    }
                }
                $params = [
                    'event' => 'Битрикс24: Создан лид ',
                    'params' => json_encode($response['leadCreated'])
                ];
                $this->webApi('POST', "users/{$id}/events", $params);

                $options = [
                    json_encode(['op' => 'update_or_create', 'key' => $this->opLead, 'value' => $response['leadCreated']['url']]),
                    json_encode(['op' => 'delete', 'key' => $this->opDeal, 'value' => ''])
                ];
            }
        }

        $contact = false;
        if ($response['dealContactUpdated']) {
            $contact = $response['dealContactUpdated'];
        }
        if ($response['leadContactUpdated']) {
            $contact = $response['leadContactUpdated'];
        }
        if ($contact) {
            if (!$contact['oldPhone'] && $contact['phone'][0]) {
                $options[] = json_encode(['op' => 'update_or_create', 'key' => '$phone', 'value' => $contact['phone'][0]]);

            }
            if (!$contact['oldEmail'] && $contact['email'][0]) {
                $options[] = json_encode(['op' => 'update_or_create', 'key' => '$email', 'value' => $contact['email'][0]]);
            }
        }
        if (!empty($response['companyData'])) {
            $company = $response['companyData']['name'] . '  ' . $response['companyData']['inn'];
            if (
                !isset($this->data['carrot']['carrotUser']['props_custom'])
                || !isset($this->data['carrot']['carrotUser']['props_custom'][$this->opCompany])
                || $company !== $this->data['carrot']['carrotUser']['props_custom'][$this->opCompany]
            ) {
                $options[] = json_encode([
                    'op' => 'update_or_create',
                    'key' => $this->opCompany,
                    'value' => $company
                ]);
            }
        }

        if (!empty($options)) {
            $this->log($options, 'Change user props');
            $options = '[' . implode(',', $options) . ']';
            $this->webApi('POST', "users/{$id}/props", ['operations' => $options]);
        }

        $this->responseOk();
    }

    /**
     * Фильтр для выборки сделок клиента
     *
     * @return array
     */
    public function getDealFilter(): array
    {
        return [
            $this->data['crmFields']['dealFieldChatId'] => $this->data['carrot']['carrotId']
        ];
    }

    /**
     * Фильтр для выборки лидов клиента
     *
     * @return array
     */
    public function getLeadFilter(): array
    {
        return [
            $this->data['crmFields']['leadFieldChatId'] => $this->data['carrot']['carrotId']
        ];
    }

    /**
     * @return array
     */
    public function getCreateLeadData(): array
    {
        $fields = array_merge($this->getLeadFilter(), [
            $this->data['crmFields']['leadFieldUrl'] => $this->data['carrot']['carrotUser']['props']['$initial_referrer']
        ]);
        if ($this->data['carrot']['carrotUser']['props_custom']['yandex ID']) {
            $fields[$this->data['crmFields']['yandexCID']] = $this->data['carrot']['carrotUser']['props_custom']['yandex ID'];
        }
        if ($this->data['carrot']['carrotUser']['props']['$last_utm_source']) {
            $fields['UTM_SOURCE'] = $this->data['carrot']['carrotUser']['props']['$last_utm_source'];
        }
        if ($this->data['carrot']['carrotUser']['props']['$last_utm_medium']) {
            $fields['UTM_MEDIUM'] = $this->data['carrot']['carrotUser']['props']['$last_utm_medium'];
        }
        if ($this->data['carrot']['carrotUser']['props']['$last_utm_campaign']) {
            $fields['UTM_CAMPAIGN'] = $this->data['carrot']['carrotUser']['props']['$last_utm_campaign'];
        }
        if ($this->data['carrot']['carrotUser']['props']['$last_utm_content']) {
            $fields['UTM_CONTENT'] = $this->data['carrot']['carrotUser']['props']['$last_utm_content'];
        }
        if ($this->data['carrot']['carrotUser']['props']['$last_utm_term']) {
            $fields['UTM_TERM'] = $this->data['carrot']['carrotUser']['props']['$last_utm_term'];
        }
        $this->log($fields, 'Create lead data');

        return [
            'contact' => $this->getContactData(),
            'leadFilter' => $fields,
            'sourceId' => $this->data['crmFields']['sourceId'],
            'chatId' => $this->data['carrot']['carrotId'],
            'geoIp' => [
                'country' => $this->data['carrot']['carrotUser']['props']['$country'],
                'region' => $this->data['carrot']['carrotUser']['props']['$region'],
                'city' => $this->data['carrot']['carrotUser']['props']['$city']
            ]
        ];
    }

    /**
     * Возвращает контактные данные пользователя чата
     *
     * @return array{email: string, phone: string, name: string}
     */
    public function getContactData(): array
    {
        $contactData = [];
        if (
            $this->data['carrot']['carrotUser']['props']['$email']
            && check_email($this->data['carrot']['carrotUser']['props']['$email'])
        ) {
            $contactData['email'] = $this->data['carrot']['carrotUser']['props']['$email'];
        }
        if ($this->data['carrot']['carrotUser']['props']['$phone']) {
            $contactData['phone'] = $this->data['carrot']['carrotUser']['props']['$phone'];
        }
        if ($this->data['carrot']['carrotUser']['props']['$name']) {
            $contactData['name'] = $this->data['carrot']['carrotUser']['props']['$name'];
        }

        return $contactData;
    }

    /**
     * Возвращает сообщения диалога
     *
     * @param int|null $carrotId
     * @return array
     */
    public function getChat(?int $carrotId = null): array
    {
        $this->chat = [];
        $carrotId = $carrotId ?? ($this->data['carrot']['carrotId'] ?? null);
        $this->log($carrotId, 'getChat CarrotId');
        if (!$carrotId) {
            return $this->chat;
        }
        $response = $this->webApi('GET', "users/{$carrotId}/conversations");

        $dialog = $response['data'][0] ?? null;
        if (!$dialog) {
            return $this->chat;
        }
        $this->chat = $this->getChatMessages($dialog);

        return $this->chat;
    }

    /**
     * Список сообщений, отсортирован по убыванию времени
     * записывается в $this->chat для постобработки
     *
     * @param array $dialog
     * @return array
     */
    public function getChatMessages(array $dialog): array
    {
        $this->chat = [];
        $this->log($dialog, 'getMesages');
        $this->chat['assignedEmail'] = $dialog['assignee']['name_internal'] ?? null;
        $params = [
            'count' => 50,
        ];

        $response = $this->webApi('GET', "conversations/{$dialog['id']}/parts", $params);
        $messages = $response['data'];
        while ($response['meta']['next_after']) {
            $params['after'] = (string)$response['meta']['next_after'];
            $response = $this->webApi('GET', "conversations/{$dialog['id']}/parts", $params);
            $messages = array_merge($messages, $response['data']);
        }
        $userName = $dialog['user']['props']['$name']
            ?? ($dialog['user']['props']['$phone']
                ?? ($dialog['user']['props']['$email'] ?? $dialog['user']['id']));
        $lastMessage = $this->getLastMessage($dialog);
        $this->log($lastMessage, 'getLastMessage');
        foreach ($messages as $message) {
            if ($lastMessage && $lastMessage['UF_PART_ID'] == $message['id']) {
                // Дошли до записанного сообщения
                break;
            }
            $key = date('Ymd', (int)$message['created']);
            if ('a2u' == $message['direction']) {
                $name = '> ' . $message['from']['name'] ?? ($this->chat['assignedEmail'] ?? 'Admin');
            } else {
                $name = '< ' . $userName;
            }
            if ($message['body']) {
                $this->chat['messages'][$key][] = $name . date(' d.m.Y H:i:s : ', $message['created']) . $message['body'];
            }
            if ($message['attachments']) {
                foreach ($message['attachments'] as $attach) {
                    if ('file' == $attach['type']) {
                        $this->chat['messages'][$key][] = $name . date(' d.m.Y H:i:s : ', $message['created'])
                            . ' Файл <a href="' . $attach['url'] . '" target="_blank">' . $attach['filename'] . '</a>';
                    }
                }
            }
        }
        if (is_array($this->chat['messages'])) {
            ksort($this->chat['messages']);
        }
        $this->chat['dialog'] = $dialog;
        $this->log($this->chat, 'chat');

        return $this->chat;
    }

    /**
     * Возвращает ответственного по умолчанию
     *
     * @return int
     */
    public function getDefaultAssigned(): int
    {
        return $this->data['crmFields']['assignedDefault'];
    }

    /**
     * Возвращает список всех диалогов Carrot Quest
     *
     * @see https://developers.carrotquest.io/endpoints/apps/conversations/
     * @param bool $onlyOpen - обрабатывать только открытые диалоги
     * @return array
     */
    public function getDialogs(bool $onlyOpen = true): array
    {
        $this->log(null, 'getDialogs');
        $params = [
//            'closed' => false, // true - только закрытые, false - только открытые, не указано - все
//            'include_not_assigned' => false, // Включать диалоги, не назначенные никому
            'count' => 50 // Количество записей, 10-50
        ];
        if ($onlyOpen) {
            $params['closed'] = false;
        }

        $response = $this->webApi('GET', "apps/{$this->appId}/conversations", $params);
        $dialogs = $response['data'];
        while ($response['meta']['next_after']) {
            $this->log($response['meta']['next_after'], 'next_after');
            $params['after'] = (string)$response['meta']['next_after'];
            $response = $this->webApi('GET', "apps/{$this->appId}/conversations", $params);
            $dialogs = array_merge($dialogs, $response['data']);
        }
        $this->log($dialogs, 'dialogs');

        return $dialogs;
    }

    /**
     * Поле для выборки сделок
     *
     * @return string
     */
    public function getDealField(): string
    {
        return $this->data['crmFields']['dealFieldChatId'];
    }

    /**
     * Поле для выборки лидов
     *
     * @return string
     */
    public function getLeadField(): string
    {
        return $this->data['crmFields']['leadFieldChatId'];
    }

    /**
     * Возвращает настройки Carrot Quest для CRM
     *
     * @return string[]
     */
    private function getCrmCodes(): array
    {
        return [
            // Код источника
            'sourceId' => CRM_SETTINGS['carrotChat']['sourceId'],
            // Ответственный по умолчанию
            'assignedDefault' => CRM_SETTINGS['carrotChat']['assignedDefault'],
            // Свойство lead для записи id пользователя в Carrot
            'leadFieldChatId' => CRM_SETTINGS['carrotChat']['leadFieldChatId'],
            // Свойство deal для записи id пользователя в Carrot
            'dealFieldChatId' => CRM_SETTINGS['carrotChat']['dealFieldChatId'],
            // Начальная страница
            'leadFieldUrl' => CRM_SETTINGS['carrotChat']['leadFieldUrl'],
            // YAD_CID
            'yandexCID' => CRM_SETTINGS['carrotChat']['leadYacid']
        ];
    }

    /**
     * Положительный ответ, данные не обрабатываются на стороне CarrotQuest
     *
     * @return void
     */
    private function responseOk(): void
    {
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }

    /**
     * Настройки Carrot Quest
     *
     * @return void
     * @throws Exception
     */
    private function getOptions(): void
    {
        $this->token = CRM_SETTINGS['carrotChat']['token'];
        $this->appId = CRM_SETTINGS['carrotChat']['apiId'];
        $this->authToken = CRM_SETTINGS['carrotChat']['authToken'];
        $this->webApiUrl = CRM_SETTINGS['carrotChat']['webApiUrl'];
        $this->fileName = __DIR__ . '/../logs/carrot_quest_' . date('Ymd') . '.log';
        $this->hlClass = (HLBT::compileEntity('CarrotQuestDialogs'))->getDataClass();
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
     * Запрос к webApi carrot quest
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @return array
     */
    private function webApi(string $method, string $url, array $params = []): array
    {
        // для ограничения количества запросов к Carrot в минуту
        $time = file_get_contents(__DIR__ . '/carrotApi');
        while ($time == time()) {
            sleep(1);
            $time = file_get_contents(__DIR__ . '/carrotApi');
        }
        file_put_contents(__DIR__ . '/carrotApi', time());

        $url = $this->webApiUrl . $url;
        $params['auth_token'] = $this->authToken;

        $curlOpt = [
            CURLOPT_HTTPGET => ('GET' == $method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_TIMEOUT => 60
        ];

        if ('POST' == $method) {
            $curlOpt[CURLOPT_POST] = true;
            $curlOpt[CURLOPT_POSTFIELDS] = http_build_query($params);
        } else {
            $curlOpt[CURLOPT_URL] = $url . '?' . http_build_query($params);
        }
        $this->log($curlOpt[CURLOPT_URL], 'curl url');


        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOpt);

        $result = curl_exec($curl);
        if (!$result) {
            $this->log($curlOpt, 'curl Error ' . curl_error($curl));
        }
        curl_close($curl);
        $result = json_decode($result, true);
        if (200 != $result['meta']['status']) {
            $this->log($result, 'carrot error');
        }

        return $result ?? [];
    }

    /**
     * Получение данных запроса
     *
     * @return array
     * @throws Exception
     */
    private function getCarrotDataFromPost(): array
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $this->log($_POST, 'Incoming request');

        if ($this->token !== $request->getPost('token')) {
            $this->log(null, 'Wrong token error');
            throw new Exception('Wrong token error');
        }

        return [
            'type' => $request->getPost('type'),
            'eventName' => $request->getPost('event_name'),
            'carrotId' => $request->getPost('user_id'),
            'carrotUser' => json_decode($request->getPost('user'), true),
            'carrotEvent' => json_decode($request->getPost('event'), true)
        ];
    }

    /**
     * Проверяет, нужна ли обработка
     *
     * @return bool
     */
    private function checkNeedProcess(): bool
    {
        // Без кода пользователя CQ дальнейшая обработка не нужна
        return (!!$this->data['carrot']['carrotId']);
    }

    /**
     * Последняя запись о диалоге пользователяв Б24
     *
     * @param array $dialog
     * @return array|null
     */
    public function getLastMessage(array $dialog): ?array
    {
        $rs = $this->hlClass::getList([
            'filter' => [
                'UF_CARROT_ID' => $dialog['user']['id'],
                'UF_DIALOG_ID' => $dialog['id']
            ]
        ]);

        // Ожидается одно совпадение
        if ($row = $rs->fetch()) {
            return $row;
        }

        return null;
    }

    /**
     * Фиксируем последнюю запись диалога
     *
     * @param array $dialog
     * @return void
     */
    public function saveLastMessage(array $dialog): void
    {
        $fields = [
            'UF_CARROT_ID' => $dialog['user']['id'],
            'UF_DIALOG_ID' => $dialog['id'],
            'UF_LAST_PART' => DateTime::createFromTimestamp($dialog['part_last']['created']),
            'UF_PART_ID' => $dialog['part_last']['id']
        ];
        try {
            if ($lastMessage = $this->getLastMessage($dialog)) {
                $this->hlClass::update($lastMessage['ID'], $fields);
            } else {
                $this->hlClass::add($fields);
            }
        } catch (Exception $e) {
            $this->log($e->getMessage(), 'Ошибка записи в хайлоад', 2);
        }
    }
}