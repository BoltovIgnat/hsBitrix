;(function () {
    'use strict';

    BX.namespace('ReloadPage');

    BX.ReloadPage.init = function (template_id) {
        this.addEvent(template_id);
        //this.onCustomEvent();
    };

    /*
    * Event
    */
    BX.ReloadPage.addEvent = function (template_id) {
        
        BX.addCustomEvent("onAjaxSuccess", function (p, params) {
            if (params !== undefined) {
                    const d = params.data || {};
                    // Обработка смены стадии с обязательными полями
                    if(d?.length > 0)  {
                        if (d.includes('DT149_14%3APREPARATION')) {
                            document.location.reload();
                        }
                    } 

                // Обработка запуска БП
                var template_id_1 = 267;
                var template_id_2 = 269;
                var template_id_3 = 279;
                var template_id_4 = 281;
                var template_id_5 = 427;

                if (d?.length > 0 && d.includes('start_workflow')) {
                    const ar = d.split('&')
                        .reduce(
                            function (p, e) {
                                var a = e.split('=');
                                p[decodeURIComponent(a[0])] = decodeURIComponent(a[1]);
                                return p;
                            },
                            {}
                        );

                    if (parseInt(ar.template_id) === template_id_1) {
                        setTimeout(() => {
                            document.location.reload(true);
                        }, 3000);
                    }

                    if (parseInt(ar.template_id) === template_id_2) {
                        setTimeout(() => {
                            document.location.reload(true);
                        }, 2000);
                        console.log(2);
                    }

                    if (parseInt(ar.template_id) === template_id_3) {
                        setTimeout(() => {
                            document.location.reload(true);
                        }, 2000);
                    }

                    if (parseInt(ar.template_id) === template_id_4) {
                        setTimeout(() => {
                            document.location.reload(true);
                        }, 2000);
                    }

                    if (parseInt(ar.template_id) === template_id_5) {
                        setTimeout(() => {
                            document.location.reload(true);
                        }, 5000);
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
