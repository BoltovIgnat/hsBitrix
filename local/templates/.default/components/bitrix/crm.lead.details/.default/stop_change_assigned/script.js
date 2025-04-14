var BX = window.BX;

BX.namespace('StopChangeAssigned');

BX.StopChangeAssigned = (function ()
{
	var StopChangeAssigned = function ()
	{
		this.addEvent();
	};

	StopChangeAssigned.prototype.addEvent = function() {
        this.run();
    };

	StopChangeAssigned.prototype.run = function(){
		var me = this;
        var timer = setInterval(function(){
			if($('[data-tab-id="main"]').length) {
				$field = $('[data-cid="ASSIGNED_BY_ID"]');
				if($field.length) {
					$field.find('.crm-widget-employee-change').remove();
				}
				clearInterval(timer);
			}
        }, 1);
    };

	return StopChangeAssigned;
})();