<?
use Bitrix\Main\Loader;

define("STOP_STATISTICS", true);
define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);
$data = file_get_contents("php://input");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$APPLICATION->RestartBuffer();
header('Content-Type: text/json; charset='.LANG_CHARSET);
?>
<?
Loader::IncludeModule('crm');
Loader::IncludeModule('iblock');
Loader::includeModule('sale');
Loader::includeModule('catalog');

$success = true;
$error = '';
$data = json_decode($data, true);
$pass = 'cf2b297ab7de17990206b684e638093b';

$entity = $data['entity'];
$entityId = $data['entityId'];
$products = $data['products'];
$token = $data['token'];
$iblockId = 24;
$logIblockId = 37;
$found = [];
$notFound = [];
$log = CIBlockElement::GetList(['ID' => 'DESC'], ['IBLOCK_ID' => $logIblockId], false, false, ['PROPERTY_NUMBER'])->GetNext();
$status = 1;
$entityText = $entity ? $data['entity'] : '';

if(!$token || $token != $pass) {
	$error = 'Ошибка при выполнении запроса';
}

if(!$entity) {
	$error = 'Не указан id entity';
}

if(!$entityId) {
	$error = 'Не указан id entityId';
}

if(!$error) {
	$products = $data['products'];

	foreach($products as $product) {
		$element = CIBlockElement::GetList(
			[],
			[
				'IBLOCK_ID' => $iblockId,
				'PROPERTY_125' => $product['artnumber']
			]
		)->GetNext();

		if($element) {
			$add = [
				'OWNER_TYPE' => $entity == 'lead' ? 'L' : 'D',
				'OWNER_ID' => $entityId,
				'PRODUCT_ID' => $element['ID'],
				'PRODUCT_NAME' => $element['NAME'],
				'QUANTITY' => $product['quantity'],
				'PRICE' => $product['price'],
				'PRICE_EXCLUSIVE' => $product['price'],
				'PRICE_NETTO' => $product['price'],
				'PRICE_BRUTTO' => $product['price'],
				'MEASURE_CODE' => 796,
				'CURRENCY_ID' => 'RUB',
				'CUSTOMIZED' => 'Y'
			];
			\CAllCrmProductRow::Add($add, false, false);
			
			$found[] = $product;
		} else {
			$notFound[] = $product;
		}
	}
	
	if(!$notFound) {
		$status = 5;
	} else {
		$status = 2;
	}
}

if($error) {
	$success = false;
	$status = 0;
}


if($data) {
	$el = new CIBlockElement;
	
	$arFields = [
		'NAME' => 'Добавление товаров ' . $entityText . ' ' . $entityId,
		'IBLOCK_ID' => $logIblockId,
		'PROPERTY_VALUES' => [
			'NUMBER' => $log ? ($log['PROPERTY_NUMBER_VALUE'] + 1) : 0,
			'DATE' => new Bitrix\Main\Type\DateTime(),
			'ENTITY' => $entity,
			'ENTITY_ID' => $entityId,
			'DATA' => json_encode($products),
			'NOT_FOUND' => $notFound ? json_encode($notFound) : '',
			'STATUS' => $status
		]
	];

	$el->Add($arFields);
}

echo json_encode([
    'success' => $success,
	'error' => $error
]);

die();