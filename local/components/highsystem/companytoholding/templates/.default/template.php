<?php
defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;

/** @var CBitrixComponentTemplate $this */

if (!Loader::includeModule('crm')) {
    ShowError(Loc::getMessage('CTOH_NO_CRM_MODULE'));
    return;
}

$asset = Asset::getInstance();
$asset->addJs('/bitrix/js/crm/interface_grid.js');

$gridManagerId = $arResult['GRID_ID'] . '_MANAGER';

$rows = array();

foreach ($arResult['COMPANIES'] as $company) {

    $viewUrl = CComponentEngine::makePathFromTemplate(
        $arParams['URL_TEMPLATES']['DETAIL'],
        array('STORE_ID' => $company['ID'])
    );

    $editUrl = CComponentEngine::makePathFromTemplate(
        $arParams['URL_TEMPLATES']['EDIT'],
        array('STORE_ID' => $company['ID'])
    );

    $deleteUrlParams = http_build_query(array(
        'action_button_' . $arResult['GRID_ID'] => 'delete',
        'ID' => array($company['ID']),
        'sessid' => bitrix_sessid()
    ));

    $deleteUrl = $arParams['SEF_FOLDER'] . '?' . $deleteUrlParams;

    $rows[] = array(
        'id' => $company['ID'],
/*        'actions' => array(
            array(
                'TITLE' => Loc::getMessage('CTOH_ACTION_VIEW_TITLE'),
                'TEXT' => Loc::getMessage('CTOH_ACTION_VIEW_TEXT'),
                'ONCLICK' => 'BX.Crm.Page.open(' . Json::encode($viewUrl) . ')',
                'DEFAULT' => true
            ),
            array(
                'TITLE' => Loc::getMessage('CTOH_ACTION_EDIT_TITLE'),
                'TEXT' => Loc::getMessage('CTOH_ACTION_EDIT_TEXT'),
                'ONCLICK' => 'BX.Crm.Page.open(' . Json::encode($editUrl) . ')',
            ),
            array(
                'TITLE' => Loc::getMessage('CTOH_ACTION_DELETE_TITLE'),
                'TEXT' => Loc::getMessage('CTOH_ACTION_DELETE_TEXT'),
                'ONCLICK' => 'BX.CrmUIGridExtension.processMenuCommand(' . Json::encode($gridManagerId) . ', BX.CrmUIGridMenuCommand.remove, { pathToRemove: ' . Json::encode($deleteUrl) . ' })',
            )
        ),*/
        'data' => $company,
        'columns' => array(
            'ID' => $company['ID'],
            'TITLE' => $company['TITLE'],
            'COMPANYINN' => $company['UF_CRM_639719B8E38A2'],
            'COMPANYKPP' => $company['UF_CRM_639719B9949D5'],
            'REQINN' => $company["RQ_INN"],
            'REQKPP' => $company["RQ_KPP"],
            "LINK" => '<a href="javascript:;" class="linkbutton" data-id='.$company["ID"].' style="cursor:pointer" onclick="linkToHolding(this,'.$company["LINK"].')">Привязать</a>',
            "UNLINK" => '<a href="javascript:;" class="unlinkbutton" data-id='.$company["ID"].' style="cursor:pointer" onclick="unlinkHolding(this,'.$company["UNLINK"].')">Отвязать</a>',
        )
    );

}

?>
<?php if ($_REQUEST["ajax"] != "Y"):?>
<div class="ajaxLoad">
<?endif;?>

    <form class="ctohsearch" style="margin-bottom:20px"><input type="text" name="search" style="padding:10px 10px;border: 1px solid #dfe0e3" placeholder='ИНН или Название' value='<?=($_REQUEST["data"]["search"])??$_REQUEST["data"]["search"];?>'> <input type="hidden" name="hid" value="<?=$arParams["AJAX_LOADER"]["data"]["PARAMS"]["holdingID"];?>"><button style="margin-left:20px;padding:10px 10px;background-color: #3bc8f5;border: 1px solid #dfe0e3;color:white;cursor:pointer" type="submit">Найти</button></form>

<?/*
<table class="simpleTable">
    <?foreach ($rows as $row):?>
    <tr><?=$row["TITLE"];?></tr>
    <?endforeach;?>
</table>
*/
?>

<?php
//$snippet = new Snippet();

$APPLICATION->IncludeComponent(
    'bitrix:crm.interface.grid',
    'titleflex',
    array(
        'GRID_ID' => $arResult['GRID_ID'],
        'HEADERS' => $arResult['HEADERS'],
        'ROWS' => $rows,

        "NAV_OBJECT"=>"",
        "FOOTER"=> "",
        'PAGINATION' => false,

        'SORT' => $arResult['SORT'],
        'FILTER' => $arResult['FILTER'],
        'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
        'IS_EXTERNAL_FILTER' => false,
        'ENABLE_LIVE_SEARCH' => $arResult['ENABLE_LIVE_SEARCH'],
        'DISABLE_SEARCH' => $arResult['DISABLE_SEARCH'],
        //'ENABLE_ROW_COUNT_LOADER' => true,
        'AJAX_MODE' => $arParams["AJAX_MODE"],
        'AJAX_ID' => \CAjax::getComponentID('bitrix:crm.interface.grid', 'titleflex', ''),
        'AJAX_OPTION_JUMP' => $arParams["AJAX_OPTION_JUMP"],
        'AJAX_OPTION_HISTORY' => $arParams["AJAX_OPTION_HISTORY"],
        'AJAX_LOADER' => $arParams["AJAX_LOADER"],
        'ACTION_PANEL' => false,
/*        'ACTION_PANEL' => array(
            'GROUPS' => array(
                array(
                    'ITEMS' => array(
                        $snippet->getRemoveButton(),
                        $snippet->getForAllCheckbox(),
                    )
                )
            )
        ),*/
/*         'EXTENSION' => array(
            'ID' => $gridManagerId,
            'CONFIG' => array(
                'ownerTypeName' => 'STORE',
                'gridId' => $arResult['GRID_ID'],
                'serviceUrl' => $arResult['SERVICE_URL'],
            ),
            'MESSAGES' => array(
                'deletionDialogTitle' => Loc::getMessage('CTOH_DELETE_DIALOG_TITLE'),
                'deletionDialogMessage' => Loc::getMessage('CTOH_DELETE_DIALOG_MESSAGE'),
                'deletionDialogButtonTitle' => Loc::getMessage('CTOH_DELETE_DIALOG_BUTTON'),
            )
        ), */
    ),
    $this->getComponent(),
    array('HIDE_ICONS' => 'Y',)
);
?>
<?php if ($_REQUEST["ajax"] != "Y"):?>
<script>
    BX.ready(function(){
        window.linkToHoldingIDS = {};
        window.unlinkHoldingIDS = {};
        window.holdingID = 0; 
        });
    
    let curCompanies = <?=json_encode($arResult['CURRENT_COMPANIES']);?>;
</script>
</div>
<?php endif;?>
<script>
    function showSaveButton(){
        if (!$('.ui-entity-wrap').hasClass('crm-section-control-active')) {
            $('.ui-entity-wrap').addClass('crm-section-control-active');
        }
    };

    function setMain(e,cId,hId){
        const url = '/ajax/Company/setMain/?cId='+cId+'&hId='+hId+'&sessid='+BX.bitrix_sessid();
        $.ajax({
            url: url,
            method: 'post',
            dataType: 'html',
            data: {},
            success: function(res){
                $(e).text("Установлено");
                document.location.reload();
            }
        });
    };

    function linkToHolding(e,cId,hId){
        if (window.linkToHoldingIDS[cId]) {
            delete linkToHoldingIDS[cId];
            $(e).text("Привязать"); 
        }
        else {
            $(e).text("Будет привязано");
            window.holdingID = hId;
            window.linkToHoldingIDS[cId] = cId;
            showSaveButton();
        }
    };

    $('.ui-btn-success').click(function(){
        $(this).addClass('disabled');
        $('.tab2').addClass('disabled');
        saveChanges();
    });

    function saveChanges(){
            $('.ui-btn-success').addClass('disabled');

            for( unlink in window.unlinkHoldingIDS ) {
                const urlunlink = '/ajax/Company/unlinkHolding/?cId='+unlink+'&hId='+window.holdingID+'&sessid='+BX.bitrix_sessid();
                $.ajax({
                    url: urlunlink,
                    method: 'post',
                    dataType: 'html',
                    data: {},
                    success: function(res){
                    }
                });
                delete unlinkHoldingIDS[unlink];
            }

            for( link in window.linkToHoldingIDS ) {
                const urllink = '/ajax/Company/linkToHolding/?cId='+link+'&hId='+window.holdingID+'&sessid='+BX.bitrix_sessid();
                $.ajax({
                    url: urllink,
                    method: 'post',
                    dataType: 'html',
                    data: {},
                    success: function(res){
                    }
                });
                delete linkToHoldingIDS[link];
            }

            document.location.reload(); 
    };

    function unlinkHolding(e,cId,hId){
        if (window.unlinkHoldingIDS[cId]) {
            delete unlinkHoldingIDS[cId];
            $(e).text("Отвязать"); 
        }
        else {
            $(e).text("Будет отвязано");
            window.holdingID = hId;
            window.unlinkHoldingIDS[cId] = cId;
            showSaveButton();
        }
    };

    $(".ctohsearch").submit(function(e) {
        e.preventDefault();
        let formData = $(".ctohsearch").serializeArray();
        let data = {};
        if (formData) {
            $.each(formData,function(){
                data[this.name] = this.value;
            });
        }
        data["hid"] = <?=($arParams["HID"]) ? $arParams["HID"]:0;?>;
        
        $.ajax({
            url: '/local/components/highsystem/companytoholding/ajax.php?ajax=Y&sessid='+BX.bitrix_sessid(),
            method: 'post',
            dataType: 'html',
            data: {data},
            success: function(res){
                $('.ajaxLoad').html(res);
                changeButtons(curCompanies);
            }
        });
    });

    function changeButtons(curCompanies) {
        let companies = curCompanies || "";
        $('.linkbutton').each(function(e){
            let id = $(this).attr('data-id');
            if (curCompanies.includes(id)) {
                $(this).removeClass('linkbutton');
                $(this).addClass('greenbutton');
                $(this).text('Привязано');
                $(this).attr('onclick',"");

            }
        });
    };

</script>
<style>
.unlinkbutton {
        margin: 0px 10px 0px 0px;
        padding: 10px 10px;
        background-color: red;
        border: 1px solid #dfe0e3;
        color: black;
        cursor: pointer;
        border-radius: 4px;
    }
    .linkbutton {
        margin: 0px 10px 0px 0px;
        padding: 10px 10px;
        background-color: #3bc8f5;
        border: 1px solid #dfe0e3;
        color: black;
        cursor: pointer;
		display: inline-block;
        border-radius: 4px;
    }
    .graybutton {
		margin: 0px 25px 0px 0px;
        padding: 10px 10px;
        background-color: gray;
        border: 1px solid #dfe0e3;
        color: black;
        cursor: pointer;
        border-radius: 4px;
    }
	.greenbutton {
		margin: 0px 10px 0px 0px;
        padding: 10px 10px;
        background-color: #bbed21;
        border: 1px solid #dfe0e3;
        color: black;
        cursor: pointer;
        border-radius: 4px;
    }
</style>