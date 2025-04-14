<?
define("STOP_STATISTICS", true);
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Sale,
    Bitrix\Main\Loader,
    Dbbo\Crm\Lead;

if($_POST['action'] == 'save' && check_bitrix_sessid()) {
	$APPLICATION->RestartBuffer();
	header('Content-Type: text/json; charset='.LANG_CHARSET);
	$data = $_POST;

	$update = [
		'SKIP_EVENT' => 'Y'
	];

	if($data['addressDeliveryValue'] && $data['addressDeliveryJson']) {
		$update[$data['addressDeliveryJson']] = json_encode($data['data'], JSON_UNESCAPED_UNICODE);
	}

	if($data['addressValue'] && $data['addressAll']) {
		$update[$data['addressAll']] = json_encode($data['data'], JSON_UNESCAPED_UNICODE);
	}

    if ($data['id']) {
        Lead::Update($data['id'], $update);
    }

	echo json_encode(['result' => 'ok']);
	die();
}