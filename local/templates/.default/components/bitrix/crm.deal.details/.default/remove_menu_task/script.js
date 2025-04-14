var BX = window.BX;

BX.namespace('RemoveMenuTask');

BX.RemoveMenuTask = (function ()
{
	var RemoveMenuTask = function ()
	{
		this.addEvent();
	};

	RemoveMenuTask.prototype.addEvent = function() {
        this.removeFields();
    };

	RemoveMenuTask.prototype.removeFields = function(fields){
		var me = this;
        var timer = setInterval(function(){
			if($('[data-tab-id="main"]').length) {
				$('[data-id="wait"]').hide();
				$('[data-id="activity_rest_applist"]').hide();
				$('[data-id="meeting"]').hide();
				$('[data-id="visit"]').hide();
				$('[data-id="delivery"]').hide();
				$('#crm_scope_timeline_c_deal__more_button').hide();
				clearInterval(timer);
			}
        }, 1);
    };

	return RemoveMenuTask;
})();