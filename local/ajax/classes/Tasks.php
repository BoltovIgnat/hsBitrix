<?php
namespace Hs\Ajax;

class Tasks
{
    public static function deletePing(){
        $taskId = $_REQUEST["id"];
        if (\Hs\Tasks::deletePings($taskId)) {
            echo json_encode(["pingDeleted" => $taskId]);
        };
    }
}
?>