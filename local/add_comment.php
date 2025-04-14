<?
define("STOP_STATISTICS", true);
define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

use Bitrix\Main\Loader;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$APPLICATION->RestartBuffer();
header('Content-Type: text/json; charset='.LANG_CHARSET);
?>
<?

Loader::IncludeModule('crm');

$success = true;
$error = '';
$data = $_GET;
$pass = '36398745a80472154c89882cc86a8dd0';
$tokenStr = "cf2b297ab7de17990206b684e638093b";

$dealId = intval($_GET['dealId']);
$text = mb_convert_encoding(urldecode(trim($_GET['text'])), 'utf8', 'cp1251');

$text = str_replace('1C_UT', 'UT_CRM', $text);

$passGet = trim($_GET['pass']);

\Bitrix\Main\Diag\Debug::dumpToFile($passGet, '$passGet');
\Bitrix\Main\Diag\Debug::dumpToFile($dealId, '$dealId');
\Bitrix\Main\Diag\Debug::dumpToFile($text, '$text');

if(!$pass || $pass != $passGet) {
	$error = 'Ошибка при выполнении запроса';
}

if(!$dealId) {
	$error = 'Не указан id сделки';
}

if(!$text) {
	$error = 'Не указан текст комментария';
}

if(!$error) {
	$arFields = [
		'UF_CRM_1667466360305' => $text
	];
	$entity = new \CCrmDeal(false);
	$resId = $entity->Update($dealId, $arFields, true, true, array('DISABLE_USER_FIELD_CHECK' => true));

	if(!$resId) {
		$error = 'Ошибка добавления';
	}
}

$token = $data['token'];

if ($token == $tokenStr) {
//	prToFile($data);
}

if($error) {
	$success = false;
}

echo json_encode([
    'success' => $success,
	'error' => $error
]);

die();