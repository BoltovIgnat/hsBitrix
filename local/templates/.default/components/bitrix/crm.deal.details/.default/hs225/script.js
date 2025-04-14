var BX = window.BX;

BX.namespace('HS225');

BX.HS225 = (function () {
    var HS225 = function (params) {
        this.params = params || {};
        this.typeCompanyValue = null;
        this.addEvent();
        //this.onCustomEvent();
    };

    HS225.prototype.addEvent = function () {
        const this_ = this;

        const data = {
            code: this.params.fieldCodeTypeCompany['code'],
            value: (!this.params.fieldCodeTypeCompany['value']['IS_EMPTY'] ? this.params.fieldCodeTypeCompany['value']['VALUE'] : '')
        };

        this.run(data);

        BX.addCustomEvent("onAfterSubmit", BX.delegate(function (params) {
            const form = params._config.data;
            let type = form.get(this.params.fieldCodeTypeCompany['code']);

            if (type !== null && this.typeCompanyValue !== type) {
                this.typeCompanyValue = type;
                document.location.reload();
            }
        }, this));
    };

    HS225.prototype.run = function (data) {
        this.typeCompanyValue = data.value;
    };

    HS225.prototype.onCustomEvent = function () {
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

    return HS225;
})();
