;(function () {
    'use strict';

    BX.namespace('ReloadPage');

    BX.ReloadPage.init = function () {
        this.addEvent();
        //this.onCustomEvent();
    };

    /*
    * Event
    */
    BX.ReloadPage.addEvent = function () {
        
        BX.addCustomEvent("onAjaxSuccess", function (p, params) {
            if (params !== undefined) {
                const d = params.data || {};
                // Обработка смены стадии с обязательными полями
                 if(d?.length > 0)  {
                    if (d.includes('DT149_14%3APREPARATION')) {
                        document.location.reload();
                    }
                } 
            }
        });

        // Обработка смены стадии без обязательных полей
/*         BX.addCustomEvent("Crm.EntityProgress.Saved", function (p, params) {
            const stages = ['LOSE', '3', '4', '5', '9'];
            if (stages.includes(params.currentStepId)) {
                document.location.reload();
            }
        }); */
    };

    BX.ReloadPage.onCustomEvent = function () {
        var originalBxOnCustomEvent = BX.onCustomEvent;
        BX.onCustomEvent = function (eventObject, eventName, eventParams, secureParams) {

            var logData = {
                eventObject: eventObject,
                eventName: eventName,
                eventParams: eventParams,
                eventParamsClassNames: [],
                secureParams: secureParams
            };

            for (var i in eventParams) {
                var param = eventParams;
                if (param !== null && typeof param == 'object' && param.constructor) {
                    logData['eventParamsClassNames'].push(param.constructor.name);
                } else {
                    logData['eventParamsClassNames'].push(null);
                }
            }

            console.log(logData);

            originalBxOnCustomEvent.apply(null, [eventObject, eventName, eventParams, secureParams]);
        };
    };

})();
