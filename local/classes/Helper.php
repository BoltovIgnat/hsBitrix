<?php

namespace Hs;
use Bitrix\Main\Page\Asset;

class Helper
{
    public static function AddBtn()
    {
        global $APPLICATION;
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        $uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
        $path = $uri->getPath();

        if (str_contains( $path, '/crm/company/details/' )){
            ob_start();
            ?>
            <button class="ui-btn ui-btn-light-border ui-btn-icon-page hs-btn-excel ui-btn-themes" >
                <span class="ui-btn-text">Загрузить УПД</span>
            </button>
            <?php
            $html = ob_get_clean();
            $APPLICATION->AddViewContent("inside_pagetitle", $html, 50000);
            \Bitrix\Main\UI\Extension::load('ui.sidepanel.layout');
            $assetManager = \Bitrix\Main\Page\Asset::getInstance();
            $assetManager->addString('
                <script>
                (function() {
                    
                    $(document).on("click",".hs-btn-excel",function() {
                        console.log(`ibc add btn hs-btn-excel`);
                        
                        BX.SidePanel.Instance.open("/hs/uploadexcel/", {
                            width: 800,
	                        requestMethod: "post",
                            requestParams: { // post-параметры
                                COMPETITOR_ID: "59622",
                            },
                            mobileFriendly: true,
                            allowChangeHistory: false,
                            label: {
                                text: "Закрыть",
                                color: "#FFFFFF",
                                bgColor: "#E2AE00",
                                opacity: 80
                            },
                        });
                    });
                    
                })();
                </script>
            ');
        }

    }
}