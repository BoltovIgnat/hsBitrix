<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Tasks\Ui\Filter;
use \Bitrix\Tasks\Kanban;


// selected items of menu
if (isset($arParams['INCLUDE_INTERFACE_HEADER']) && $arParams['INCLUDE_INTERFACE_HEADER'] == 'Y')
{
	if(Kanban\StagesTable::getWorkMode() == Kanban\StagesTable::WORK_MODE_GROUP)
	{
		$arParams['MARK_SECTION_ALL'] = 'N';
		$arParams['MARK_ACTIVE_ROLE'] = 'N';
	}
	else
	{
		$arParams['MARK_ACTIVE_ROLE'] = 'Y';
		$arParams['MARK_SECTION_ALL'] = 'N';

		$state = Filter\Task::getListStateInstance()->getState();

		if (isset($state['SECTION_SELECTED']['CODENAME']) &&
			$state['SECTION_SELECTED']['CODENAME'] == 'VIEW_SECTION_ADVANCED_FILTER')
		{
			$arParams['MARK_SECTION_ALL'] = 'Y';
			$arParams['MARK_ACTIVE_ROLE'] = 'N';
		}

//		if (isset($state['SPECIAL_PRESETS']) && is_array($state['SPECIAL_PRESETS']))
//		{
//			foreach ($state['SPECIAL_PRESETS'] as $preset)
//			{
//				if ($preset['SELECTED'] == 'Y')
//				{
//					$arParams['MARK_SPECIAL_PRESET'] = 'Y';
//					$arParams['MARK_SECTION_ALL'] = 'N';
//					$arParams['MARK_ACTIVE_ROLE'] = 'N';
//					break;
//				}
//			}
//		}
	}
}

// kanban wo group tmp not available
if ($arParams['GROUP_ID'] == 0 && $arParams['PERSONAL'] != 'Y')
{
	$arResult['DATA']['items'] = array();
	foreach ($arResult['DATA']['columns'] as &$column)
	{
		$column['total'] = 0;
	}
	unset($column);
}

?>
<script>
      BX.addCustomEvent("Kanban.Grid:onRender", BX.delegate(function(params) {
        addTasksUFInfoRender(params);
    }, this));  

    BX.addCustomEvent("onPullEvent-tasks", BX.delegate(function(event, params) {
        let eventName = event || "";
        if (eventName == "task_update") {
            if (params?.AFTER.STATUS == 5) {
                let tid = params.TASK_ID;
                let url = '/ajax/Tasks/deletePing/?id='+tid+'&sessid='+BX.bitrix_sessid();
                $.ajax({
                    url: url,
                    method: 'post',
                    dataType: 'json',
                    data: {},
                    success: function(res){
                        let deletedId = res.pingDeleted || "";
                        $('div[data-id='+deletedId+']').remove();
                        setTimeout(function(){
                            $('div[data-id='+deletedId+']').remove(); 
                        }, 1000)
                    }
                });
            }
        }


    }, this)); 
    
    function addTasksUFInfo(params) {
        let companyType = params.data.uf_crm_company_type || "";
        let companybg = "";
        let cardColor = "";
        let contactID = params.data.uf_crm_contact_id || "";
        let contactNAME = params.data.uf_crm_contact || "";
        let companyID = params.data.uf_crm_company_id || "";
        let companyNAME = params.data.uf_crm_company || "";
        let formatRub = params.data.uf_crm_total | "";
        let crmType = params.data.uf_crm_type || "";
        let supportName = params.data.uf_crm_support_name || "";
        switch (companyType) {
            case "CUSTOMER":
                companybg = "bg-customer";
                break;
            case "COMPETITOR":
                companybg = "bg-competitor";
                break;
            case "SUPPLIER":
                companybg = "bg-supplier";
                break;
            case "PARTNER":
                companybg = "bg-parnter";
                break;
            case "OTHER":
                companybg = "bg-other";
                break;
            default:
                companybg = "bg-standart";    
        }

        switch (crmType) {
            case "Лид":
                cardColor = "yellow";
                break;
            case "Сделка":
                cardColor = "orange";
                break;
            case "Проект":
                cardColor = "skyblue";
                break;
            case "Реанимация":
                cardColor = "grey";
                break;
            case "Заявка support":
                cardColor = "violet";
                break;
            default:
                cardColor = "transparent";    
        }

            $('div[data-id='+params.data.id +']').find('.tasks-kanban-item-title').after('<div class="UFDetail"><span><b>'+supportName+'</b></span><span><b>'+params.data.uf_crm_type+'</b></span><span><b>'+formatRub+'</b></span><span class="crm-kanban-item-contact"><a href="/crm/contact/details/'+contactID+'/" bx-tooltip-user-id="CONTACT_'+contactID+'" bx-tooltip-loader="/bitrix/components/bitrix/crm.contact.show/card.ajax.php" bx-tooltip-classname="crm_balloon_contact">'+contactNAME+'</a><br><a href="/crm/company/details/'+companyID+'/" bx-tooltip-user-id="COMPANY_'+companyID+'" bx-tooltip-loader="/bitrix/components/bitrix/crm.company.show/card.ajax.php" bx-tooltip-classname="crm_balloon_company" class="company-data '+companybg+'">'+companyNAME+'</a></span></div>');
            $('div[data-id='+params.data.id +']').children('.main-kanban-item-wrapper').children('.tasks-kanban-item').css('border-right','20px solid '+cardColor);
    }
    function addTasksUFInfoRender(params) {
        let items = params.items;
        for (ii in items) {
            let companyType = items[ii].data.uf_crm_company_type || "";
            let companybg = "";
            let cardColor = "";
            let contactID = items[ii].data.uf_crm_contact_id || "";
            let contactNAME = items[ii].data.uf_crm_contact || "";
            let companyID = items[ii].data.uf_crm_company_id || "";
            let companyNAME = items[ii].data.uf_crm_company || "";
            let formatRub = items[ii].data.uf_crm_total || "";
            let crmType = items[ii].data.uf_crm_type || "";
            let supportName = items[ii].data.uf_crm_support_name || "";
            switch (companyType) {
                case "CUSTOMER":
                    companybg = "bg-customer";
                    break;
                case "COMPETITOR":
                    companybg = "bg-competitor";
                    break;
                case "SUPPLIER":
                    companybg = "bg-supplier";
                    break;
                case "PARTNER":
                    companybg = "bg-parnter";
                    break;
                case "OTHER":
                    companybg = "bg-other";
                    break;
                default:
                    companybg = "bg-standart";
            }


            switch (crmType) {
                case "Лид":
                    cardColor = "yellow";
                    break;
                case "Сделка":
                    cardColor = "orange";
                    break;
                case "Проект":
                    cardColor = "skyblue";
                    break;
                case "Реанимация":
                    cardColor = "grey";
                    break;
                case "Заявка support":
                    cardColor = "violet";
                    break;
                default:
                    cardColor = "transparent";    
            }

            $('div[data-id='+items[ii].data.id +']').find('.tasks-kanban-item-title').after('<div class="UFDetail"><span><b>'+supportName+'</b></span><span><b>'+crmType+'</b></span><span><b>'+formatRub+'</b></span><span class="crm-kanban-item-contact"><a href="/crm/contact/details/'+contactID+'/" bx-tooltip-user-id="CONTACT_'+contactID+'" bx-tooltip-loader="/bitrix/components/bitrix/crm.contact.show/card.ajax.php" bx-tooltip-classname="crm_balloon_contact">'+contactNAME+'</a><br><a href="/crm/company/details/'+companyID+'/" bx-tooltip-user-id="COMPANY_'+companyID+'" bx-tooltip-loader="/bitrix/components/bitrix/crm.company.show/card.ajax.php" bx-tooltip-classname="crm_balloon_company" class="company-data '+companybg+'">'+companyNAME+'</a></span></div>');
            $('div[data-id='+items[ii].data.id +']').children('.main-kanban-item-wrapper').children('.tasks-kanban-item').css('border-right','20px solid '+cardColor);
        } 
    }

</script>
<link href="/local/templates/.default/components/bitrix/crm.kanban/.default/show_event/style.css?<?=rand();?>" rel="stylesheet" />
<style>
    .UFDetail {
        display: flex;
        flex-direction: column;
        gap:5px 0px;
    }
    .tasks-kanban-item {
        border-top-right-radius:0px;
        border-bottom-right-radius:0px;
    }
</style>
