var BX = window.BX;

BX.namespace('StartBp');

BX.StartBp = (function ()
{
	var StartBp = function (pathAjax, $userId)
	{
		this.pathAjax = pathAjax;
		this.userId = $userId;
		this.addEvent();
	};

	StartBp.prototype.addEvent = function() {
		//this.onCustomEvent();
		const me = this;
        this.run();

		BX.addCustomEvent("BX.Main.grid:paramsUpdated", BX.delegate(function(params) {
            this.run();
        }, this));

		$(document).on('click', '[data-action="startbp-process"]', function(){
			const checked = $('.main-grid-table').find('[name="ID[]"]:checked');
			if(checked.length) {
				const company = [];
				const userInput = $('#main-user-selector-mail_client_config_queue').find('[data-role="tile-item"]');
				if(!userInput.length) {
					alert('Не указан пользователь');
					return;
				}
				checked.each(function(){
					company.push($(this).val());
				});
				if(company.length > 100) {
					alert('Не больше 100 компаний');
					return;
				}
				const userId = userInput.data('bx-id');
				me.Process(company, userId.replace('U', ''));
			}
		});
    };

	StartBp.prototype.run = function(){
		var me = this;
		const button = $('[data-action="startbp-process"]');
		if(!button.length) {
			var timer = setInterval(function(){
				if($('.main-grid-action-panel').length) {
					$field = $('.main-grid-control-panel-cell');
					if($field.length) {
						$field.append(me.GetButton());
						me.GetSearchInput($field);
					}
					clearInterval(timer);
				}
			}, 1);
		}
    };
	
	StartBp.prototype.GetButton = function() {
        return '<span class="main-grid-panel-control-container"><span class="main-grid-buttons" data-action="startbp-process" data-user="" title="Создать лиды на обзвон">Создать лиды на обзвон</span>';
    };

	StartBp.prototype.GetSearchInput = function(field) {
		let $container = $('#start-user-search');
		if($container.length) {
			$container.removeClass('hidden').appendTo(field);
			$('#start-user-search').wrap('<span class="main-grid-panel-control-container"/>');
		} else {
			$.ajax({
				url: document.location.href,
				data: { ajax111: '1' },
				type: 'get',
				dataType: 'html',
				success: function(res) {
					$container = $(res);
					$container.removeClass('hidden').appendTo(field);
					$('#start-user-search').wrap('<span class="main-grid-panel-control-container"/>');
				}
			});
		}
    };

	StartBp.prototype.Process = function(company, userId) {
		const me = this;
		const user = userId ? userId : me.userId;
		$.ajax({
			url: me.pathAjax,
			data: { userId: user, company: company, action: 'add-agent' },
			type: 'post',
			dataType: 'json',
			success: function(res) {
				alert('Задание добавлено');
			}
		});
    };

	StartBp.prototype.onCustomEvent = function () {
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

	return StartBp;
})();