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
				$('[data-id="email"]').hide();
				clearInterval(timer);
			}
        }, 100);
    };

	return RemoveMenuTask;
})();