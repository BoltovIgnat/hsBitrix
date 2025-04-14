<?php

class myBP {

    public static function getAndSortSmartElement (int $idElement, int $smartId, string $filterField) {

        $searchItems = new Dbbo\Crm\Smart();
        $searchItems->SetEntityId($smartId);
        $filter = [$filterField => $idElement];
        $items = $searchItems->GetItems(['filter' => $filter]);

        if (!empty($items)) {
            return $items; // Вернуть массив $items
        } else {
            return []; // Вернуть пустой массив в случае отсутствия элементов
        }

    }

    public static function getAndSortSmartElementToCompanyDev($companyId, $idSmartClientResuscitation, $filterField) {
        $itemSort = [
            'Planning' => [],
            'Take' => [],
            'Overdue' => [],
            'Choice' => []
        ];

        $stageOpen = ['DT149_14:NEW', 'DT149_14:UC_ZQ0BZZ', 'DT149_14:PREPARATION', 'DT149_14:CLIENT', 'DT149_14:1', 'DT149_14:2'];
        $stagePlanned = ['DT149_14:PREPARATION', 'DT149_14:CLIENT', 'DT149_14:1', 'DT149_14:2'];
        $stageTake = ['DT149_14:UC_ZQ0BZZ'];
        $stageOverdue = ['DT149_14:NEW'];

        $searchItems = new Dbbo\Crm\Smart();
        $searchItems->SetEntityId($idSmartClientResuscitation);
        $filter = [$filterField => $companyId];
        $items = $searchItems->GetItems(['filter' => $filter]);

        foreach ($items as $item) {
            $stageIdElement = $item['STAGE_ID'];

            if (in_array($stageIdElement, $stageOpen)) {
                if (in_array($stageIdElement, $stagePlanned)) {
                    $itemSort['Planning'][] = [$item['ID'], $stageIdElement, $item['ASSIGNED_BY_ID']];
                } elseif (in_array($stageIdElement, $stageTake)) {
                    $itemSort['Take'][] = [$item['ID'], $stageIdElement, $item['ASSIGNED_BY_ID']];
                } elseif (in_array($stageIdElement, $stageOverdue)) {
                    $itemSort['Overdue'][] = [$item['ID'], $stageIdElement, $item['ASSIGNED_BY_ID']];
                } else {
                    $itemSort['Choice'][] = [$item['ID'], $stageIdElement, $item['ASSIGNED_BY_ID']];
                }
            }
        }
        return $itemSort;
    }

    public static function getAndSortSmartElementToCompanyProd($companyId, $idSmartClientResuscitation, $filterField) {
        $itemSort = [
            'Planning' => [],
            'Take' => [],
            'Overdue' => [],
            'Choice' => []
        ];

        $stageOpen = ['DT168_15:NEW', 'DT168_15:UC_GOORZW', 'DT168_15:PREPARATION', 'DT168_15:CLIENT', 'DT168_15:1', 'DT168_15:2'];
        $stagePlanned = ['DT168_15:PREPARATION', 'DT168_15:CLIENT', 'DT168_15:1', 'DT168_15:2'];
        $stageTake = ['DT168_15:UC_GOORZW'];
        $stageOverdue = ['DT168_15:NEW'];

        $searchItems = new Dbbo\Crm\Smart();
        $searchItems->SetEntityId($idSmartClientResuscitation);
        $filter = [$filterField => $companyId];
        $items = $searchItems->GetItems(['filter' => $filter]);

        foreach ($items as $item) {
            $stageIdElement = $item['STAGE_ID'];

            if (in_array($stageIdElement, $stageOpen)) {
                if (in_array($stageIdElement, $stagePlanned)) {
                    $itemSort['Planning'][] = [$item['ID'], $stageIdElement, $item['ASSIGNED_BY_ID']];
                } elseif (in_array($stageIdElement, $stageTake)) {
                    $itemSort['Take'][] = [$item['ID'], $stageIdElement, $item['ASSIGNED_BY_ID']];
                } elseif (in_array($stageIdElement, $stageOverdue)) {
                    $itemSort['Overdue'][] = [$item['ID'], $stageIdElement, $item['ASSIGNED_BY_ID']];
                } else {
                    $itemSort['Choice'][] = [$item['ID'], $stageIdElement, $item['ASSIGNED_BY_ID']];
                }
            }
        }
        return $itemSort;
    }
}