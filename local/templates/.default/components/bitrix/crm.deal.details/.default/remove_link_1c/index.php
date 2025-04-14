<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addJs($templateFolder . "/remove_link_1c/script.js?v=1", true);
$asset->addCss($templateFolder . "/remove_link_1c/style.css?v=1", true);

$asset->addString('
<script>
	BX.ready(function () {
		try{
			new BX.RemoveLink1c();
		} catch(e){
			console.error("error RemoveLink1c: " + e);
		}
	});
</script>');