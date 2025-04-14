var BX = window.BX;

BX.namespace('ShowEvent');

BX.HS227 = (function ()
{
	var HS227 = function (params)
	{
		this.params = params;
		this.addEvent();
		//this.onCustomEvent();
	};

	HS227.prototype.addEvent = function() {
		const this_ = this;
		let timer = setInterval(function(){
			if($('#crm_kanban').length) {
				clearInterval(timer);
				this_.hide();
			}
        }, 1);
    };

	HS227.prototype.hide = function() {
        let column = $('.main-kanban-column-body').filter('[data-id="'+this.params.deal_stage_id+'"]');
        if(column.length) {
            column.parents('.main-kanban-column').remove();
        }
        column = $('.main-kanban-column-body').filter('[data-id="'+this.params.lead_stage_id+'"]');
        if(column.length) {
            column.parents('.main-kanban-column').remove();
        }
    };

	HS227.prototype.onCustomEvent = function () {
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

	return HS227;
})();