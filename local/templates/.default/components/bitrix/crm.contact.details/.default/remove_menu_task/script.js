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
				$('#crm_scope_timeline_c_contact__visit').remove();
				$('#crm_scope_timeline_c_contact__activity_rest_applist').remove();
				$('#crm_scope_timeline_c_contact__meeting').remove();
				$('#crm_scope_timeline_c_contact__more_button').remove();
				clearInterval(timer);
			}
        }, 1);
    };

	return RemoveMenuTask;
})();