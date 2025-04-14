<?
$aMenuLinks = Array(
	Array(
		"CRM", 
		"/crm/", 
		Array(), 
		Array(), 
		"CBXFeatures::IsFeatureEnabled('crm') && CModule::IncludeModule('crm') && CCrmPerms::IsAccessEnabled()" 
	),
	Array(
		"Dashboard", 
		"/dashboard/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		"Отчеты", 
		"/otchety/", 
		Array(), 
		Array(), 
		"" 
	)
);
?>