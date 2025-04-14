var BX = window.BX;

BX.namespace('FiredButtons');

BX.FiredButtons = (function ()
{
	var FiredButtons = function (eid,uid)
	{
		this.eid = eid;
		this.uid = uid;

		this.addEvent();

		BX.addCustomEvent("onAjaxSuccess", BX.delegate(function(params) {
            this.addEvent();
        }, this));

		$(document).on('click', '.firedbutton', BX.delegate(function (e) {
			let cid = $(e.currentTarget).attr('cid');
			this.firedButton(cid);
        }, this)); 

		$(document).on('click', '.unlinkbutton', BX.delegate(function (e) {
			let cid = $(e.currentTarget).attr('cid');
			this.unlinkbutton(cid);
        }, this)); 

	};

	FiredButtons.prototype.firedButton = function(cid) {
		let url = '/ajax/Company/firedContact/?type=fired&cid='+cid+'&uid='+this.uid+'&eid='+this.eid+'&sessid='+BX.bitrix_sessid();
		$.ajax({
			url: url,
			method: 'post',
			dataType: 'json',
			data: {},
			success: function(res){
				$(this).html('Отвязан');
			}
		});
    };

	FiredButtons.prototype.unlinkbutton = function(cid) { 
		let url = '/ajax/Company/firedContact/?type=unlink&cid='+cid+'&uid='+this.uid+'&eid='+this.eid+'&sessid='+BX.bitrix_sessid();
		$.ajax({
			url: url,
			method: 'post',
			dataType: 'json',
			data: {},
			success: function(res){
				$(this).html('Отвязан');
			}
		});
    };

	FiredButtons.prototype.addEvent = function() {
        this.addFiredButtons();
    };

	FiredButtons.prototype.addFiredButtons = function(){
		setTimeout(() => {
			let block = $('div[data-field-tag="CONTACT"]').parent('.crm-entity-widget-content-block-inner-container');
			$('.crm-entity-widget-client-block', block).each(function(e){
				let url = $(this).find('.crm-entity-widget-participants-block').find('.crm-entity-widget-client-box-name-row').find('a').attr("href");
				let arr = url.split('/');
				$(this).find('.crm-entity-widget-participants-block').append('<div class="fired_button_wrap"><div class="fired_buttons"><a class="button firedbutton" cid="'+arr[4]+'" href="javascript:;">Не работает</a><a class="button unlinkbutton" cid="'+arr[4]+'" href="javascript:;">Отвязать</a></div></div>');

			})
		}, 300);
    };

	return FiredButtons;
})();