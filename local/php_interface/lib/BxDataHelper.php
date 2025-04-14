<?php

namespace HighSystem\Bitrix;

use Bitrix\Main\Loader;
use Bitrix\Crm\Service\Container;

/**
 * Класс BxDataHelper - универсальный набор методов
 * для получения данных из разных областей Bitrix.
 */
class BxDataHelper
{
    /**
     * Получить реквизиты (ИНН и прочее) по компании.
     * @param int $companyId
     * @return array
     */
    public static function getCompanyRequisites(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }

        \Bitrix\Main\Loader::includeModule('crm');

        $requisite = new \Bitrix\Crm\EntityRequisite();
        $dbRes = $requisite->getList([
            'filter' => [
                'ENTITY_ID'      => $companyId,
                // ВАЖНО: указываем глобальное пространство имён для CCrmOwnerType
                'ENTITY_TYPE_ID' => \CCrmOwnerType::Company
            ],
            'select' => ['ID', 'PRESET_ID', 'NAME', 'RQ_INN', 'RQ_KPP']
        ]);

        $result = [];
        while ($item = $dbRes->fetch()) {
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Получить данные о компании по её ID
     * (включая пользовательские поля UF_*).
     *
     * @param int   $companyId  Идентификатор компании
     * @param array $select     Набор полей (по умолчанию ['*','UF_*'])
     *
     * @return array Ассоциативный массив полей или пустой массив, если не найдена
     */
    public static function getCompanyData(int $companyId, array $select = ['*', 'UF_*']): array
    {
        if ($companyId <= 0) {
            return [];
        }

        \Bitrix\Main\Loader::includeModule('crm');

        $res = \Bitrix\Crm\CompanyTable::getList([
            'filter' => ['=ID' => $companyId],
            'select' => $select,
            'limit'  => 1,
        ]);

        $company = $res->fetch();
        return $company ?: [];
    }

    /**
     * Получить данные о контакте по её ID
     * (включая пользовательские поля UF_*).
     *
     * @param int   $contactId  Идентификатор контакта
     * @param array $select     Набор полей (по умолчанию ['*','UF_*'])
     *
     * @return array Ассоциативный массив полей или пустой массив, если не найдена
     */
    public static function getContactData(int $contactId, array $select = ['*', 'UF_*']): array
    {
        if ($contactId <= 0) {
            return [];
        }

        \Bitrix\Main\Loader::includeModule('crm');

        $res = \Bitrix\Crm\ContactTable::getList([
            'filter' => ['=ID' => $contactId],
            'select' => $select,
            'limit'  => 1,
        ]);

        $contact = $res->fetch();
        return $contact ?: [];
    }

    /**
     * Получает стадии и состояние стадий указанной сущности (сделка / смарт-процесс)
     * и раскладывает их по категориям:
     *  - ALL (все стадии)
     *  - QUALITY (стадии с семантикой 'S' — Качественные)
     *  - NON_QUALITY (стадии с семантикой 'F' — Не качественные)
     *
     * @param int $entityTypeId \CCrmOwnerType::Deal (2) или ID смарт-процесса (например, 174).
     *
     * @return array [
     *   'ALL'         => [...],
     *   'ACTIVE'      => [...],
     *   'QUALITY'     => [...],
     *   'NON_QUALITY' => [...]
     * ]
     */
    public static function getStagesCategorized(int $entityTypeId): array
    {
        \Bitrix\Main\Loader::includeModule('crm');

        $factory = Container::getInstance()->getFactory($entityTypeId);
        if (!$factory) {
            return [
                'ALL'         => [],
                'ACTIVE'      => [],
                'QUALITY'     => [],
                'NON_QUALITY' => []
            ];
        }

        $stages = $factory->getStages();

        $result = [
            'ALL'         => [],
            'ACTIVE'      => [],
            'QUALITY'     => [],
            'NON_QUALITY' => []
        ];

        foreach ($stages as $stage)
        {
            $statusId  = $stage->getStatusId();
            $semantics = $stage->getSemantics();

            // Все стадии
            $result['ALL'][] = $statusId;

            // Качественные (SEMANTICS = 'S')
            if ($semantics === 'S') {
                $result['QUALITY'][] = $statusId;
            }
            // Некачественные (SEMANTICS = 'F')
            elseif ($semantics === 'F') {
                $result['NON_QUALITY'][] = $statusId;
            }
        }

        $result['ACTIVE'] = array_diff($result['ALL'], $result['QUALITY'], $result['NON_QUALITY']);

        return $result;
    }

    /**
     * Возвращает количество сделок компании.
     * Фильтрует по массиву стадий (stageIds),
     * Если ничего не передано — возвращаются все сделки компании.
     *
     * @param int         $companyId
     * @param array|null  $stageIds       (Опционально) массив ID стадий, например ["NEW","PREPARATION"]
     *
     * @return int
     */
    public static function getDealsCountByCompany(int $companyId, ?array $stageIds = null): int
    {
        \Bitrix\Main\Loader::includeModule('crm');

        // Базовый фильтр
        $filter = [
            'COMPANY_ID' => $companyId
        ];

        // Если передали НЕ пустой массив стадий — фильтруем только по ним
        if (!empty($stageIds)) {
            $filter['@STAGE_ID'] = $stageIds;
        }
        $res = \Bitrix\Crm\DealTable::query()
            ->setSelect(["ID", "COMPANY_ID", "STAGE_ID"])
            ->setFilter($filter)
            ->exec();

        return $res->getSelectedRowsCount();
    }


    /**
     * Проверяет право на чтение любой CRM-сущности (контакт, компания, смарт-процесс и т.д.)
     *
     * @param int $entityTypeId  ID типа сущности (например, \CCrmOwnerType::Contact, \CCrmOwnerType::Company, \CCrmOwnerType::DynamicType и т.д.)
     * @param int $entityId      ID самой сущности
     * @param int $userId        ID пользователя, для которого проверяются права
     * @return bool              true, если у пользователя есть право чтения, иначе false
     */
    public static function canUserReadCrmElement(int $entityTypeId, int $entityId, int $userId): bool
    {
        \Bitrix\Main\Loader::includeModule('crm');

        // Получаем объект прав пользователя
        $permissions = \CCrmPerms::GetUserPermissions($userId);

        // Используем универсальную проверку через CCrmAuthorizationHelper
        return \CCrmAuthorizationHelper::CheckReadPermission($entityTypeId, $entityId, $permissions);
    }

    /**
     * Возвращает название подразделения по его ID.
     *
     * @param int $departmentId     ID раздела (подразделения)
     *
     * @return string|null          Название подразделения или null, если не найдено
     */
    public static function getDepartmentNameById (int $departmentId) {

        // Подключаем модуль iblock
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return null;
        }

        // Получаем раздел (подразделение) по ID
        $res = \CIBlockSection::GetList(
            [],
            [
                'IBLOCK_ID' => 1,
                'ID'        => $departmentId,
                'ACTIVE'    => 'Y',
            ],
            false,
            ['ID', 'NAME']
        );

        if ($arSection = $res->Fetch()) {
            return $arSection['NAME'] ?: null;
        }

        return null;
    }

    /**
     * Получает список пользователей, отфильтрованных по активности и ID подразделения.
     *
     * @param int   $departmentId     ID подразделения (UF_DEPARTMENT).
     * @param bool  $activeOnly       Фильтровать только активных пользователей (ACTIVE = 'Y').
     * @return array                  Массив с данными о пользователях (ID, LOGIN, NAME, LAST_NAME и т.д.).
     *
     * Пример поля UF_DEPARTMENT:
     *   - Если пользователь состоит в одном подразделении, там может быть [10].
     *   - Если в нескольких, это может быть [10, 12] и т.д.
     */
    public static function getUsersByDepartment(int $departmentId, bool $activeOnly = true): array
    {
        // Подключаем модуль principal (main), чтобы работать с CUser
        if (!\Bitrix\Main\Loader::includeModule('main')) {
            return [];
        }

        // Фильтр по пользователям
        // UF_DEPARTMENT хранит массив ID подразделений. При поиске можно использовать оператор "основной" ~ UF_DEPARTMENT
        // Однако CUser::GetList обычно корректно обрабатывает UF_DEPARTMENT = $departmentId, если пользователь входит в это подразделение.
        $filter = [
            'UF_DEPARTMENT' => $departmentId
        ];
        if ($activeOnly) {
            $filter['ACTIVE'] = 'Y';
        }

        // Сортировка
        $by    = 'ID';
        $order = 'ASC';

        // Получаем только основные поля (при необходимости можно расширить)
        $selectFields = [
            'FIELDS' => [
                'ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL', 'ACTIVE'
            ]
        ];

        $rsUsers = \CUser::GetList($by, $order, $filter, $selectFields);

        $result = [];
        while ($arUser = $rsUsers->Fetch()) {
            $result[] = $arUser;
        }

        return $result;
    }

    public static function buildCompanyParams (array $arrayData, string $entity): array
    {
        $params = [];

        writeLog('Logic', 'Получены данные из ' . $entity);
        writeLog('Logic', 'Необходимо получить данные: ', $arrayData);

        //Проверка прав доступа у пользователя
        $entityTypeId = \CCrmOwnerType::Company;
        $entityId = $arrayData['COMPANY_ID'];
        $userId = $arrayData['USER_ID'];
        $params['ACCESS'] = self::canUserReadCrmElement($entityTypeId,$entityId,$userId);

        if ($params['ACCESS'] === true) {

            writeLog('Logic', 'У пользователя есть доступ к компании');

            //Получение ИНН / КПП и запись в массив
            $reqCompany = self::getCompanyRequisites($entityId);

            $params['RQ_INN'] = $reqCompany[0]['RQ_INN'];
            $params['RQ_KPP'] = $reqCompany[0]['RQ_KPP'];

            //Получение данных из компании и запись в массив
            $companyField = self::getCompanyData($entityId);
            $params['companyID'] = $entityId;
            $params['type'] = $companyField['COMPANY_TYPE'];
            $params['link'] = $companyField['UF_CRM_1675832535137'];
            $params['revenue'] = $companyField['UF_CRM_1675832278996'];

            //Получить массив стадий сделок (\CCrmOwnerType::Deal == 2)
            $stagesDeal = BxDataHelper::getStagesCategorized(2);

            //Посчитать кол-во сделок
            $params['DealsCount'] = self::getDealsCountByCompany($entityId);
            $params['successDealsCount'] = self::getDealsCountByCompany($entityId, $stagesDeal['QUALITY']);
            $params['activeDealsCount'] = self::getDealsCountByCompany($entityId, $stagesDeal['ACTIVE']);

            //Получить массив стадий Проектов (\CCrmOwnerType::entityTypeId == 174)
            $stagesProject = BxDataHelper::getStagesCategorized(174);

            //Посчитать кол-во проектов
            $params['projectsAll'] = self::getDealsCountByCompany($entityId);
            $params['projectsSuccess'] = self::getDealsCountByCompany($entityId, $stagesDeal['QUALITY']);
            $params['projectsActive'] = self::getDealsCountByCompany($entityId, $stagesDeal['ACTIVE']);

            $params['class'] = [
                'CUSTOMER' => 'customer',
                'SUPPLIER' => 'supplier',
                'COMPETITOR' => 'competitor',
                'PARTNER' => 'partner',
                'OTHER' => 'other'
            ];
        } else {
            writeLog('Logic', 'У пользователя отсутствует доступ к компании');
        }

        return $params;
    }
}
