<?php
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;
use Bitrix\Crm\LeadTable;

// Подключаем окружение Bitrix
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

header('Content-Type: application/json');

// Функция для записи логов
//function writeLog($message) {
//    $logFile = __DIR__ . '/dashboard_log.txt';
//    $timestamp = date("Y-m-d H:i:s");

//    if (is_array($message)) {
//        $formattedMessage = "[{$timestamp}] Массив:\n";
//        foreach ($message as $key => $value) {
//            $formattedMessage .= "  {$key}: " . print_r($value, true) . PHP_EOL;
//        }
//    } else {
//        $formattedMessage = "[{$timestamp}] {$message}" . PHP_EOL;
//    }
//
//    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
//}

// Функция для получения пользователей группы с ID отдела
function getUsersByGroup($groupId) {
    $userList = [];
    $res = \CUser::GetList(
        $by = 'id',
        $order = 'asc',
        ['GROUPS_ID' => [$groupId]],
        ['SELECT' => ['ID', 'NAME', 'LAST_NAME', 'UF_DEPARTMENT']]
    );

    while ($user = $res->Fetch()) {
        $userList[$user['ID']] = [
            'FULL_NAME' => trim($user['NAME'] . ' ' . $user['LAST_NAME']),
            'DEPARTMENT_ID' => $user['UF_DEPARTMENT'][0] ?? null,
        ];
    }

    return $userList;
}

// Функция для получения списка стадий с SEMANTICS
function getStatusListWithSemantics() {
    $statusList = [];
    $res = \CCrmStatus::GetStatus('STATUS');

    foreach ($res as $statusId => $status) {
        $statusList[$statusId] = [
            'NAME' => $status['NAME'],
            'SEMANTICS' => $status['SEMANTICS'] ?? '', // Добавляем SEMANTICS
        ];
    }

    return $statusList;
}

// Функция для получения списка источников
function getSourceList() {
    return \CCrmStatus::GetStatusList('SOURCE');
}

// Функция для получения списка отделов
function getDepartments($iblockId) {
    $departments = [];
    $res = \CIBlockSection::GetList(
        ['NAME' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'],
        false,
        ['ID', 'NAME']
    );

    while ($department = $res->Fetch()) {
        $departments[$department['ID']] = $department['NAME'];
    }

    return $departments;
}

try {
    if (!Loader::includeModule('main') || !Loader::includeModule('crm') || !Loader::includeModule('iblock')) {
        throw new \Exception("Не удалось подключить необходимые модули");
    }

	//    writeLog("AJAX вызов");

    // Чтение тела запроса
    $requestBody = file_get_contents('php://input');
    $requestData = json_decode($requestBody, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception("Некорректный JSON: " . json_last_error_msg());
    }

    $dateFrom = $requestData['start-date'] ?? null;
    $dateTo = $requestData['end-date'] ?? null;
    $type = $requestData['type'] ?? 'details';

    if (!$dateFrom || !$dateTo) {
        throw new \Exception("Не указаны даты фильтра");
    }

    $dateFromFormatted = DateTime::createFromPhp(new \DateTime($dateFrom . ' 00:00:00'));
    $dateToFormatted = DateTime::createFromPhp(new \DateTime($dateTo . ' 23:59:59'));

    $userList = getUsersByGroup(11); // Группа 11
    $statusList = getStatusListWithSemantics(); // Получаем статусы с SEMANTICS
    $sourceList = getSourceList();
    $departments = getDepartments(1); // IBLOCK_ID = 1

	//    writeLog("Список Статусов: " . print_r($statusList, true));

    $query = LeadTable::query()
        ->setSelect([
            "ID",
            "TITLE",
            "DATE_CREATE",
            "ASSIGNED_BY_ID",
            "STATUS_ID",
            "SOURCE_ID",
            "UF_CRM_1733695003876",    // Ручное распределение
            "UF_CRM_1733695065",       // Кто распределил
            "UF_CRM_1733695040593" // Дата распределения
        ])
        ->whereBetween("DATE_CREATE", $dateFromFormatted, $dateToFormatted);

    if ($type === 'distribution') {
        $query->where("UF_CRM_1733695003876", "1")
            ->setOrder(["UF_CRM_1733695040593" => "DESC"]);
    } else {
        $query->setOrder(["DATE_CREATE" => "ASC"]);
    }

    $leads = [];
    foreach ($query->exec() as $lead) {
        $assignedUser = $userList[$lead['ASSIGNED_BY_ID']] ?? ['FULL_NAME' => 'Неизвестный', 'DEPARTMENT_ID' => null];
        $departmentName = $departments[$assignedUser['DEPARTMENT_ID']] ?? "Неизвестный отдел";
        $statusId = $lead['STATUS_ID'];
        $statusInfo = $statusList[$statusId] ?? ['NAME' => 'Неизвестный', 'SEMANTICS' => ''];

        $leads[] = [
            'ID' => $lead['ID'],
            'TITLE' => $lead['TITLE'],
            'DATE_CREATE' => $lead['DATE_CREATE']->format('Y-m-d H:i'),
            'ASSIGNED_BY_ID' => $lead['ASSIGNED_BY_ID'],
            'ASSIGNED_BY_NAME' => $assignedUser['FULL_NAME'],
            'SOURCE_NAME' => $sourceList[$lead['SOURCE_ID']] ?? 'Неизвестный',
            'STATUS_NAME' => $statusInfo['NAME'],
            'STATUS_SEMANTICS' => $statusInfo['SEMANTICS'], // SEMANTICS
            'MANUAL_DISTRIBUTION' => $lead['UF_CRM_1733695003876'] == "1" ? "Да" : "Нет",
            'WHO_DISTRIBUTED_ID' => $lead['UF_CRM_1733695065'],
            'WHO_DISTRIBUTED_NAME' => $userList[$lead['UF_CRM_1733695065']]['FULL_NAME'] ?? '',
            'DISTRIBUTION_DATE' => $lead['UF_CRM_1733695040593']
                ? $lead['UF_CRM_1733695040593']->format('Y-m-d H:i')
                : null,
            'DEPARTMENT_NAME' => $departmentName,
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $leads,
    ]);
} catch (\Exception $e) {
	//    writeLog("Ошибка: " . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}