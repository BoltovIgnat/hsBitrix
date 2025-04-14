<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$group = $USER->GetUserGroupArray();

if(!defined('ALLOW_CHANGE_ASSIGNED_GROUP_ID')) {
	define('ALLOW_CHANGE_ASSIGNED_GROUP_ID', 41);
}

if(in_array(ALLOW_CHANGE_ASSIGNED_GROUP_ID, $group)) {
	CJSCore::Init(array('jquery2'));
	$asset->addJs($templateFolder . "/start_bp/script.js?v=1", true);
	$asset->addCss($templateFolder . "/start_bp/style.css?v=1", true);

	$asset->addString('
	<script>
		BX.ready(function () {
			try{
				new BX.StartBp("'. $templateFolder .'/start_bp/ajax.php", '.$USER->GetID().');
			} catch(e){
				console.error("error StartBp: " + e);
			}
		});
	</script>');
	if(isset($_GET['ajax111'])) {
		$APPLICATION->RestartBuffer();
	}
	?>
	<div class="hidden" id="start-user-search">
	<?
	$APPLICATION->IncludeComponent(
	'bitrix:main.user.selector',
	' ',
	[
	   "ID" => "mail_client_config_queue",
	   "API_VERSION" => 3,
	   "LIST" => [],
	   "INPUT_NAME" => "lead-user-id",
	   "USE_SYMBOLIC_ID" => true,
	   "BUTTON_SELECT_CAPTION" => '',
	   "SELECTOR_OPTIONS" => 
		[
		  "departmentSelectDisable" => "Y",
		  'context' => 'MAIL_CLIENT_CONFIG_QUEUE',
		  'contextCode' => 'U',
		  'enableAll' => 'N',
		  'userSearchArea' => 'I'
		]
	]
	);
	?>
	</div>
	<?
	if(isset($_GET['ajax111'])) {
		die();
	}
}