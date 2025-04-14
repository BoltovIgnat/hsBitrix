var BX = window.BX;

BX.namespace('RemoveFields');

BX.RemoveFields = (function ()
{
	var RemoveFields = function ()
	{
		this.addEvent();
		//this.onCustomEvent();
	};

	RemoveFields.prototype.addEvent = function() {
        this.removeFields();

		BX.addCustomEvent("Kanban.Grid:onRender", BX.delegate(function(params) {
            this.removeFields();
        }, this));
    };

	RemoveFields.prototype.removeFields = function(){
		var me = this;
        var timer = setInterval(function(){
			if($('#crm_kanban').length) {
				$('.crm-kanban-item-title').remove();
				clearInterval(timer);
			}
        }, 1);
    };

	RemoveFields.prototype.onCustomEvent = function () {
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

	return RemoveFields;
})();