<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}
CJSCore::Init("sidepanel");
\Bitrix\Main\UI\Extension::load('ui.sidepanel-content');
\Bitrix\Main\UI\Extension::load("ui.forms");
?>
    <!DOCTYPE html>
    <html>
    <head>
        <script type="text/javascript">
            // Prevent loading page without header and footer
            if(window == window.top)
            {
                window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('IFRAME'))); ?>";
            }
        </script>
        <?$APPLICATION->ShowHead();?>
    </head>
    <body class="disk-slider-body">

    <div class="disk-slider-title"><? $APPLICATION->ShowTitle(); ?></div>
    <div class="disk-slider-workarea">
        <div class="ui-slider-section">
            <div class="ui-slider-content-box">
                <div class="ui-slider-heading-2">Загрузите УПД</div>
                <!--p class="ui-slider-paragraph">Paragraph 1. Текст слайдера. Теперь при загрузке картинки автоматически попадают в графический редактор. Вы можете обрезать изображение до нужного размера, настроить параметры, добавить текст и стикеры</p-->
            </div>

            <label class="ui-ctl ui-ctl-file-btn">
                <input type="file" class="ui-ctl-element hs-excel-file">
                <div class="ui-ctl-label-text">Добавить эксель файл</div>
            </label>

            <div id="ui-button-panel" class="ui-button-panel-wrapper ui-pinner ui-pinner-bottom ui-pinner-full-width">
                <div class="ui-button-panel  ">
                    <button id="ui-button-panel-save" name="save" value="Y" class="ui-btn ui-btn-success hs-excel-file-upload" hs-data-COMPETITOR_ID="<?=$arParams['COMPETITOR_ID']?>">Сохранить</button>
                    <a id="ui-button-panel-cancel" name="cancel" class="ui-btn ui-btn-link" href="/company/list/">Отмена</a>
                </div>
            </div>
        </div>
    </div>
    </body>
    </html>
<?