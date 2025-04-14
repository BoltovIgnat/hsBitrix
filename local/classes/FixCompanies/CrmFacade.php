<?php

namespace Hs\FixCompanies;

class CrmFacade
{
    private static ?CrmProcessing $crmProcessing = null;

    /**
     * Получить всех пользователей
     * @param bool $isCached
     * @return array
     */
    static function getUsers(bool $isCached = false): array
    {
        return self::getInstance()->getUsers($isCached);
    }

    /**
     * Привязка к Карпову
     * @return void
     */
    public static function bindToKarpov(): void
    {
        $karpovId = self::getInstance()->getUserByEmail("a.karpov@highsystem.ru");
        echo "Karpov id: " . $karpovId . "\n";
        $found = self::getInstance()->getSmartProcessesByFilter(141, ["UF_CRM_12_1698319475" => "SUPPLIER"]);
//        print_r($found); die();
        foreach ($found as $item) {
            $companyId = $item["COMPANY_ID"];
            $contacts = $item["CONTACT_IDS"];
            if (!empty($companyId)) {
                self::getInstance()->setResponsibleCompany($companyId, $karpovId);
            }
            if (!empty($contacts)) {
                foreach ($contacts as $contactId) {
                    if (!empty($contactId)) {
                        self::getInstance()->setResponsibleContact($contactId, $karpovId);
                    }
                }
            }
        }
    }

    /**
     * Инициализация инстанса
     * @return CrmProcessing|null
     */
    private static function getInstance()
    {
        if (self::$crmProcessing == null) {
            self::$crmProcessing = new CrmProcessing(new CrmLogger(), new CrmCache());
        }
//        self::$crmProcessing->setDryRun(true);
        return self::$crmProcessing;
    }

    /**
     * Обработать открытые проекты, закрепить компании и контакты за неуволенными сотрудниками, которые были первыми
     * ответственными
     * @param array $users
     * @param array $companies
     * @return void
     */
    public static function processOpenedProjects(array &$users, array &$companies): void
    {
        $openedProjects = self::getInstance()->getSmartProcessesByFilter(174, ["OPENED" => true]);
        echo "Opened projects: " . count($openedProjects) . "\n";
//        print_r($openedProjects);
//        die();
        self::setFirstResponsibleToContacts(["Projects" => $openedProjects], $users, $companies);
    }

    /**
     * Выбрать ответственных у разных сущностей пачкой
     * @param array $groups
     * @param array $users
     * @param array $companies
     * @return void
     */
    public static function processBulkEntities(array &$groups, array &$users, array &$companies): void
    {
        $_groups = [];
        foreach ($groups as $nameGroup => $entities) {
            $filteredEntities = array_filter($entities, function ($v) use ($users) {
                if ($v["OPENED"] && !$users[$v["ASSIGNED_BY_ID"]]["IsMain"]) {
                    return true;
                }
                return false;
            });
            $_groups[$nameGroup] = $filteredEntities;
        }
//        echo "All entities before filter: " . count($entities) . "\n";
//        echo "All entities to sync: " . count($filteredEntities) . "\n";
        self::setFirstResponsibleToContacts($_groups, $users, $companies);
    }

    /**
     * @param array $groups
     * @param array $users
     * @param array $companies
     * @return void
     */
    public static function setFirstResponsibleToContacts(array $groups, array &$users, array &$companies): void
    {
        $stages = [];
        $links = [];
        $goodStatuses = [
            "Leads" => ["NEW", "IN_PROCESS"],
            "Deals" => ["NEW", "PREPARATION", "UC_X2DO9S", "1", "PREPAYMENT_INVOICE", "UC_W97ONA", "10", "UC_AMPHT1", "9", "7"],
            "Projects" => ["DT174_10:NEW", "DT174_10:PREPARATION", "DT174_10:CLIENT", "DT174_10:1", " DT174_10:2", "DT174_10:3"],
            "Processes" => [],
        ];
        foreach ($groups as $nameGroup => $entities) {
            foreach ($entities as $entity) {
                if (!in_array($entity["STAGE_ID"], $goodStatuses[$nameGroup])) {
                    continue;
                }
                $stages[$nameGroup . "-" . $entity["STAGE_ID"]]++;
                $responsibleId = $entity["ASSIGNED_BY_ID"];
                if (!$users[$responsibleId]["IsActive"]) {
                    if ($nameGroup == "Projects") {
                        printf("Entity (%s) %d, blocked user: %d\n", $nameGroup, $entity["ID"], $responsibleId);
                    }
                    continue;
                }
                $timeStamp = $entity["CREATED_TIME"];
                $companyId = $entity["COMPANY_ID"];
                $currentResponsible = $companies[$companyId]["ASSIGNED_BY_ID"];
                if (in_array($nameGroup, ["Leads", "Deals", "Processes"])
                    && $users[$currentResponsible]["IsMain"]
                    && !$users[$responsibleId]["IsMain"]) {
//                    printf("Skip entity (%s) %d\n", $nameGroup, $entity["ID"]);
                    continue;
                }
                $links[$companyId]["Responsible"][$timeStamp] = $responsibleId;
                if (!isset($links[$companyId]["Contacts"])) {
                    $links[$companyId]["Contacts"] = $companies[$companyId]["CONTACT_IDS"];
                }
            }
        }
//        print_r($stages);
        echo "Links: " . count($links) . "\n";
        foreach ($links as $companyId => $linkData) {
            ksort($linkData["Responsible"]);
            if (empty($linkData["Responsible"])) {
                continue;
            }
//            print_r($linkData["Responsible"]);
            $responsibleId = array_shift($linkData["Responsible"]);
            self::getInstance()->setResponsibleCompany($companyId, $responsibleId);
            if (empty($linkData["Contacts"])) {
                continue;
            }
            foreach ($linkData["Contacts"] as $contactId) {
                self::getInstance()->setResponsibleContact($contactId, $responsibleId);
            }
        }
//        print_r($links);
    }

    /**
     * Вывести неправильные связки
     */
    public static function showWrongContacts(): void
    {
        $wrong = self::getInstance()->getWrongAssignedContacts();
        echo "Wrong: " . count($wrong) . "\n";
        foreach ($wrong as $v) {
            printf("https://crm.highsystem.ru/crm/contact/details/%d/\n", $v);
        }
    }

    /**
     * Привязать контакты к компании
     * @param array $contacts
     * @return void
     */
    private static function bindBulkContactsToCompanies(array &$contacts): void
    {
        foreach ($contacts as $companyId => $contactIds) {
            foreach ($contactIds as $contactId) {
                self::getInstance()->bindContactToCompany($contactId, $companyId);
            }
        }
    }

    /**
     * Привязать все контакты компании основных ответственных к ответственному за компанию
     * @param array $companies
     * @return void
     */
    private static function bindMainContactsToResponsible(array &$companies): void
    {
        if (empty($companies)) {
            return;
        }
        $mainCompanies = self::getInstance()->getMainAssigned();
//        echo "Main Companies: " . count($mainCompanies) . "\n";
        if (empty($mainCompanies)) {
            return;
        }
        foreach ($mainCompanies as $mainCompany) {
            $responsibleId = $companies[$mainCompany]["ASSIGNED_BY_ID"];
            $contacts = $companies[$mainCompany]["CONTACT_IDS"];
            if (empty($contacts)) {
                continue;
            }
            foreach ($contacts as $contactId) {
                self::getInstance()->setResponsibleContact($contactId, $responsibleId);
            }
        }
    }

    /**
     * Привязать все неосновные компании и их контакты к определенному менеджеру
     * @param int   $responsibleId
     * @param array $companies
     * @return void
     */
    private static function bindOtherContactsToResponsible(int $responsibleId, array &$companies): void
    {
        if (empty($companies)) {
            return;
        }
        $otherCompanies = self::getInstance()->getNotMainAssigned();
        if (empty($otherCompanies)) {
            return;
        }
        foreach ($otherCompanies as $otherCompany) {
            self::getInstance()->setResponsibleCompany($otherCompany, $responsibleId);
            $contacts = $companies[$otherCompany]["CONTACT_IDS"];
            if (empty($contacts)) {
                continue;
            }
            foreach ($contacts as $contactId) {
                self::getInstance()->setResponsibleContact($contactId, $responsibleId);
            }
        }
    }

    /**
     * Связать все несвязанные контакты с компаниями
     * @param array $deals
     * @param array $leads
     * @param array $smarts
     * @return void
     */
    private static function bindAllContactsToCompanies(array &$deals, array &$leads, array &$smarts): void
    {
        $allBindingsCompanies = self::getInstance()->combineContactsCompanies([$deals, $leads, $smarts]);
        $toBindContacts = self::getInstance()->getNotBindingContacts($allBindingsCompanies);
        echo "To binding companies: " . count($toBindContacts) . "\n";
        self::bindBulkContactsToCompanies($toBindContacts);
    }

    /**
     * Привязать все контакты к ответственному по компании
     * @param array $companies
     * @return void
     */
    private static function processAllResponsibleCompаnies(array &$companies): void
    {
        foreach ($companies as $company) {
            $responsibleId = $company["ASSIGNED_BY_ID"];
            $contacts = $company["CONTACT_IDS"];
            if (!empty($contacts)) {
                foreach ($contacts as $contactId) {
                    if (!empty($contactId)) {
                        self::getInstance()->setResponsibleContact($contactId, $responsibleId);
                    }
                }
            }
        }
    }

    /**
     * Тест методов
     * @param bool $isLastWeek
     * @return void
     */
    public static function run(bool $isLastWeek = false): void
    {
//        if ($_SERVER["REMOTE_ADDR"] != "176.108.144.17") {
//            die("Fuck off");
//        }
        echo date("d.m.Y H:i:s") . "\n";
        $users = self::getUsers();
        echo "Users: " . count($users) . "\n";
        self::getInstance()->setCachedUsers($users);
        $contacts = self::getInstance()->getContacts();
        echo "Contacts: " . count($contacts) . "\n";
        self::getInstance()->setCachedContacts($contacts);
        $companies = self::getInstance()->getCompanies();
        echo "Companies: " . count($companies) . "\n";
        self::getInstance()->setCachedCompanies($companies);
        $leads = self::getInstance()->getLeads($isLastWeek);
        echo "Leads: " . count($leads) . "\n";
        $deals = self::getInstance()->getDeals($isLastWeek);
        echo "Deals: " . count($deals) . "\n";
        $smarts = [];
        foreach ([174, 141] as $id) {
            $smarts = array_merge($smarts, self::getInstance()->getSmartProcesses($id, $isLastWeek));
        }
        echo "Smarts: " . count($smarts) . "\n";
        echo date("d.m.Y H:i:s") . "\n";
//        die("prepare stop");

        // Причесать все контакты компаний - связать несвязанные
        self::bindAllContactsToCompanies($deals, $leads, $smarts);
        // Привязать все контакты компаний КАМов
//        self::bindMainContactsToResponsible($companies);
        // Отвязать все остальные контакты на ждуна
//        self::bindOtherContactsToResponsible(1599, $companies);
//        self::bindOtherContactsToResponsible(406, $companies);
        // Выбрать первых ответственных за контакты во всех сущностях, кроме проектов
//        foreach ([141] as $id) {
//            $smarts = array_merge($smarts, self::getInstance()->getSmartProcesses($id, $isLastWeek));
//        }
//        $entities = array_merge(["Leads" => &$leads, "Deals" => &$deals, "Processes" => &$smarts]);
//        self::processBulkEntities($entities, $users, $companies);
        // Привязать отвественных за открытые проекты
//        self::processOpenedProjects($users, $companies);
        // Привязать перекупов на Карпова
//        self::bindToKarpov();
        // Привязать все контакты к ответственным за компании
        self::processAllResponsibleCompаnies($companies);
//        die("stop");
        // Показать ошибочные привязки
//        self::showWrongContacts();
        echo date("d.m.Y H:i:s") . "\n";
        echo "process is done";
    }
}
