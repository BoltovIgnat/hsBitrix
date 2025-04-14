<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

$siteID = isset($_REQUEST['site'])? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if($siteID !== '')
{
	define('SITE_ID', $siteID);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('crm') || !CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid())
{
	die();
}

global $APPLICATION;
Header('Content-Type: text/html; charset='.LANG_CHARSET);
$APPLICATION->ShowAjaxHead();

if(\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
{
	$APPLICATION->IncludeComponent(
		'bitrix:app.placement',
		'menu',
		array(
			'PLACEMENT' => "CRM_COMPANY_LIST_MENU",
			"PLACEMENT_OPTIONS" => array(),
			'INTERFACE_EVENT' => 'onCrmCompanyMenuInterfaceInit',
			'MENU_EVENT_MODULE' => 'crm',
			'MENU_EVENT' => 'onCrmCompanyListItemBuildMenu',
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
}

$componentData = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : [];
$componentParams = [];
if (isset($componentData['signedParameters']))
{
	$componentParams = \CCrmInstantEditorHelper::unsignComponentParams(
		(string)$componentData['signedParameters'],
		'crm.company.listnperm'
	);
	if (is_null($componentParams))
	{
		ShowError('Wrong component signed parameters');
		die();
	}
}
elseif (isset($componentData['params']) && is_array($componentData['params']))
{
	ShowError('Component params must be signed');
	die();
}

//Security check
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$filter = isset($componentParams['INTERNAL_FILTER']) && is_array($componentParams['INTERNAL_FILTER'])
	? $componentParams['INTERNAL_FILTER'] : array();

$isPermitted = false;
if(isset($filter['ID']) && $filter['ID'] > 0)
{
	$isPermitted = CCrmCompany::CheckReadPermission($filter['ID'], $userPermissions);
}
elseif(isset($filter['LEAD_ID']) && $filter['LEAD_ID'] > 0)
{
	$isPermitted = CCrmLead::CheckReadPermission($filter['LEAD_ID'], $userPermissions);
}
elseif(isset($filter['ASSOCIATED_CONTACT_ID']) && $filter['ASSOCIATED_CONTACT_ID'] > 0)
{
	$isPermitted = CCrmContact::CheckReadPermission($filter['ASSOCIATED_CONTACT_ID'], $userPermissions);
}
if (!$isPermitted)
{
	$isPermitted = \Bitrix\Crm\Service\Container::getInstance()->getParentFieldManager()->tryPrepareListComponentParametersWithParentItem(
		\CCrmOwnerType::Company,
		$componentParams
	);
}
//hsfix start
/* if(!$isPermitted)
{
	die();
} */
//hsfix end

//For custom reload with params
$ajaxLoaderParams = array(
	'url' => '/local/components/bitrix/crm.company.listnperm/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
	'method' => 'POST',
	'dataType' => 'ajax',
	'data' => array('PARAMS' => $componentData)
);

//Force AJAX mode
$componentParams['AJAX_MODE'] = 'Y';
$componentParams['AJAX_OPTION_JUMP'] = 'N';
$componentParams['AJAX_OPTION_HISTORY'] = 'N';
$componentParams['AJAX_LOADER'] = $ajaxLoaderParams;
$componentParams['HID'] = $_SESSION["holdingID"];

//Enable sanitaizing
//$componentParams['IS_EXTERNAL_CONTEXT'] = 'Y';

?>
<?if ($_SESSION["holdingID"]):?>	
<div class="tabs">
	<span class="tabbutton tab1display hover">Текущие компании</span>
	<span class="tabbutton tab2display">Привязать комании</span>

	<div class="tab1 active">
<?
$APPLICATION->IncludeComponent('bitrix:crm.company.listnperm',
	isset($componentData['template']) ? $componentData['template'] : '',
	$componentParams,
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
);

?>
	</div>

	<div class="tab2">
<?
if ($_REQUEST["data"]) {
    $componentData["search"] = $_REQUEST["data"];
    $componentData["hid"] = $_SESSION["holdingID"];
}
$APPLICATION->IncludeComponent('highsystem:companytoholding',
	isset($componentData['template']) ? $componentData['template'] : '',
	$componentParams,
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
);?>

	</div>
	
</div>

<style>
	.tabs .active {
		display: block;
	}
	.tab1,.tab2 {
		display: none;
		margin-top:30px;
	}
	.tabbutton {
		margin-right:20px;
		padding:10px 10px;
		cursor:pointer;
		position: relative;
		display: inline-block;
		color: #525c68;
		font-size:14px;
		font-family: "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
		font-weight:600;
	}
	.hover {
		outline:2px solid #525c68
	}
	.tabbutton:hover {
		outline:2px solid #525c68
	}
</style>
<script>
    $(".tab1display").click(function(e) { 
		$(this).addClass('hover');
		$(".tab2display").removeClass('hover');
		$('.tab1').addClass('active');
		$('.tab2').removeClass('active');
	});
	$(".tab2display").click(function(e) {
		$(this).addClass('hover');
		$(".tab1display").removeClass('hover');
		$('.tab2').addClass('active');
		$('.tab1').removeClass('active');
	});
</script>
<?else:?>
<?
	$APPLICATION->IncludeComponent('bitrix:crm.company.listnperm',
		isset($componentData['template']) ? $componentData['template'] : '',
		$componentParams,
		false,
		array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
	);
?>
<?endif;?>

<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
die();
?>