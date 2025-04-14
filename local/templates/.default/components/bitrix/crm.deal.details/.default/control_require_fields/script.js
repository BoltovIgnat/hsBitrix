var BX = window.BX;

BX.namespace('ControlRequireFields');

BX.ControlRequireFields = (function ()
{
	var ControlRequireFields = function ()
	{
		this.params = {
			'SELECT_CODE': 'UF_CRM_1677608011',
			'COMMENT_CODE': 'UF_CRM_1677608081',
			'DATE_CODE': 'UF_CRM_1673199510673',
			'SELECT_VALUE_YES': '2302'
		};
		this.addEvent();
		//this.onCustomEvent();
	};

	ControlRequireFields.prototype.addEvent = function() {
		BX.addCustomEvent("BX.Crm.EntityEditor:onInit", BX.delegate(function(params) {
			//console.log(params);
			setTimeout(() => {
				this.run();
			}, 1000);
        }, this));
    };

	ControlRequireFields.prototype.run = function(){
		const frame = $('#popup-window-content-progressbar-entity-editor');
		const me = this;

		if(frame.length) {
			const select = frame.find('select[name="'+this.params.SELECT_CODE+'"]');
			select.val(this.params.SELECT_VALUE_YES);
			me.change(frame, true);

			select.on('change', function() {
				const state = $(this).val() === me.params.SELECT_VALUE_YES;
				me.change(frame, state);
			});
		}
    };

	ControlRequireFields.prototype.change = function(frame, state){
		const comment = frame.find('textarea[name="'+this.params.COMMENT_CODE+'"]');
		const date = frame.find('input[name="'+this.params.DATE_CODE+'"]');

		if(state) {
			comment.val(' ');
			date.val('');
		} else {
			const d = new Date();
			const options = { year: 'numeric', month: 'numeric', day: 'numeric' };
			date.val(d.toLocaleString('ru-RU', options));

			const text = comment.val().trim();
			if(!text.length) {
				comment.val('');
			}
		}
    };

	ControlRequireFields.prototype.onCustomEvent = function () {
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

	return ControlRequireFields;
})();