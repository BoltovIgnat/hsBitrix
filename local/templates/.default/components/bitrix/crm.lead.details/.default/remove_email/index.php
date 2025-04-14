<?php

$asset = \Bitrix\Main\Page\Asset::getInstance();
CJSCore::Init(array('jquery2'));

$asset->addJs($templateFolder . "/remove_email/script.js?v=1", true);
$asset->addCss($templateFolder . "/remove_email/style.css?v=2", true);

$asset->addString('
	<script>
		BX.ready(function () {
			try{
				new BX.RemoveMenuTask();
			} catch(e){
				console.error("error RemoveEmail: " + e);
			}
		});
	</script>');
