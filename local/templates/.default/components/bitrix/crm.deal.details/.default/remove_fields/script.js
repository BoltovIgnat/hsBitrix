var BX = window.BX;

BX.namespace('RemoveFields');

BX.RemoveFields = (function ()
{
	var RemoveFields = function (parameters)
	{
		this.FIELDS = parameters.FIELDS;
		this.addEvent();
	};

	RemoveFields.prototype.addEvent = function() {
        this.removeFields(this.FIELDS);
    };

	RemoveFields.prototype.removeFields = function(fields){
		var me = this;
        var timer = setInterval(function(){
			if($('[data-tab-id="main"]').length) {
				fields.forEach((entry)=> {
					const container = $('[data-cid="'+entry+'"]');
					if(container.length) {
						container.remove();
					}
				});
				$('.crm-entity-stream-container').remove();
				$('[data-id="tab_event"]').remove();
				$('[data-cid="additional"]').remove();
				clearInterval(timer);
			}
        }, 1);
    };

	return RemoveFields;
})();