<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;

if (!Loader::includeModule('iblock') || !Loader::includeModule('main')) {
    echo json_encode([]);
    die();
}

$userId = intval($_GET['USER_ID']);
$iblockId = 59; // ID инфоблока с документами
$startDate = DateTime::createFromFormat('Y-m-d', $_GET['START_DATE'])->format('d.m.Y');
$endDate = DateTime::createFromFormat('Y-m-d', $_GET['END_DATE'])->format('d.m.Y');

// Получение документов по userId
$filter = [
    'IBLOCK_ID' => $iblockId,
    'PROPERTY_275' => $userId, // Поле "Ответственный"
    '>=DATE_CREATE' => $startDate . ' 00:00:00',
    '<=DATE_CREATE' => $endDate . ' 23:59:59',
    'ACTIVE' => 'Y',
];

$selectFields = [
    'ID',
    'NAME',
    'DATE_CREATE',
    'PROPERTY_276', // Сущность CRM
    'PROPERTY_275', // Ответственный
    'PROPERTY_278', // Проверяющий
    'PROPERTY_283', // Комментарий
    'PROPERTY_279', // ID элемента CRM
];

// Кэш для пользователей
$userCache = [];

function getUserName($userId) {
    global $userCache;

    // Проверяем, есть ли пользователь в кэше
    if (isset($userCache[$userId])) {
        return $userCache[$userId];
    }

    // Получаем данные пользователя из базы
    $user = UserTable::getList([
        'filter' => ['ID' => $userId],
        'select' => ['ID', 'NAME', 'LAST_NAME']
    ])->fetch();

    if ($user) {
        // Формируем полное имя и сохраняем в кэш
        $fullName = "{$user['NAME']} {$user['LAST_NAME']} [{$user['ID']}]";
        $userCache[$userId] = $fullName;
        return $fullName;
    }

    return "Неизвестный [{$userId}]";
}

$result = [];
$rsItems = CIBlockElement::GetList(['DATE_CREATE' => 'DESC'], $filter, false, false, $selectFields);
while ($item = $rsItems->GetNext()) {
    // Определяем ссылку на элемент CRM
    $link = '';
    $entityType = strtolower($item['PROPERTY_276_VALUE'] ?? '');
    $elementId = preg_replace('/\D/', '', $item['PROPERTY_279_VALUE']) ?? '';

    switch ($entityType) {
        case 'Компания':
            $link = "https://crm.highsystem.ru/crm/company/details/{$elementId}/";
            $documentName = 'Компания: ' . $elementId;
            break;
        case 'Контакт':
            $link = "https://crm.highsystem.ru/crm/contact/details/{$elementId}/";
            $documentName = 'Контакт: ' . $elementId;
            break;
        case 'Сделка':
            $link = "https://crm.highsystem.ru/crm/deal/details/{$elementId}/";
            $documentName = 'Сделка: ' . $elementId;
            break;
        case 'Проект':
            $link = "https://crm.highsystem.ru/page/proekty/proekty/type/174/details/{$elementId}/";
            $documentName = 'Проект: ' . $elementId;
            break;
        default:
            $link = null; // Если тип сущности не распознан
            break;
    }

    // Получение Имени и Фамилии ответственного
    $responsibleName = getUserName($item['PROPERTY_275_VALUE']);
    $checkerName = getUserName($item['PROPERTY_278_VALUE']);

    $result[] = [
        'ID' => $item['ID'],
        'NAME' => $item['NAME'],
        'DATE_CREATE' => date('d.m.Y H:i', strtotime($item['DATE_CREATE'])),
        'ENTITY' => $item['PROPERTY_276_VALUE'] ?? '',
        'ELEMENT' => $documentName,
        'RESPONSIBLE' => $responsibleName,
        'CHECKER' => $checkerName,
        'COMMENT' => $item['PROPERTY_283_VALUE']['TEXT'] ?? '',
        'LINK' => $link, // Добавляем ссылку
    ];

	    writeLog('updateTable', "Данные", $item);


}

echo json_encode($result);
