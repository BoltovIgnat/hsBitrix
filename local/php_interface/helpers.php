<?php

/**
 * Запись данных в лог-файл.
 *
 * @param string $logName Имя лог-файла.
 * @param mixed  $data    Данные для записи в лог (строка, массив, объект и т.д.).
 */
function writeLog(string $logName, string $message, $data = null): void
{
    // Указываем путь к директории логов
    $logDir = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/';

    // Имя файла с учётом текущей даты
    $fileName = $logDir . $logName . '_' . date('Y-m-d') . '.txt';

    // Форматируем сообщение
    $formattedData = "[" . date('Y-m-d H:i:s') . "] " . $message;

    // Если переданы данные, добавляем их в лог
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $formattedData .= ' | ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            $formattedData .= ' | ' . (string)$data;
        }
    }

    $formattedData .= PHP_EOL;

    // Записываем данные в лог-файл
    file_put_contents($fileName, $formattedData, FILE_APPEND | LOCK_EX);
}


/**
 * Проверяет, состоит ли пользователь в одной из указанных групп.
 *
 * @param string $login Логин пользователя.
 * @param array $groupIds Массив ID групп.
 * @return bool true, если пользователь состоит хотя бы в одной из групп.
 */
function isUserInGroups(string $login, array $groupIds): bool
{
    if (!$login || empty($groupIds)) {
        return false;
    }

    $filter = ['LOGIN' => $login];
    $user = \CUser::GetList($by = 'id', $order = 'asc', $filter, ['FIELDS' => ['ID']])->Fetch();

    if (!$user) {
        return false;
    }

    $userGroups = \CUser::GetUserGroup($user['ID']);
    return !empty(array_intersect($groupIds, $userGroups));
}

/**
 * Возвращает IP-адрес клиента.
 *
 * @return string IP-адрес.
 */
function getClientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}