<?
define('STOP_STATISTICS', true);
define('PUBLIC_AJAX_MODE', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

$shopId = htmlspecialchars($_GET['shopID']);

if($shopId) {
	$hideMenu = [
		'crm_catalogue' => [
			'HIDE' => 'Y',
			'GROUP_ID' => 30,
			'CHILDS' => [
				'/crm/catalog/',
				'/shop/documents/'
			]
		],
		'crm_clients' => [
			'HIDE' => 'Y',
			'GROUP_ID' => [31, 42],
			'RULES' => [
				31 => [
					'/services/contact_center/'
				],
				42 => [
					'/crm/contact/list/',
					'/crm/company/list/'
				]
			]
		],
		'crm_sales' => [
			'GROUP_ID' => 32,
			'HIDE' => 'Y',
			'CHILDS' => [
				'/crm/type/31/list/category/0/',
				'/crm/quote/kanban/',
				'/saleshub/'
			]
		],
		'crm_analytics' => [
			'GROUP_ID' => 33,
			'LINK' => '/report/analytics/',
			'HIDE' => 'Y',
			'CHILDS' => [
				'/report/telephony/',
				'/services/contact_center/dialog_statistics/',
				'/crm/tracking/',
				'/crm/reports/report/'
			]
		],
		'crm_integrations' => [
			'GROUP_ID' => 34,
			'HIDE' => 'Y',
			'CHILDS' => [
				'/telephony/',
				'/crm/button/',
				'/crm/webform/',
				'/onec/',
				'/devops/'
			]
		],
		'crm_settings' => [
			'GROUP_ID' => 35,
			'HIDE' => 'Y',
			'CHILDS' => [
				'/crm/configs/',
				'/crm/configs/mycompany/',
				'/crm/configs/perms/',
				'/crm/type/'
			]
		],
		'menu_crm_stream' => [
			'GROUP_ID' => 40,
			'HIDE' => 'Y',
			'LINK' => '/crm/stream/'
		],
		'menu_crm_activity' => [
			'GROUP_ID' => 38,
			'HIDE' => 'Y',
			'LINK' => '/crm/activity/'
		],
		'menu_crm_event' => [
			'GROUP_ID' => 37,
			'HIDE' => 'Y',
			'LINK' => '/crm/events/'
		],
		'menu_crm_recycle_bin' => [
			'GROUP_ID' => 36,
			'HIDE' => 'Y',
			'LINK' => '/crm/recyclebin/'
		],
		'menu_crm_start' => [
			'GROUP_ID' => 39,
			'HIDE' => 'Y',
			'LINK' => '/crm/start/'
		]
	];
	$arGroups = $USER->GetUserGroupArray();

	foreach($hideMenu as $key => $item) {
		if(is_array($item['GROUP_ID'])) {
			$hideMenu[$key]['CHILDS'] = [];
			foreach($item['GROUP_ID'] as $groupId) {
				if(!in_array($groupId, $arGroups)) {
					$hideMenu[$key]['CHILDS'] = array_merge($hideMenu[$key]['CHILDS'], $item['RULES'][$groupId]);
				} else {
					$hideMenu[$key]['HIDE'] = 'N';
				}
			}
		} else {
			if($item['GROUP_ID'] && in_array($item['GROUP_ID'], $arGroups)) {
				unset($hideMenu[$key]);
			}
		}
	}
}

header('Content-Type: application/json');
if($hideMenu && !$USER->IsAdmin()) {
	die(json_encode(array('status' => 'CUSTOM', 'menu' => $hideMenu)));
}
die(json_encode(array('status' => 'OK')));