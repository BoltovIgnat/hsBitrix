<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addJs($templateFolder . "/reload_page/script.js?v=1", true);

CJSCore::Init(array('jquery2'));

$asset->addString('
    <script>
        BX.ready(function () {
            try{
                BX.ReloadPage.init();
            } catch(e){
                console.error("error ReloadPage: " + e);
            }
        });
    </script>');
