<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if (SITE_TEMPLATE_ID != "landing24")
{
	$APPLICATION->IncludeComponent("bitrix:crm.shop.page.controller", "", array(
		"CONNECT_PAGE" => "N",
		"ADDITIONAL_PARAMS" => array(
			"stores" => array(
				"IS_ACTIVE" => true
			)
		)
	));
}

$APPLICATION->IncludeComponent(
	"bitrix:landing.start", 
	".default", 
	array(
		"SEF_FOLDER" => "/shop/stores/",
		"SEF_MODE" => "Y",
		"COMPONENT_TEMPLATE" => ".default",
		"TYPE" => "STORE",
		"EDIT_FULL_PUBLICATION" => "Y",
		"STRICT_TYPE" => "N",
		"SHOW_MENU" => "N",
		"REOPEN_LOCATION_IN_SLIDER" => "N",
		"TILE_LANDING_MODE" => "edit",
		"TILE_SITE_MODE" => "list",
		"EDIT_PANEL_LIGHT_MODE" => "N",
		"EDIT_DONT_LEAVE_FRAME" => "N",
		"DRAFT_MODE" => "N",
		"SEF_URL_TEMPLATES" => array(
			"sites" => "",
			"site_show" => "site/#site_show#/",
			"site_edit" => "site/edit/#site_edit#/",
			"site_design" => "site/design/#site_edit#/",
			"site_settings" => "site/settings/#site_edit#/",
			"site_master" => "site/master/#site_edit#/",
			"site_contacts" => "site/contacts/#site_edit#/",
			"site_domain" => "site/domain/#site_edit#/",
			"site_domain_switch" => "site/domain_switch/#site_edit#/",
			"site_cookies" => "site/cookies/#site_edit#/",
			"landing_edit" => "site/#site_show#/#landing_edit#/",
			"landing_design" => "site/#site_show#/design/#landing_edit#/",
			"landing_view" => "site/#site_show#/view/#landing_edit#/",
			"landing_settings" => "site/#site_show#/settings/#landing_edit#/",
			"domains" => "domains/",
			"domain_edit" => "domain/edit/#domain_edit#/",
			"roles" => "roles/",
			"role_edit" => "role/edit/#role_edit#/",
			"folder_edit" => "folder/edit/#folder_edit#/",
		)
	),
	false
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>