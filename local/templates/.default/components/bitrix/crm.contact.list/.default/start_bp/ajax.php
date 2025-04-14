<?
define("STOP_STATISTICS", true);
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$action = $_POST['action'];

if($action == 'add-agent') {
	$userId = intval($_POST['userId']);
	$targetUserId = $USER->GetID();
	$company = $_POST['company'];
	$workflowId = 168;

	$filename = $userId. "-" .randString(7) . ".txt";
	$path = \Bitrix\Main\Application::getDocumentRoot() . "/local/php_interface/logs/" . $filename;
	file_put_contents($path, serialize($company));
	
	\CAgent::AddAgent("\Dbbo\Agent\AgentLead::AgentLeadGenerator(".$workflowId.", ".$userId.", ".$targetUserId.", 'Contact', '".$filename."');", '', 'Y', 2600, '', 'Y', ConvertTimeStamp(time() + CTimeZone::getOffset(), 'FULL'));
	
	echo json_encode([
		'result' => 'ok'
	]);

}