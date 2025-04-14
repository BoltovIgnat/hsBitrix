<?
use Dbbo\Debug\Dump;

if(!$USER->IsAdmin()) {
	$asset = \Bitrix\Main\Page\Asset::getInstance();
	$asset->addJs($templateFolder . "/hs260/script.js?v=1", true);
	$asset->addCss($templateFolder . "/hs260/style.css?v=1", true);

writeLog('DealForm', "hs260 запуск");

	$asset->addString('
	<script>
		BX.ready(function () {
			try{
				new BX.HS260();
			} catch(e){
				console.error("error HS260: " + e);
			}
		});
	</script>');
}