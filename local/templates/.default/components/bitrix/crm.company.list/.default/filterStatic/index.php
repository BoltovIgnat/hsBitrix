<?php
use Bitrix\Main\Page\Asset;

$assetManager = \Bitrix\Main\Page\Asset::getInstance();
$userId = $USER->getId();
$userName = trim($USER->GetFirstName() . ' ' . $USER->GetLastName());
$userGroups = CUser::GetUserGroup($userId);

if (!$USER->IsAdmin() && !in_array(51, $userGroups)) {

// Для подключения css
    Asset::getInstance()->addCss("/local/templates/.default/components/bitrix/crm.company.list/.default/style.css");
    $assetManager->addString('
        <script>
        (function() {
            const userId = "'.$userId.'";
            const userName = "'.$userName.'";
            let isApplying = false;
            
            BX.addCustomEvent("Grid::ready", function(gridData) {
                //console.log("ready");
                               
                const filter = BX.Main.filterManager.getById(gridData.containerId);
                if (filter) safeApplyFilter(filter);
            });
           
            BX.addCustomEvent("Grid::beforeRequest", function(gridData, args) {
                //console.log("beforeRequest");                
                const filter = BX.Main.filterManager.getById(args.gridId);
                if (filter) safeApplyFilter(filter);
            });
            
            BX.addCustomEvent("BX.Main.Filter:show", function(gridData) {
                $(`input[name="ASSIGNED_BY_ID_label"]`).attr(`disabled`, `disabled`)
            });

            function safeApplyFilter(filter) {
                //console.log("Изменение фильтра");
                                
                if (isApplying) return false;
                if (filter.presets.getPreset(filter.presets.getCurrentPresetId()).TITLE == `Свободные компании`) return false;
                
                try {
                    isApplying = true;
                    const values = filter.getFilterFieldsValues();
                    let needUpdate = false;
                    debugger;
                    //filter.presets.getPreset(filter.presets.getCurrentPresetId())
                    if (!values["ASSIGNED_BY_ID"].includes(userId)) {
                        values["ASSIGNED_BY_ID"] = [userId];
                        needUpdate = true;
                    }
                    
                    if (!values["ASSIGNED_BY_ID_label"].includes(userName)) {
                        values["ASSIGNED_BY_ID_label"] = [userName];
                        needUpdate = true;
                    }
                    
                    if (needUpdate) {
                        filter.getApi().setFields(values);
                        filter.getApi().apply();
                    }
                    
                    //console.log("Завершен");

                    return needUpdate;
                } catch (e) {
                    console.error("Filter error:", e);
                    return false;
                } finally {
                    isApplying = false;
                }
            }
            
            // Просмотр событий всех
            /*BX.onCustomEvent = function(eventObject, eventName, arEventParams, secureParams) {
                //console.log(eventName);
            };*/
            
        })();
        </script>
    ');
}