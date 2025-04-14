var BX = window.BX;

BX.namespace('ShowEvent');

BX.ShowEvent = (function ()
{
	var ShowEvent = function (params)
	{
		this.params = params;
		this.addEvent();
		//this.onCustomEvent();
	};

	ShowEvent.prototype.addEvent = function() {
		BX.addCustomEvent("tasksTaskEvent", BX.delegate(function(params, params1) {
            this.request([params1.options.id]);
        }, this));

		BX.addCustomEvent("Kanban.Column:render", BX.delegate(function(params) {
			const arr = [];
			Object.keys(params.items).forEach(key => {
				if(params.items[key].id > 0) {
					arr.push(params.items[key].id);
				}
			});
			if(arr.length) {
				this.request(arr);
			}
        }, this));
    };
	
	ShowEvent.prototype.request = function(items){
		const me = this;
		BX.ajax({
			url: '/local/templates/.default/components/bitrix/crm.kanban/.default/show_event/ajax.php',
			method: 'POST',
			dataType: 'json',
			data: { items: items, type: me.params.type },
			onsuccess: function(result){
				me.show(result);
			},
			onfailure: function(){
			},
		});
    };

	ShowEvent.prototype.show = function(data){
		var me = this;
        var timer = setInterval(function(){
			if($('#crm_kanban').length) {
				clearInterval(timer);
				me.add(data);
			}
        }, 1);
    };

	ShowEvent.prototype.add = function(info){
		Object.keys(info.items).forEach(key => {
			const data = info.items[key][0];
			const $item = $('#crm_kanban [data-element="kanban-element"]').filter('[data-id="'+key+'"]');
			if($item.length) {
				$item.find('[data-entity="show-event"]').remove();
				$item.find('.crm-kanban-item-fields').append(this.draw(data));
			}
		});

		if(info.company) {
			Object.keys(info.company).forEach(key => {
				const data = info.company[key];
				const $item = $('#crm_kanban [bx-tooltip-user-id="COMPANY_'+key+'"]');
				if($item.length) {
					$item.addClass('company-data bg-'+data.toLowerCase());
				}
			});
		}
    };

	ShowEvent.prototype.draw = function(data){
		return '<div class="crm-kanban-item-fields-item" data-entity="show-event"><div class="crm-kanban-item-fields-item-title" title="'+data['SUBJECT']+'"><span class="ui-label ui-label-md ui-label-tag-light ui-label-fill"><span class="ui-label-inner">'+data['SUBJECT']+'</span></span></div><div class="crm-kanban-item-fields-item-value" title="'+data['SUBJECT']+'"><span class="ui-label ui-label-md '+data['CLASS']+'"><span class="ui-label-inner">'+data['DEADLINE_FORMAT']+'</span></span></div></div>';
    };

	ShowEvent.prototype.onCustomEvent = function () {
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

	return ShowEvent;
})();