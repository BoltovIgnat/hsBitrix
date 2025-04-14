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
				$('[data-id="crm_rest_marketplace"]').hide();
				$('#crm_scope_detail_c_deal__more_button').hide();
				clearInterval(timer);
			}
        }, 1);
    };

	return RemoveMenuEntity;
})();