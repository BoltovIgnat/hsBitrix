<?
define("STOP_STATISTICS", true);
define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
?>
<?
$data = json_decode(file_get_contents("php://input"), true);

include 'local/api/Main.php';
include 'local/api/Deal.php';
include 'local/api/Lead.php';
include 'local/api/User.php';
include 'local/api/Contact.php';

$class = new Dbbo\Jivo\Api\Main($data);
$class->SetDataField('leadFieldChatId', 'UF_CRM_1562012924');
$class->SetDataField('dealFieldChatId', 'UF_CRM_5D1AFD5776AA7');
$class->SetDataField('assignedDefault', '16');
$class->SetDataField('sourceId', '3');
$class->SetDataField('leadFieldYmId', 'UF_CRM_1562002256');
$class->SetDataField('leadFieldGaId', 'UF_CRM_1562002127');
$class->SetDataField('leadFieldUrl', 'UF_CRM_1562002225');

if($data['event_name']) {
    $class->Action($data['event_name']);
}

echo json_encode([
    'result' => 'ОК'
]);