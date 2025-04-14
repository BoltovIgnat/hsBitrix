<?
define("STOP_STATISTICS", true);
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;

Loader::includeModule('bizproc');

$result = [
	'result' => false,
	'error' => ''
];

if($_POST['action'] == 'startWorkflow' && check_bitrix_sessid()) {
	$APPLICATION->RestartBuffer();
	header('Content-Type: text/json; charset='.LANG_CHARSET);

	\CBPDocument::StartWorkflow(
		intval($_POST['workflowId']),
		array("crm", "CCrmDocumentLead", 'LEAD_' . intval($_POST['id'])),
		array("TargetUser" => "user_".intval($GLOBALS["USER"]->GetID())),
		$arErrorsTmp
	);

	if($arErrorsTmp) {
		foreach ($arErrorsTmp as $e)
			$result['error'] .= "[".$e["code"]."] ".$e["message"];
	} else {
		$result['result'] = true;
	}

    echo json_encode($result);
	die();
}