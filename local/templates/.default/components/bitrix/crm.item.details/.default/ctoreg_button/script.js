var BX = window.BX;

BX.namespace('CToRegButton');

BX.CToRegButton = (function ()
{
	var CToRegButton = function (eid,uid)
	{
		this.eid = eid;
		this.uid = uid;

		this.addEvent();

		BX.addCustomEvent("onAjaxSuccess", BX.delegate(function(params) {
            setTimeout(() => {
				this.addEvent();
			}, 500);
        }, this));

		BX.addCustomEvent("BX.Crm.EntityEditor:onControlModeChange", BX.delegate(function(params) {
            setTimeout(() => {
				this.addEvent();
			}, 500);
        }, this));
		
		$(document).on('click', '.ctoregbutton', BX.delegate(function (e) {
			let cid = $(e.currentTarget).attr('cid');
			this.ctoregbutton(cid);
        }, this)); 

	};

	CToRegButton.prototype.ctoregbutton = function(cid) {
		let url = '/ajax/Project/contactForProjectStartBP/?cid='+cid+'&uid='+this.uid+'&eid='+this.eid+'&sessid='+BX.bitrix_sessid();
		
 		$.ajax({
			url: url,
			method: 'post',
			dataType: 'json',
			data: {},
			success: function(res){
				//$(this).html('Отвязан');
			}
		});
    };

	CToRegButton.prototype.addEvent = function() {
        this.addctoregbutton();
    };

	CToRegButton.prototype.addctoregbutton = function(){
		setTimeout(() => {
			let block = $('div[data-field-tag="CONTACT"]').parent('.crm-entity-widget-content-block-inner-container');
			$('.crm-entity-widget-client-block', block).each(function(e){
				let url = $(this).find('.crm-entity-widget-participants-block').find('.crm-entity-widget-client-box-name-row').find('a').attr("href");
				let arr = url.split('/');
				$(this).find('.crm-entity-widget-participants-block').append('<div class="fired_button_wrap"><div class="fired_buttons"><a class="button ctoregbutton" cid="'+arr[4]+'" href="javascript:;">Контакт для рег. проекта</a></div></div>');

			})
		}, 300);
    };

	return CToRegButton;
})();