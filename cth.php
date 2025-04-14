<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>

<?
$APPLICATION->IncludeComponent('highsystem:companytoholding',
	isset($componentData['template']) ? $componentData['template'] : '',
	$componentParams,
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
);
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>