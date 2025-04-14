<?php
namespace Hs;
    class Tasks {
        public static function deletePings($taskId) {
            global $DB;
            \Bitrix\Main\Loader::includeModule('tasks');
            $query = \Bitrix\Tasks\Internals\TaskTable::query()
            ->setSelect(["CLOSED_BY","RESPONSIBLE_ID","TASK_CONTROL"])
            ->setFilter(["ID" => $taskId])
            ->exec();
            $task = $query->fetch();
            $results = $DB->Query("SELECT UF_CRM_TYPE FROM b_uts_tasks_task WHERE VALUE_ID='$taskId' ");
            $crmtype = $results->Fetch()["UF_CRM_TYPE"];

            if ($task["CLOSED_BY"] == 406) {
                $correct = true;
            }
            if ($task["CLOSED_BY"] == $task["RESPONSIBLE_ID"] && $task["TASK_CONTROL"] == "N" && !empty($crmtype)) {
                $correct = true;
            }
    
            if ($correct === true) {
                \Bitrix\Main\Loader::includeModule('im');
                $query = \Bitrix\Im\Model\MessageParamTable::query()
                    ->setSelect(["MESSAGE_ID"])
                    ->setFilter(["PARAM_VALUE" => "taskId", "PARAM_VALUE" => $taskId])
                    ->exec();
                $params = $query->fetchAll();
    
                foreach ($params as $param) {
                    \Bitrix\Im\Model\MessageTable::update($param["MESSAGE_ID"],["NOTIFY_READ" => "Y"]);
                } 
    
                $query = \Bitrix\Tasks\Internals\Counter\CounterTable::query()
                ->addSelect("ID")
                ->setFilter(["TYPE" => ['my_new_comments','project_comments','auditor_muted_new_comments'],"TASK_ID" => $taskId])
                ->exec();
                $scores = $query->fetchAll();
                
                foreach ($scores as $scorer) {
                    \Bitrix\Tasks\Internals\Counter\CounterTable::delete($scorer["ID"]);
                }
                return true;
            }
            return false;
        }    
    }
?>