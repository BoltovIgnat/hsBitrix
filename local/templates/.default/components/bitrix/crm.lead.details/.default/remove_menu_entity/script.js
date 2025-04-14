var BX = window.BX;

BX.namespace('RemoveMenuEntity');

BX.RemoveMenuEntity = (function ()
{
	var RemoveMenuEntity = function ()
	{
		console.log('Constructor called');
		this.addEvent();
	};

	RemoveMenuEntity.prototype.addEvent = function() {
		console.log('addEvent called');
        this.removeFields();
    };

	RemoveMenuEntity.prototype.removeFields = function(fields){
		console.log('removeFields called');
		var me = this;
        var timer = setInterval(function(){
			console.log('Timer running...');
			if ($('[data-tab-id="main"]').length) {
				console.log('Found main tab:', $('[data-tab-id="main"]').length);
				console.log('Removing elements...');
				$('#crm_scope_detail_c_lead__crm_rest_marketplace').remove();
				$('#crm_scope_detail_c_lead__more_button').remove();
				clearInterval(timer);
			}
        }, 100); // Увеличьте интервал для лучшей диагностики
    };

	return RemoveMenuEntity;
})();