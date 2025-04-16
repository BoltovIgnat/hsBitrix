<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use HighSystem\Bitrix\BxDataHelper;

class HighSystemDocumentReportComponent extends CBitrixComponent
{
    /**
     * Возвращает диапазон дат на основе предустановки фильтра.
     *
     * @param string $preset
     * @return array
     */
    private function getDateRange($preset)
    {
        switch ($preset) {
            case 'current_month':
                return [date('Y-m-01'), date('Y-m-t')];
            case 'current_week':
                return [
                    date('Y-m-d', strtotime('monday this week')),
                    date('Y-m-d', strtotime('sunday this week'))
                ];
            case 'current_day':
                return [date('Y-m-d'), date('Y-m-d')];
            default:
                return [
                    $this->request->getQuery('START_DATE'),
                    $this->request->getQuery('END_DATE')
                ];
        }
    }

    /**
     * Выполнение компонента.
     */
    public function executeComponent()
    {
        try {
            $preset = $this->request->getQuery('DATE_PRESET') ?: 'current_month';
            list($startDate, $endDate) = $this->getDateRange($preset);
            $departmentId = $this->request->getQuery('DEPARTMENT') ?: '';

            $this->arResult['FILTER'] = [
                'START_DATE' => $startDate,
                'END_DATE' => $endDate,
                'DEPARTMENT' => $departmentId,
                'DATE_PRESET' => $preset,
            ];
            $startDate = DateTime::createFromFormat('Y-m-d', $startDate)->format('d.m.Y');
            $endDate = DateTime::createFromFormat('Y-m-d', $endDate)->format('d.m.Y');

            $this->arResult['DEPARTMENTS'] = [
                ['ID' => 32, 'NAME' => 'Отдел КАМ'],
                ['ID' => 35, 'NAME' => 'Team 1'],
                ['ID' => 36, 'NAME' => 'Team 2'],
                ['ID' => 38, 'NAME' => 'Отдел по работе с партнерами'],
            ];

            if ($startDate && $endDate) {
                $groupedDocs = $this->getDocumentsGroupedByUser(
                    (int)$this->arParams['IBLOCK_ID'],
                    'DATE_CREATE',
                    $startDate,
                    $endDate
                );

                foreach ($this->arResult['DEPARTMENTS'] as $key => $department) {
                    if ($departmentId && $departmentId != $department['ID']) {
                        continue;
                    }

                    $employees = BxDataHelper::getUsersByDepartment($department['ID']);
                    foreach ($employees as &$employee) {
                        $userId = (int)$employee['ID'];
                        $employee['DOCUMENTS'] = $groupedDocs[$userId] ?? [];
                        $this->addLinksToDocuments($employee['DOCUMENTS']);
                        $employee['DOCUMENT_STATS'] = [
                            'CONTACT' => count(array_filter($employee['DOCUMENTS'], fn($doc) => $doc['ENTITY'] === 'Контакт')),
                            'COMPANY' => count(array_filter($employee['DOCUMENTS'], fn($doc) => $doc['ENTITY'] === 'Компания')),
                            'DEALS' => count(array_filter($employee['DOCUMENTS'], fn($doc) => $doc['ENTITY'] === 'Сделка')),
                            'PROJECTS' => count(array_filter($employee['DOCUMENTS'], fn($doc) => $doc['ENTITY'] === 'Проект')),
                        ];
                        $employee['DOCUMENT_TOTAL'] = count($employee['DOCUMENTS']);
                        $employee['RESPONSIBLE_NAME'] = $this->getUserNameById($userId);
                    }

                    $this->arResult['DEPARTMENTS_WITH_USERS'][$key] = [
                        'ID' => $department['ID'],
                        'NAME_DEPART' => $department['NAME'],
                        'EMPLOYEE' => $employees,
                    ];
                }
            }

            // Запись в лог
            writeLog('report', 'Полный массив данных', $this->arResult);

            $this->includeComponentTemplate();
        } catch (Exception $e) {
            echo 'Ошибка: ' . $e->getMessage();
        }
    }

    /**
     * Подготовка параметров компонента.
     */
    public function onPrepareComponentParams($arParams)
    {
        if (!isset($arParams['IBLOCK_ID'])) {
            $arParams['IBLOCK_ID'] = 59;
        }

        return $arParams;
    }

    /**
     * Получает документы, сгруппированные по пользователям.
     */
    private function getDocumentsGroupedByUser(
        int $iblockId,
        string $dateField,
        string $startDate,
        string $endDate,
        bool $onlyActive = true
    ): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $filter = [
            'IBLOCK_ID' => $iblockId,
            '>=' . $dateField => $startDate . ' 00:00:00',
            '<=' . $dateField => $endDate . ' 23:59:59',
        ];

        if ($onlyActive) {
            $filter['ACTIVE'] = 'Y';
        }

        $selectFields = [
            'ID',
            'NAME',
            'DATE_CREATE',
            'PROPERTY_276', // Сущность
            'PROPERTY_275', // Ответственный
            'PROPERTY_278', // Проверяющий
            'PROPERTY_283', // Комментарий
            'PROPERTY_279', // Элемент CRM
        ];

        $result = [];
        $rsItems = \CIBlockElement::GetList(['DATE_CREATE' => 'ASC'], $filter, false, false, $selectFields);

        while ($item = $rsItems->GetNext()) {
            $userId = (int)$item['PROPERTY_275_VALUE'];
            $docData = [
                'ID' => $item['ID'],
                'DATE_CREATE' => $item['DATE_CREATE'],
                'NAME' => $item['NAME'],
                'ENTITY' => $item['PROPERTY_276_VALUE'] ?? null,
                'RESPONSIBLE' => $item['PROPERTY_275_VALUE'] ?? null,
                'CHECKER' => $item['PROPERTY_278_VALUE'] ?? null,
                'COMMENT' => $item['PROPERTY_283_VALUE']['TEXT'] ?? null,
                'ELEMENT_ID' => $item['PROPERTY_279_VALUE'] ?? null,
            ];

            $result[$userId][] = $docData;
        }

        writeLog('document', "Данные для отчета", $result);
        return $result;
    }

    /**
     * Добавляет ссылки к документам.
     */
    private function addLinksToDocuments(array &$documents): void
    {
        foreach ($documents as &$doc) {
            $entityType = strtolower($doc['ENTITY']);
            $elementId = $doc['ELEMENT_ID'];
            switch ($entityType) {
                case 'компания':
                    $doc['LINK'] = "https://crm.highsystem.ru/crm/company/details/{$elementId}/";
                    break;
                case 'контакт':
                    $doc['LINK'] = "https://crm.highsystem.ru/crm/contact/details/{$elementId}/";
                    break;
                case 'сделка':
                    $doc['LINK'] = "https://crm.highsystem.ru/crm/deal/details/{$elementId}/";
                    break;
                case 'проект':
                    $doc['LINK'] = "https://crm.highsystem.ru/page/proekty/proekty/type/174/details/{$elementId}/";
                    break;
                default:
                    $doc['LINK'] = null;
                    break;
            }
        }
    }

    /**
     * Получает имя пользователя по его ID.
     */
    private function getUserNameById(int $userId): string
    {
        $user = \CUser::GetByID($userId)->Fetch();
        if ($user) {
            return "{$user['NAME']} {$user['LAST_NAME']} [{$userId}]";
        }
        return "[{$userId}]";
    }
}
