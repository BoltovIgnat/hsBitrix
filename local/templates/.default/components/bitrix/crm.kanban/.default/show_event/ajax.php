<?
define("STOP_STATISTICS", true);
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('crm');

$items = $_POST['items'] ?: [];
$type = $_POST['type'] ?: 'DEAL';

if(!$items) {
	return;
}

$ids = [];
$result = [];
$companyIds = [];
$company = [];

$db = \CCrmActivity::GetList(
	[
		'OWNER_ID' => 'ASC',
		'DEADLINE' => 'ASC'
	],
	[
		'OWNER_ID' => $items,
		'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($type),
		'CHECK_PERMISSIONS' => 'N',
		'COMPLETED' => 'N',
		'TYPE_ID' => 6
	],
	false, false,
	[
		'ID',
		'OWNER_ID',
		'DEADLINE',
		'START_TIME',
		'SUBJECT',
		'TYPE_ID'
	]
);
while($res = $db->Fetch()) {
	$result[] = $res;
}

$prevId = 0;
$uniquieResult = [];
$datetime = new Bitrix\Main\Type\DateTime();
$date = new DateTime();

foreach($result as $resItem) {
	if($resItem['OWNER_ID'] == $prevId) {
		continue;
	}

	$today = "Сегодня, ". "H:i";
	$formatLetter = 'Q';
	$sign = '';
	
	$datetime1 = new Bitrix\Main\Type\DateTime($resItem['DEADLINE']);

	$date2 = new DateTime($datetime1->format('Y-m-d H:i:s'));
	$sinceTime = $date->diff($date2);
	$min = ($sinceTime->days * 24 * 60) + ($sinceTime->h * 60) + $sinceTime->i;
	$hours = $sinceTime->h;
	$days = $sinceTime->days;
	
	if($datetime1->getTimestamp() < $datetime->getTimestamp() && $days >= 0) {
		$letter = 'Q';
		if($days == 0 && $hours >= 1) {
			$sign = '–';
			$letter = 'Hago';
		} elseif($days == 0 && $hours == 0) {
			$sign = '–';
			$letter = 'iago';
		} else {
			$sign = '–';
			$formatLetter = 'Q';
		}
		$today = $sign . $letter;
		$resItem['CLASS'] = 'ui-label-danger';
	} elseif($datetime1->getTimestamp() > $datetime->getTimestamp() && $days > 14) {
		$formatLetter = 'd F, H:s';
		$resItem['CLASS'] = 'ui-label-tag-light ui-label-fill';
	} elseif($datetime1->getTimestamp() > $datetime->getTimestamp() && $days == 1) {
		$formatLetter = 'd F, H:s';
		$resItem['CLASS'] = 'ui-label-blue';
	} elseif($datetime1->getTimestamp() > $datetime->getTimestamp() && $days >= 2 && $days <= 14) {
		$formatLetter = 'd F, H:s';
		$resItem['CLASS'] = 'ui-label-blue';
	} elseif($datetime1->getTimestamp() > $datetime->getTimestamp() && $days == 0 && $min <= 5) {
		$resItem['CLASS'] = 'ui-label-orange';
	} elseif($datetime1->getTimestamp() > $datetime->getTimestamp() && $days == 0) {
		$resItem['CLASS'] = 'ui-label-green';
	}

	$resItem['DEADLINE_FORMAT'] = FormatDate(array(
		"tommorow" => "Завтра, ". "H:i",
		"today" => $today,
		 "" => $sign . $formatLetter,
		), $datetime1->getTimestamp() + CTimeZone::GetOffset()
	);
	$resItem['DEADLINE_FORMAT'] = str_replace([
		'-',
		'назад',
		'минуту',
		'минуты',
		'минут'
	], [
		'',
		'',
		'мин',
		'мин',
		'мин'
	], $resItem['DEADLINE_FORMAT']);
	
	$uniquieResult[$resItem['OWNER_ID']][] = $resItem;
	
	$prevId = $resItem['OWNER_ID'];
}

if($type == 'DEAL') {
	$dbRes = \CCrmDeal::GetListEx(
		[],
		[
			'CHECK_PERMISSION' => 'N',
			'ID' => $items
		],
		false,
		false,
		[
			'ID',
			'COMPANY_ID'
		]
	);
} else {
	$dbRes = \CCrmLead::GetListEx(
	[],
	[
		'CHECK_PERMISSION' => 'N',
		'ID' => $items
	],
	false,
	false,
	[
		'ID',
		'COMPANY_ID'
	]
);
}
while($res = $dbRes->Fetch()) {
	if($res['COMPANY_ID']) {
		$companyIds[] = $res['COMPANY_ID'];
	}
}

if($companyIds) {
	$db = \Bitrix\Crm\CompanyTable::getList([
		'filter' => [
			'ID' => $companyIds
		]
	]);
	while($res = $db->Fetch()) {
		if($res['COMPANY_TYPE']) {
			$company[$res['ID']] = $res['COMPANY_TYPE'];
		}
	}
}

echo CUtil::PhpToJSObject(['items' => $uniquieResult, 'company' => $company]);