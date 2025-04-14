<?
define("STOP_STATISTICS", true);
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Sale,
    Bitrix\Main\Loader,
    Dbbo\Sportzaniashop\Internal\Element;

Loader::includeModule('crm');
Loader::includeModule('bizproc');

if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] == 'updateContact' && check_bitrix_sessid()) {
	$APPLICATION->RestartBuffer();
	header('Content-Type: text/json; charset='.LANG_CHARSET);
        
        foreach($_POST['fields'] as $v) {
            $field[$v['name']] = $v['value'];
        }
        
        $entity = new \CCrmContact(false);
        $entity->Update($_POST['id'], $field);

		\CCrmBizProcHelper::AutoStartWorkflows(
			\CCrmOwnerType::Contact,
			$_POST['id'],
			\CCrmBizProcEventType::Edit,
			$errors
		);

		\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Contact, $_POST['id']);

        echo json_encode(['result' => 'ok']);
	die();
}