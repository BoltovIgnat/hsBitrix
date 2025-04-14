var BX = window.BX;

BX.namespace('HS260');

BX.HS260 = (function ()
{
	var HS260 = function (params)
	{
		this.params = params || {};
		this.addEvent();
		//this.onCustomEvent();
	};

	HS260.prototype.addEvent = function() {
        const this_ = this;
        const timer = setInterval(function(){
			if($('#pagetitle-menu').length) {
				clearInterval(timer);
				setTimeout(() => {
					this_.action();
				}, 1);
			}
        }, 1);
    };

	HS260.prototype.action = function(data){
		$('#pagetitle-menu .ui-btn-icon-setting').remove();
    };

	HS260.prototype.onCustomEvent = function () {
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

	return HS260;
})();