<?php
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\LeadTable;

class DashboardComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        try {
            // Получение данных фильтра
            $quickSelect = $_GET['quick-select'] ?? 'range';
            $dateFrom = $_GET['start-date'] ?? null;
            $dateTo = $_GET['end-date'] ?? null;

            // Преобразование дат
            if (!$dateFrom || !$dateTo) {
                [$dateFrom, $dateTo] = $this->getDateRange($quickSelect);
            }
            $dateFrom = new DateTime($dateFrom . ' 00:00:00');
            $dateTo = new DateTime($dateTo . ' 23:59:59');

            // Получение источников
            $sourceList = \CCrmStatus::GetStatusList('SOURCE');
            $statusList = \CCrmStatus::GetStatusList('STATUS');

            // Получение списка пользователей
            $userList = $this->getUserList(11);

            // Запрос лидов
            $query = LeadTable::query()
                ->setSelect([
                    "ID",
                    "TITLE",
                    "DATE_CREATE",
                    "ASSIGNED_BY_ID",
                    "STATUS_ID",
                    "SOURCE_ID"
                ])
                ->whereBetween("DATE_CREATE", $dateFrom, $dateTo)
                ->setOrder(["DATE_CREATE" => "ASC"]);

            $leads = [];
            foreach ($query->exec() as $lead) {
                $leads[] = [
                    'ID' => $lead['ID'],
                    'TITLE' => $lead['TITLE'],
                    'DATE_CREATE' => (new DateTime($lead['DATE_CREATE']))->format('Y-m-d H:i'),
                    'ASSIGNED_BY_NAME' => $userList[$lead['ASSIGNED_BY_ID']] ?? 'Неизвестный',
                    'SOURCE_NAME' => $sourceList[$lead['SOURCE_ID']] ?? 'Неизвестный',
                    'STATUS_NAME' => $statusList[$lead['STATUS_ID']] ?? 'Неизвестный',
                ];
            }

            $this->arResult['LEADS'] = $leads;

        } catch (\Exception $e) {
            $this->arResult['ERROR'] = $e->getMessage();
        }

        $this->includeComponentTemplate();
    }

    private function getUserList($groupId)
    {
        $userList = [];
        $res = \CUser::GetList(
            $by = 'id',
            $order = 'asc',
            ['GROUPS_ID' => [$groupId]],
            ['SELECT' => ['ID', 'NAME', 'LAST_NAME']]
        );
        while ($user = $res->Fetch()) {
            $userList[$user['ID']] = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
        }
        return $userList;
    }

    private function getDateRange($quickSelect)
    {
        $today = new \DateTime();
        if ($quickSelect === 'today') {
            $start = $end = $today->format('Y-m-d');
        } elseif ($quickSelect === 'week') {
            $start = $today->modify('this week')->format('Y-m-d');
            $end = $today->modify('+6 days')->format('Y-m-d');
        } elseif ($quickSelect === 'month') {
            $start = $today->modify('first day of this month')->format('Y-m-d');
            $end = $today->modify('last day of this month')->format('Y-m-d');
        } else {
            $start = $_GET['start-date'] ?? $today->format('Y-m-d');
            $end = $_GET['end-date'] ?? $today->format('Y-m-d');
        }
        return [$start, $end];
    }
}