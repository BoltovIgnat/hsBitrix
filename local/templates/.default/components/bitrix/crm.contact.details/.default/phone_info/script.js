var BX = window.BX;

BX.namespace('PhoneInfo');

BX.PhoneInfo = (function ()
{
	var PhoneInfo = function (params)
	{
		this.params = params || {};

		if(this.params) {
			this.addEvent();
			//this.onCustomEvent();
		} else {
			$(document).on('click', '.crm-entity-widget-content-block-select', function(){
				PhoneInfo.prototype.change(this);
			});
		}
	};

	PhoneInfo.prototype.addEvent = function() {
        this.run();

		BX.addCustomEvent("BX.Crm.EntityEditor:onRelease", BX.delegate(function(params) {
            this.run();
        }, this));

		$(document).on('click', '.crm-entity-widget-content-block-select', function(){
			PhoneInfo.prototype.change(this);
		});
    };

	PhoneInfo.prototype.run = function(fields){
		var me = this;
        var timer = setInterval(function(){
			if($('[data-tab-id="main"]').length) {
				clearInterval(timer);

				for (var key in me.params) {
					const link = $('[data-cid="PHONE"]').find('.crm-entity-phone-number[title="'+me.params[key]['PHONE_FORMATTED']+'"]');
					const region = me.params[key]['REGION'] !== undefined ? me.params[key]['REGION'] : false;
					const city = me.params[key]['CITY'] !== undefined ? me.params[key]['CITY'] : false;
					const gmt = me.params[key]['GMT'] !== undefined ? me.params[key]['GMT'] : false;
					if(link && (region || city) ) {
						link.closest('.crm-entity-widget-content-block-mutlifield-value').append('<div class="phone-region">'+region || ''+' '+city || ''+' '+gmt || ''+'</div>');
					}
				}
			}
        }, 1);
    };

	PhoneInfo.prototype.change = function(target){
		const popup = $('body').find('.popup-window.popup-window-fixed-width');
		if(popup.length) {
			const offset = $(target).offset();
			const top = offset.top - 124;
			const item = popup.find('.menu-popup-item');
			item.each(function(){
				const text = $(this).find('.menu-popup-item-text').text();
				// if(text !== 'Рабочий' && text !== 'Мобильный' && text !== 'Другой') {
				if(text == 'Факс' || text == 'Домашний' || text == 'Пейджер' || text == 'Для рассылок') {
					$(this).remove();
				}
			});
			popup.css('top', top+'px');
		}
    };

	PhoneInfo.prototype.onCustomEvent = function () {
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

	return PhoneInfo;
})();