<?php
namespace Hs\Ajax;

class Project
{
    public static function contactForProjectStartBP(){
        $cid = $_REQUEST["cid"];
        $uid = $_REQUEST["uid"];
        $eid = $_REQUEST["eid"];

        \CBPDocument::StartWorkflow(
            486,
            array("crm", "Bitrix\Crm\Integration\BizProc\Document\Dynamic", "DYNAMIC_174_".$eid),
            [ "TargetUser" => "user_".$uid,  "contactId" => $cid ],
            $arErrorsTmp
        );
    }
}
?>