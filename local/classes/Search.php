<?php
namespace Hs;

class Search {

    public static function onAfterTasksIndexUpdate(\Bitrix\Main\Entity\Event $event)
    {
        $primary = $event->getParameter("primary");
        self::AddTaskToSearchWithPrimary($primary["ID"]);

    }

    public static function AddTaskToSearchWithPrimary($primary){
        $DB = \CDatabase::GetModuleConnection('tasks');
        $res = $DB->query("SELECT TASK_ID FROM b_tasks_search_index WHERE ID=$primary");
        $taskID = $res->Fetch()["TASK_ID"];
        self::AddTaskToSearch($taskID);
    }

    public static function onAfterTasksIndexAdd(\Bitrix\Main\Entity\Event $event)
    {
        $eventFields = $event->getParameter("fields");
        self::AddTaskToSearch($eventFields["TASK_ID"]);
    }

    public static function AddTaskToSearch($taskId){
        $DB = \CDatabase::GetModuleConnection('tasks');

        if (\CModule::IncludeModule("tasks")) {
            $res = \CTasks::GetList(
                ["TITLE" => "ASC"],
                ["ID" => $taskId],
                ["UF_CRM_CONTACT","UF_CRM_COMPANY"]
            );
            if ($task = $res->Fetch()) {
                if (!empty($task["UF_CRM_CONTACT"])) {
                    $query = \Bitrix\Crm\ContactTable::query()
                    ->setSelect(["ID", "NAME", "LAST_NAME", "SECOND_NAME"])
                    ->where("ID", $task["UF_CRM_CONTACT"])
                    ->exec();
                    $contactRes = $query->fetch();
                    $contact = $contactRes;
                    
                    $query = \Bitrix\Crm\FieldMultiTable::query()
                    ->setSelect( ["ELEMENT_ID", "ENTITY_ID", "TYPE_ID", "VALUE"] )
                    ->setFilter( ["ELEMENT_ID" => $contact["ID"],"ENTITY_ID" => "CONTACT","TYPE_ID" => "PHONE"] )
                    ->exec();
                    $phones = $query->fetchAll();

                    foreach ($phones as $key => $phone) {
                        $contact["PHONE".$key] = $phone["VALUE"];
                    }

                }
                if (!empty($task["UF_CRM_COMPANY"])) {
                    $query = \Bitrix\Crm\CompanyTable::query()
                    ->setSelect(["ID","TITLE"])
                    ->where("ID", $task["UF_CRM_COMPANY"])
                    ->exec();
                    $companyRes = $query->fetch();
                    $company = $companyRes;
                    
                    $query = \Bitrix\Crm\RequisiteTable::query()
                    ->setSelect(["NAME","RQ_INN"])
                    ->setFilter(["ENTITY_TYPE_ID" => 4, "ENTITY_ID" => $task["UF_CRM_COMPANY"]])
                    ->exec();
                    $companyReq = $query->fetch();
                    if ($companyReq) {
                        $company["NAME"] = $companyReq["NAME"];
                        $company["RQ_INN"] = $companyReq["RQ_INN"];
                    }
                }
            }
        }
        //$word = $arFields["TITLE"].' ';
        $word = '';
        foreach ($contact as $cw) {
            if (!empty($cw)) {
                $word .= $cw.' ';
            }
        }
        foreach ($company as $cw) {
            if (!empty($cw)) {
                $word .= $cw.' ';
            }
        }
        if (!empty($word)) {
            $DB->query("UPDATE b_tasks_search_index SET SEARCH_INDEX='$word' WHERE TASK_ID=$taskId");
        }
    }
}
?>