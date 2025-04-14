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

        var template_id = 295;
        var template_id1 = 226;

        BX.addCustomEvent("onAjaxSuccess", function (p, params) {
            if (params !== undefined) {
                const d = params.data || {};

                // Обработка смены стадии с обязательными полями
                if(d instanceof FormData && d.has('STAGE_ID')) {
                    const stages = ['LOSE', '3', '4', '5', '9'];
                    if (stages.includes(d.get('STAGE_ID'))) {
                        document.location.reload();
                    }
                }

                // Обработка запуска БП
                if (Object.keys(d).length !== 0) {
                    const ar = d.split('&')
                        .reduce(
                            function (p, e) {
                                var a = e.split('=');
                                p[decodeURIComponent(a[0])] = decodeURIComponent(a[1]);
                                return p;
                            },
                            {}
                        );

                    if (d.indexOf('start_workflow') !== -1) {
                        if (parseInt(ar.template_id) === template_id || parseInt(ar.template_id) === template_id1) {
                            document.location.reload(true);
                        }
                    }
                }
            }
        });

        // Обработка смены стадии без обязательных полей
        BX.addCustomEvent("Crm.EntityProgress.Saved", function (p, params) {
            const stages = ['LOSE', '3', '4', '5', '9'];
            if (stages.includes(params.currentStepId)) {
                document.location.reload();
            }
        });
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
