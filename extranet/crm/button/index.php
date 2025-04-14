<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/button/index.php");
$APPLICATION->SetTitle(GetMessage("CRM_TITLE"));
?><?$APPLICATION->IncludeComponent(
	"bitrix:crm.button",
	".default",
	Array(
		"SEF_MODE" => "Y",
		"PATH_TO_CONTACT_SHOW" => "/extranet/crm/contact/show/#contact_id#/",
		"PATH_TO_CONTACT_EDIT" => "/extranet/crm/contact/edit/#contact_id#/",
		"PATH_TO_COMPANY_SHOW" => "/extranet/crm/company/show/#company_id#/",
		"PATH_TO_COMPANY_EDIT" => "/extranet/crm/company/edit/#company_id#/",
		"PATH_TO_DEAL_SHOW" => "/extranet/crm/deal/show/#deal_id#/",
		"PATH_TO_DEAL_EDIT" => "/extranet/crm/deal/edit/#deal_id#/",
		"PATH_TO_USER_PROFILE" => "/extranet/company/personal/user/#user_id#/",
		"ELEMENT_ID" => $_REQUEST["id"],
		"SEF_FOLDER" => "/extranet/crm/button/",
		"SEF_URL_TEMPLATES" => Array(
			"list" => "list/",
			"edit" => "edit/#id#/",
		),
		"VARIABLE_ALIASES" => Array(
			"list" => Array(),
			"edit" => Array(),
		)
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>