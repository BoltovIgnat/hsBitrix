<?
use Dbbo\Phone\Region;
use Dbbo\Crm\Fields;

$asset = \Bitrix\Main\Page\Asset::getInstance();
$result = [];

$phone = Fields::GetList([], [
	'ENTITY_ID' => 'CONTACT',
	'TYPE_ID' => 'PHONE',
	'ELEMENT_ID' => $arResult['ENTITY_ID']
]);

if($phone) {
	$region = new Region();

	foreach($phone as $phoneItem) {
		$region->SetPhone($phoneItem['VALUE']);
		$region->GetInfo();
		$result = $region->GetResult();
	}
}

if(true || $result) {
	CJSCore::Init(array('jquery2'));
	$asset->addJs($templateFolder . "/phone_info/script.js?v=2", true);
	$asset->addCss($templateFolder . "/phone_info/style.css?v=1", true);

	$asset->addString('
	<script>
		BX.ready(function () {
			try{
				new BX.PhoneInfo('.CUtil::PhpToJSObject($result).');
			} catch(e){
				console.error("error PhoneInfo: " + e);
			}
		});
	</script>');
}