var BX = window.BX;

BX.namespace('HS231');

BX.HS231 = (function ()
{
	var HS231 = function (params)
	{
		this.params = params || {};
		this.addEvent();
		//this.onCustomEvent();
	};

	HS231.prototype.addEvent = function() {
		const this_ = this;

		BX.addCustomEvent("BX.Crm.EntityEditor:onControlModeChange", BX.delegate(function(params) {
			setTimeout(() => {
				this.action(params._container);
			}, 1000);
        }, this));
    };

	HS231.prototype.action = function(container) {
		const this_ = this;
		const client = $(container).find('[data-cid="CLIENT"]');

		if(client.length) {
			const companyItems = $(container).find('.crm-entity-widget-img-company');

			if(companyItems.length) {
				companyItems.each(function(index, item){
					this_.showClose(item);
				});
			}
		}
	};

	HS231.prototype.showClose = function(item) {
		const box = $(item).parents('.crm-entity-widget-content-search-inner');
		const close = box.find('.crm-entity-widget-btn-close').show();
		BX.bind(close[0], 'click', BX.delegate(this.startWorflow, this));
	};

	HS231.prototype.startWorflow = function() {
		BX.ajax({   
			url: this.params.url,
			data: {
				workflowId: this.params.workflowId,
				sessid: BX.bitrix_sessid(),
				action: "startWorkflow",
				id: this.params.id
			},
			method: 'POST',
			dataType: 'json',
			timeout: 30,
			async: true,
			start: true,
			cache: false,
			onsuccess: function(res){
				if(!res.result) {
					alert(res.error);
				} else {
					document.location.reload();
				}
			},
			onfailure: function(){}
		});
	};

	HS231.prototype.onCustomEvent = function () {
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

	return HS231;
})();