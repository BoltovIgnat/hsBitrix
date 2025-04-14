<?php

namespace Dbbo\Chat;

use Exception;

interface ConnectorInterface
{
    /**
     * Обработка входящих данных чата
     *  $response[process] нужна ли дальнейшая обработка
     *
     * @return array{process: boolean} $response
     */
    public function getData(): array;

    /**
     *  Ответ чату в случае ошибки
     *
     * @param Exception $e
     * @return void
     */
    public function errorResponse(Exception $e): void;

    /**
     * Ответ чату при успехе
     * Возможны необходимые обработки по ключам $response [
     *      'dealContactUpdated' bool обновлены данные контакта сделки
     *      'dealChatUpdated' bool в сделку записан диалог
     *      'leadContactUpdated' bool обновлены данные контакта лида
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
    public function sendResponse(array $response): void;

    /**
     * Фильтр для выборки сделок клиента
     *
     * @return array
     */
    public function getDealFilter(): array;

    /**
     * Фильтр для выборки лидов клиента
     *
     * @return array
     */
    public function getLeadFilter(): array;

    public function getCreateLeadData(): array;

    /**
     * Массив контактных данных из чата
     *
     * @return array{email: string, phone:string, name: string}
     */
    public function getContactData(): array;

    public function getChat(?int $carrotId = null): array;

    public function getDefaultAssigned(): int;

    /**
     * Возвращает список всех диалогов
     *
     * @return array
     */
    public function getDialogs(): array;

    public function getLeadField(): string;

    public function getDealField(): string;

}