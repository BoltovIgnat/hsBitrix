var BX = window.BX;

BX.namespace('RemoveMenuEntity');

BX.RemoveMenuEntity = (function ()
{
	var RemoveMenuEntity = function ()
	{
		this.addEvent();
	};

	RemoveMenuEntity.prototype.addEvent = function() {
        this.removeFields();
    };

	RemoveMenuEntity.prototype.removeFields = function(fields){
		var me = this;
        var timer = setInterval(function(){
			if($('[data-tab-id="main"]').length) {
				$('#crm_scope_detail_c_contact__crm_rest_marketplace').remove();
				$('#crm_scope_detail_c_contact__more_button').remove();
				clearInterval(timer);
			}
        }, 1);
    };

	return RemoveMenuEntity;
})();