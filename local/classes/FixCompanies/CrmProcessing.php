<?php

namespace Hs\FixCompanies;

use CCrmContact;

class CrmProcessing
{
    private array $companies = [];
    private array $contacts = [];
    private array $leas = [];
    private array $deals = [];
    private array $smarts = [];
    private array $users = [];
    private ICrmLogger $logger;
    private ICrmCache $cache;
    private bool $isDryRun = false;

    function __construct(ICrmLogger $logger, ICrmCache $cache)
    {
        $this->logger = $logger;
        $this->cache = $cache;

        if (!\Bitrix\Main\Loader::includeModule("crm")) {
            die('Module "crm" not loaded.');
        }

        @mkdir("./tmp", 0777);
    }

    /**
     * Закешировать контакты для экземпляра
     * @param array $contacts
     * @return void
     */
    function setCachedContacts(array &$contacts): void
    {
        $this->contacts = &$contacts;
    }

    /**
     * Закешировать компании для экземпляра
     * @param array $companies
     * @return void
     */
    function setCachedCompanies(array &$companies): void
    {
        $this->companies = &$companies;
    }

    /**
     * Закешировать пользователей для экземпляра
     * @param array $users
     * @return void
     */
    function setCachedUsers(array &$users): void
    {
        $this->users = &$users;
    }

    /**
     * Установить режим dry
     * @param bool $isDryRun
     * @return void
     */
    function setDryRun(bool $isDryRun): void
    {
        $this->isDryRun = $isDryRun;
    }

    /**
     * Отфильтровать необходимые поля элемента
     * @param $item
     * @param $filter
     * @return array
     */
    function getFilteredParams(&$item, &$filter)
    {
        return array_filter($item->getData(), function ($k) use ($filter) {
            return in_array($k, $filter);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Получить список пользователей
     * @param bool $isCached
     * @return array
     */
    function getUsers(bool $isCached = true): array
    {
        $fileName = "users.db";
        $cacheData = $this->cache->get($fileName);
        if (!empty($cacheData)) {
            return (array)$cacheData;
        }

        $users = [];
        $result = \Bitrix\Main\UserTable::getList([
            'select' => ['ID', 'NAME', 'LAST_NAME', 'ACTIVE', 'BLOCKED', 'LOGIN'],
            //'order' => ['LAST_LOGIN' => 'DESC'],
        ]);
        $mainUsers = [
            "v.bezzubenko@highsystem.ru",
            "r.solovov@highsystem.ru",
            "k.timoshenko@highsystem.ru",
            "t.azimov@highsystem.ru",
            "a.ponomarev@highsystem.ru",
            "s.klenichev@highsystem.ru",
            // уволенные
            "a.li@highsystem.ru",
            "a.skortsesku@highsystem.ru",
            "m.galkin@highsystem.ru",
            "v.obabkov@highsystem.ru",
        ];
        while ($arUser = $result->fetch()) {
            $arUser["IsMain"] = in_array($arUser["LOGIN"], $mainUsers);
            $arUser["IsActive"] = $arUser["ACTIVE"] == "Y" && $arUser["BLOCKED"] == "N";
            $users[$arUser['ID']] = $arUser;
        }
        if ($isCached) {
            $this->cache->set($fileName, $users);
        }
        return $users;
    }

    /**
     * Привязать контакт к компании
     * @param int $contactId
     * @param int $companyId
     * @return void
     */
    function bindContactToCompany(int $contactId, int $companyId): void
    {
//        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Contact);
//        $item = $factory->getItem($contactId);
        $item = $this->contacts[$contactId] ?? null;
        if ($item == null) {
            $this->logger->log("ERROR! Dry:{$this->isDryRun}:" . __METHOD__ . ":{$contactId}:{$companyId} Контакт {$contactId} не найден");
            return;
        }
//        $data = $item->getData();
        $companies = $item["COMPANY_IDS"];
        if (in_array($companyId, $companies)) {
            $this->logger->log("SKIP! Dry:{$this->isDryRun}:" . __METHOD__ . ":{$contactId}:{$companyId}} Контакт {$contactId} уже привязан к компании {$companyId}");
            return;
        }
        $lastCompanies = implode(",", $companies);
        $companies[] = $companyId;
        $companies = array_unique($companies);
        if (!$this->isDryRun) {
            $arFields = [
                "COMPANY_IDS" => $companies,
            ];
            $contact = new CCrmContact(false);
            $contact->Update($contactId, $arFields);
        }
        $this->contacts[$contactId]["COMPANY_IDS"] = $companies;
        $this->logger->log("[Dry:{$this->isDryRun}:" . __METHOD__ . ":{$contactId}:{$companyId}:{$lastCompanies}] Привязан контакт {$contactId} к компании {$companyId}");
    }

    /**
     * Назначить ответственного за компанию
     * @param int $companyId
     * @param int $userId
     * @return void
     */
    function setResponsibleCompany(int $companyId, int $userId): void
    {
//        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Company);
//        $item = $factory->getItem($companyId);
        $item = $this->companies[$companyId] ?? null;
        if ($item == null) {
            $this->logger->log("ERROR! Dry:{$this->isDryRun}:" . __METHOD__ . ":{$companyId}:{$userId} Компания {$companyId} не найдена");
            return;
        }
//        $data = $item->getData();
        if ($item["ASSIGNED_BY_ID"] == $userId) {
            $this->logger->log("SKIP! Dry:{$this->isDryRun}:" . __METHOD__ . ":{$companyId}:{$userId}:{$item["ASSIGNED_BY_ID"]} Пользователь {$userId} уже закреплен за компанией {$companyId}");
            return;
        }
        if (!$this->isDryRun) {
            $arFields = [
                "ASSIGNED_BY_ID" => $userId,
            ];
            $company = new CCrmCompany(false);
            $company->Update($companyId, $arFields);
        }
        $this->companies[$companyId]["ASSIGNED_BY_ID"] = $userId;
        $this->logger->log("[Dry:{$this->isDryRun}:" . __METHOD__ . ":{$companyId}:{$userId}:{$item["ASSIGNED_BY_ID"]}] Пользователь {$userId} закреплен за компанией {$companyId}, был {$item["ASSIGNED_BY_ID"]}");
    }

    /**
     * Назначить ответственного за контакт
     * @param int $contactId
     * @param int $userId
     * @return void
     */
    function setResponsibleContact(int $contactId, int $userId): void
    {
//        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Contact);
//        $item = $factory->getItem($contactId);
        $item = $this->contacts[$contactId] ?? null;
        if ($item == null) {
            $this->logger->log("ERROR! Dry:{$this->isDryRun}:" . __METHOD__ . ":{$contactId}:{$userId} Контакт {$contactId} не найден");
            return;
        }
//        $data = $item->getData();
        if ($item["ASSIGNED_BY_ID"] == $userId) {
            $this->logger->log("SKIP! Dry:{$this->isDryRun}:" . __METHOD__ . ":{$contactId}:{$userId}:{$item["ASSIGNED_BY_ID"]} Пользователь {$userId} уже закреплен за контактом {$contactId}");
            return;
        }
        if (!$this->isDryRun) {
            $arFields = [
                "ASSIGNED_BY_ID" => $userId,
            ];
            $contact = new CCrmContact(false);
            $contact->Update($contactId, $arFields);
        }
        $this->contacts[$contactId]["ASSIGNED_BY_ID"] = $userId;
        $this->logger->log("[Dry:{$this->isDryRun}:" . __METHOD__ . ":{$contactId}:{$userId}:{$item["ASSIGNED_BY_ID"]}] Пользователь {$userId} закреплен за контактом {$contactId}, был {$item["ASSIGNED_BY_ID"]}");
    }

    /**
     * Получить все контакты
     * @param bool $isLastWeek
     * @return array
     */
    function getContacts(bool $isLastWeek = false): array
    {
        $fileName = "contacts.db";
        $cacheData = $isLastWeek ? null : $this->cache->get($fileName);
        if (!empty($cacheData)) {
            return (array)$cacheData;
        }
        $filter = [];
        $this->processFilter($isLastWeek, $filter);
        $result = [];
        $select = ["ID", "FULL_NAME", "ASSIGNED_BY_ID", "COMPANY_ID", "CREATED_TIME", "COMPANY_IDS"];
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Contact);
        $items = $factory->getItems([
            "select" => $select,
            "filter" => $filter,
        ]);
        foreach ($items as $item) {
            $current = $this->getFilteredParams($item, $select);
            $current["CREATED_TIME"] = $current["CREATED_TIME"]->getTimeStamp();
            $result[$current["ID"]] = $current;
        }
        if (!$isLastWeek) {
            $this->cache->set($fileName, $result);
        }
        return $result;
    }

    /**
     * Выбрать пользователья по email
     * @param      $email
     * @return ?int
     */
    function getUserByEmail($email): ?int
    {
        /*        $id = array_key_first(array_filter($this->getUsers($isCached), function ($v, $k) use ($email) {
                    return $v["LOGIN"] == $email;
                }, ARRAY_FILTER_USE_BOTH));*/
        $id = array_key_first(array_filter($this->users, function ($v, $k) use ($email) {
            return $v["LOGIN"] == $email;
        }, ARRAY_FILTER_USE_BOTH));
        return $id;
    }

    /**
     * Получить все компании
     * @param bool $isLastWeek
     * @return array
     */
    function getCompanies(bool $isLastWeek = false): array
    {
        $fileName = "companies.db";
        $cacheData = $isLastWeek ? null : $this->cache->get($fileName);
        if (!empty($cacheData)) {
            return (array)$cacheData;
        }
        $result = [];
        $select = ["ID", "TITLE", "ASSIGNED_BY_ID", "CONTACT_IDS", "CREATED_TIME"];
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Company);
        $filter = [];
        $this->processFilter($isLastWeek, $filter);
        $items = $factory->getItems([
            "select" => $select,
            "filter" => $filter,
        ]);
        foreach ($items as $item) {
            $current = $this->getFilteredParams($item, $select);
            $current["CREATED_TIME"] = $current["CREATED_TIME"]->getTimeStamp();
            $result[$current["ID"]] = $current;
        }
        if (!$isLastWeek) {
            $this->cache->set($fileName, $result);
        }
        return $result;
    }

    /**
     * Получить смарт процессы
     * @param int  $id
     * @param bool $isLastWeek
     * @return array
     */
    function getSmartProcesses(int $id, bool $isLastWeek = false): array
    {
        $fileName = "smart{$id}.db";
        $cacheData = $isLastWeek ? null : $this->cache->get($fileName);
        if (!empty($cacheData)) {
            return (array)$cacheData;
        }
        $result = [];
        $select = ["ID", "TITLE", "OPENED", "STAGE_ID", "ASSIGNED_BY_ID", "COMPANY_ID", "CONTACT_ID", "CONTACT_IDS", "CREATED_TIME"];
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($id);
        $filter = [];
        $this->processFilter($isLastWeek, $filter);
        $items = $factory->getItems([
            "select" => $select,
            "filter" => $filter,
        ]);
        foreach ($items as $item) {
//            print_r($item->getData()); die();
            $current = $this->getFilteredParams($item, $select);
            $current["CREATED_TIME"] = $current["CREATED_TIME"]->getTimeStamp();
            $result[$current["ID"]] = $current;
        }
        if (!$isLastWeek) {
            $this->cache->set($fileName, $result);
        }
        return $result;
    }

    function getSmartProcessesByFilter(int $id, array $filter): array
    {
        $result = [];
        $select = [
            "ID", "TITLE", "OPENED", "STAGE_ID", "ASSIGNED_BY_ID", "COMPANY_ID", "CONTACT_ID", "CONTACT_IDS", "CREATED_TIME",
        ];
        foreach ($filter as $key => $value) {
            $select[] = $key;
        }
        $select = array_unique($select);
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($id);
        $items = $factory->getItems([
            "select" => $select,
            "filter" => $filter,
        ]);
        foreach ($items as $item) {
            $current = $this->getFilteredParams($item, $select);
            $current["CREATED_TIME"] = $current["CREATED_TIME"]->getTimeStamp();
            $result[$current["ID"]] = $current;
        }
        return $result;
    }

    function getSmartProcessById(int $processId, int $entityId): array
    {
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($processId);
        $item = $factory->getItem($entityId);
        return $item->getData();
    }

    /**
     * Получить все сделки
     * @param bool $isLastWeek
     * @return array
     */
    function getDeals(bool $isLastWeek = false): array
    {
        $fileName = "deals.db";
        $cacheData = $isLastWeek ? null : $this->cache->get($fileName);
        if (!empty($cacheData)) {
            return (array)$cacheData;
        }
        $result = [];
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
        $select = ["ID", "COMPANY_ID", "CONTACT_ID", "ASSIGNED_BY_ID", "OPENED", "IS_WORK", "IS_WON", "IS_LOSE", "STAGE_ID", "CREATED_TIME"];
        $limit = 20000;
        $filter = [
            "!COMPANY_ID" => false,
        ];
        $this->processFilter($isLastWeek, $filter);
//        $countItems = $factory->getCountItems($filter);
//        $steps = ceil($countItems / $limit);
//        for ($i = 0; $i < $steps; $i++) {
        $items = $factory->getItems([
            "filter" => $filter,
            "select" => $select,
//                "offset" => $i * $limit,
//                "limit" => $limit,
        ]);
        foreach ($items as $item) {
//                print_r($item->getData());
            $current = $this->getFilteredParams($item, $select);
            $current["CREATED_TIME"] = $current["CREATED_TIME"]->getTimeStamp();
            $result[$current["ID"]] = $current;
        }
//        }
        if (!$isLastWeek) {
            $this->cache->set($fileName, $result);
        }
        return $result;
    }

    /**
     * Получить лиды
     * @param bool $isLastWeek
     * @return array
     */
    function getLeads(bool $isLastWeek = false): array
    {
        $fileName = "leads.db";
        $cacheData = $isLastWeek ? null : $this->cache->get($fileName);
        if (!empty($cacheData)) {
            return (array)$cacheData;
        }
        $result = [];
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Lead);
        $select = ["ID", "COMPANY_ID", "CONTACT_ID", "ASSIGNED_BY_ID", "OPENED", "STAGE_ID", "CREATED_TIME"];
        $limit = 20000;
        $filter = [
            "!COMPANY_ID" => false,
        ];
        $this->processFilter($isLastWeek, $filter);
//        $countItems = $factory->getCountItems($filter);
//        $steps = ceil($countItems / $limit);
//        for ($i = 0; $i < $steps; $i++) {
        $items = $factory->getItems([
            "filter" => $filter,
            "select" => $select,
//                "offset" => $i * $limit,
//                "limit" => $limit,
        ]);
        foreach ($items as $item) {
//                print_r($item->getData()); die();
            $current = $this->getFilteredParams($item, $select);
            $current["CREATED_TIME"] = $current["CREATED_TIME"]->getTimeStamp();
            $result[$current["ID"]] = $current;
        }
        echo date("d.m.Y H:i:s") . "\n";
//            die("stop 1");
//        }
        if (!$isLastWeek) {
            $this->cache->set($fileName, $result);
        }
        return $result;
    }

    /**
     * Добавить фильтр по дате
     * @param bool  $isLastWeek
     * @param array $filter
     */
    function processFilter(bool $isLastWeek, array &$filter)
    {
        if ($isLastWeek) {
            $filter[">=UPDATED_TIME"] = $this->getLastWeekDate();
        }
    }

    /**
     * Собрать все контакты компаний из разных источников
     * @param $sources
     * @return array
     */
    function combineContactsCompanies($sources): array
    {
        $result = [];
        foreach ($sources as $source) {
            foreach ($source as $item) {
                $result[$item["COMPANY_ID"]][] = $item["CONTACT_ID"];
            }
        }
        foreach ($result as &$v) {
            $v = array_unique($v);
        }
        return $result;
    }

    /**
     * Сравнить компании для поиска непривязанных контактов и вернуть непривязанные
     * @param $companies
     * @param $bindings
     * @return array
     */
    function getNotBindingContacts(&$bindings): array
    {
        $differences = [];
        foreach ($bindings as $id => $binding) {
            foreach ($binding as $v) {
                if ($v == 0) {
                    continue;
                }
                if ($this->companies[$id]["CONTACT_IDS"] == null || !in_array($v, $this->companies[$id]["CONTACT_IDS"])) {
                    $differences[$id][] = $v;
                }
            }
        }
        return $differences;
    }

    /**
     * Вернуть список контактов с неправильными ответственными
     * @param $companies
     * @param $contacts
     * @param $users
     * @return array
     */
    function getWrongAssignedContacts(): array
    {
        $_contacts = [];
        foreach ($this->companies as $company) {
            if (!$this->users[$company["ASSIGNED_BY_ID"]]["IsMain"]) {
                $_contacts = array_merge($_contacts, $company["CONTACT_IDS"]);
            }
        }
        $result = [];
        $_contacts = array_unique($_contacts);
        foreach ($_contacts as $id) {
            $responsible = $this->contacts[$id]["ASSIGNED_BY_ID"];
            if ($this->users[$responsible]["IsMain"]) {
                $result[] = $id;
            }
        }
        return $result;
    }

    /**
     * Получить сущности с привязкой к пользователям
     * @param $companies
     * @param $users
     * @return array
     */
    function getMainAssigned(): array
    {
        $result = [];
        foreach ($this->companies as $company) {
            if ($this->users[$company["ASSIGNED_BY_ID"]]["IsMain"]) {
                $result[] = $company["ID"];
            }
        }
        return $result;
    }

    /**
     * Получить сущности без привязки к пользователям
     * @param $companies
     * @param $users
     * @return array
     */
    function getNotMainAssigned(): array
    {
        $result = [];
        foreach ($this->companies as $company) {
            if (!$this->users[$company["ASSIGNED_BY_ID"]]["IsMain"]) {
                $result[] = $company["ID"];
            }
        }
        return $result;
    }

    /**
     * Получить дату для выбора сущностей за последнюю неделю
     * @return string
     */
    function getLastWeekDate()
    {
        return date("d.m.Y", strtotime("-2 day"));
    }
}