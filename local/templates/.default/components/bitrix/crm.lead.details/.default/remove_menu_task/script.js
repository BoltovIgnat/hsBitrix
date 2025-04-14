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
				$('#crm_scope_timeline_c_lead__wait').remove();
				$('#crm_scope_timeline_c_lead__activity_rest_applist').remove();
				$('#crm_scope_timeline_c_lead__meeting').remove();
				$('#crm_scope_timeline_c_lead__visit').remove();
				$('#crm_scope_timeline_c_lead__more_button').remove();
				$('#crm_scope_timeline_c_lead__whatsapp').remove();
				$('#crm_scope_timeline_c_lead__gotochat').remove();
				$('#crm_scope_timeline_c_lead__call').remove();
				$('#crm_scope_timeline_c_lead__todo').remove();
				clearInterval(timer);
			}
        }, 1);
    };

	return RemoveMenuTask;
})();