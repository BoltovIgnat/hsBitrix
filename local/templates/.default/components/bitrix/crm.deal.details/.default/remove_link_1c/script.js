var BX = window.BX;

BX.namespace('RemoveLink1c');

BX.RemoveLink1c = (function ()
{
	var RemoveLink1c = function ()
	{
		this.addEvent();
		//this.onCustomEvent();
	};

	RemoveLink1c.prototype.addEvent = function() {
		const this_ = this;
		var timer = setInterval(function(){
			if($('.crm-entity-stream-container-content').length) {
				clearInterval(timer);
				this_.checkLink();
			}
        }, 1);

		BX.addCustomEvent("Schedule:onBeforeRefreshLayout", function(params) {
            this_.checkLink();
        });

		BX.addCustomEvent("Crm.EntityModel.Change", function(params) {
            this_.checkLink();
        });
    };

	RemoveLink1c.prototype.checkLink = function() {
		$('.crm-entity-stream-content-event-title:contains("Дело приложения")').parents('.crm-entity-stream-content-event').find('a').css({'pointer-events':'none'});
		$('.crm-entity-stream-content-event-title:contains("Дело 1с")').parents('.crm-entity-stream-content-event').find('a').css({'pointer-events':'none'});
    };

	RemoveLink1c.prototype.onCustomEvent = function () {
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

	return RemoveLink1c;
})();