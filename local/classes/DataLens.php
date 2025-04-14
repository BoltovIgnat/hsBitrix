<?php
namespace Hs;
    class DataLens {

        private function getConnection() {
            $connection = pg_connect("host=192.168.25.53 port=5432 dbname=dl user=alex password=KeksMP44p");
            return $connection;
        }

        private function exportCrmSource() {
            $dbconn = $this->getConnection();
            $table = "sources";
            if ($dbconn) {
                $query = \Bitrix\Crm\StatusTable::query()
                ->setSelect(["*"])
                ->setFilter(["ENTITY_ID" => "SOURCE"])
                ->exec();
                $resSources = $query->fetchAll();

                foreach ($resSources as $source) {
                    $data = array(
                        "id" => $source["STATUS_ID"],
                        "name" => $source["NAME"],
                    );

                    $selectfields = ["id" => $source["STATUS_ID"]];
                    $records = pg_select($dbconn,$table,$selectfields);
                    if (empty($records)) {
                        $result = pg_insert($dbconn, $table, $data);
                    }
                    else {
                        $result = pg_update($dbconn, $table, $data, $selectfields);
                    }
                }

                pg_close($dbconn);
            }
        }

        private function exportCrmStatus() {
            $dbconn = $this->getConnection();
            $table = "statuses";
            if ($dbconn) {
                $query = \Bitrix\Crm\StatusTable::query()
                ->setSelect(["*"])
                ->setFilter(["ENTITY_ID" => "STATUS"])
                ->exec();
                $resStatuses = $query->fetchAll();

                foreach ($resStatuses as $status) {
                    $data = array(
                        "id" => $status["STATUS_ID"],
                        "name" => $status["NAME"],
                    );

                    $selectfields = ["id" => $status["STATUS_ID"]];
                    $records = pg_select($dbconn,$table,$selectfields);
                    if (empty($records)) {
                        $result = pg_insert($dbconn, $table, $data);
                    }
                    else {
                        $result = pg_update($dbconn, $table, $data, $selectfields);
                    }
                }

                pg_close($dbconn);
            }
        }

        private function exportDepartment() {
            $dbconn = $this->getConnection();
            $table = "departments";
            if ($dbconn) {
                $arFilter = Array('IBLOCK_ID'=>1, 'GLOBAL_ACTIVE'=>'Y');
                $db_list = \CIBlockSection::GetList(Array("ID"=>"DESC"), $arFilter, false, Array("ID", "NAME", "UF_HEAD"));
                while($ar_result = $db_list->GetNext())
                {
                    $departments[]= $ar_result;
                }

                foreach($departments as $department) {
                    $data = array(
                        "id" => $department["ID"],
                        "name" => $department["NAME"],
                        "rop_id" => $department["UF_HEAD"],
                    );

                    $selectfields = ["id" => $department["ID"]];
                    $records = pg_select($dbconn,$table,$selectfields);
                    if (empty($records)) {
                        $result = pg_insert($dbconn, $table, $data);
                    }
                    else {
                        $result = pg_update($dbconn, $table, $data, $selectfields);
                    }
                }

                pg_close($dbconn);
            }
        }

        private function exportUser() {
            $dbconn = $this->getConnection();
            $table = "users";
            if ($dbconn) {
                $arFilter = Array('IBLOCK_ID'=>1, 'GLOBAL_ACTIVE'=>'Y');
                $db_list = \CIBlockSection::GetList(Array("ID"=>"DESC"), $arFilter, false, Array("ID", "NAME", "UF_HEAD" ));
                while($ar_result = $db_list->GetNext())
                {
                    $department[$ar_result["ID"]]["UF_HEAD"] = $ar_result["UF_HEAD"];
                }
                
                $rsUsers = \CUser::GetList(($by="ID"), ($order="desc"), ["ACTIVE" => "Y"],["SELECT" =>["UF_DEPARTMENT"],"FIELDS" =>["ID","NAME","LAST_NAME","SECOND_NAME"]]);
                while($userRes = $rsUsers->GetNext()) {
                    $userRes["UF_HEAD"] = $department[$userRes["UF_DEPARTMENT"][0]]["UF_HEAD"];
                    $users[] = $userRes;
                } 

                foreach ($users as $user) {
                    $data = array(
                        "id" => $user["ID"],
                        "last_name" => $user["LAST_NAME"],
                        "name" => $user["NAME"],
                        "second_name" => $user["SECOND_NAME"],
                        "department_id" => $user["UF_DEPARTMENT"][0],
                        "rop_id" => $user["UF_HEAD"]
                    );

                    $selectfields = ["id" => $user["ID"]];
                    $records = pg_select($dbconn,$table,$selectfields);
                    if (empty($records)) {
                        $result = pg_insert($dbconn, $table, $data);
                    }
                    else {
                        $result = pg_update($dbconn, $table, $data, $selectfields);
                    }
                }

                pg_close($dbconn);
            }
        }

        private function exportLead() {
            $dbconn = $this->getConnection();
            $table = "leads";
            if ($dbconn) {
                $date_from = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime("-90 day"));
                $query = \Bitrix\Crm\LeadTable::query()
                ->setSelect(["*"])
                ->setFilter([ ">DATE_CREATE" => $date_from])
                ->exec();
                $resLeads = $query->fetchAll();
            
                foreach ($resLeads as $lead) {
                    $data = array(
                        "b24_id" => $lead["ID"],
                        "title" => $lead["TITLE"],
                        "source_id" => $lead["SOURCE_ID"],
                        "status_id" => $lead["STATUS_ID"],
                        "assigned_by_id" => $lead["ASSIGNED_BY_ID"],
                        "date_create" => $lead["DATE_CREATE"]->format("Y-m-d H:i:s"),
                    );

                    $selectfields = ["b24_id" => $lead["ID"]];
                    $records = pg_select($dbconn,$table,$selectfields);
                    if (empty($records)) {
                        $result = pg_insert($dbconn, $table, $data);
                    }
                    else {
                        $result = pg_update($dbconn, $table, $data, $selectfields);
                    }
                }

                pg_close($dbconn);                
            }

        }

        public static function exportLeads(){
            (new \Hs\DataLens)->exportLead();
        }

        public static function exportUsers(){
            (new \Hs\DataLens)->exportUser();
        }

        public static function exportDepartments(){
            (new \Hs\DataLens)->exportDepartment();
        }

        public static function exportCrmStatuses(){
            (new \Hs\DataLens)->exportCrmStatus();
        }

        public static function exportCrmSources(){
            (new \Hs\DataLens)->exportCrmSource();
        }

    }
?>