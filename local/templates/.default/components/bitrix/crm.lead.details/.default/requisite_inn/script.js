var BX = window.BX;

BX.namespace('CompanyReq');

BX.CompanyReq = (function ()
{
	var CompanyReq = function (parameters)
	{
		this.RQ_INN = parameters.RQ_INN || null;
		this.parameters = parameters || {};
        this.addEvent();
	};

	CompanyReq.prototype.addEvent = function() {
        this.addField(this.RQ_INN);

        BX.addCustomEvent("BX.Crm.EntityEditor:onSave", BX.delegate(function(params) {
            this.addField(this.RQ_INN);
        }, this));

        BX.addCustomEvent("BX.Crm.EntityEditor:onRelease", BX.delegate(function(params) {
            this.addField(this.RQ_INN);
        }, this));

        BX.addCustomEvent("BX.Crm.EntityEditor:onCancel", BX.delegate(function(params) {
            this.addField(this.RQ_INN);
        }, this));

		BX.addCustomEvent("BX.Crm.EntityEditor:onControlModeChange", BX.delegate(function(params) {
            this.addField(this.RQ_INN);
        }, this));
    };

	CompanyReq.prototype.addField = function(RQ_INN){
		var me = this;
        var timer = setInterval(function(){
            var container = $('[data-cid="CLIENT"]');

            if(container.length) {
				var widget = container.find('.crm-entity-widget-client-block');
				var contactWidget = $('[data-field-tag="COMPANY"]');
				var widget = contactWidget.siblings('.crm-entity-widget-client-block');
				container.find('.crm-entity-widget-content-block-inner').addClass('bg-' + me.parameters.class[me.parameters.type]);

                if(widget.length && RQ_INN) {
                    var div = widget[0].getElementsByClassName("crm-entity-widget-client-contact-custom");

                    if(div) {
                        BX.remove(div[0]);
                    }

                    var child = BX.create(
                        "div",
                        {
                            props:
                            {
                                className: "crm-entity-widget-client-contact crm-entity-widget-client-contact-custom"
                            },
                            children: [
                                BX.create(
                                    "div",
                                    {
                                        props:
                                        {
                                            className: "crm-entity-widget-client-contact-item crm-entity-widget-client-contact-uf"
                                        },
										text: 'ИНН: ' + me.RQ_INN
                                    }
                                )
                            ]
                        }
                    );

                    widget[0].appendChild(
                        child
                    );
                }
				clearInterval(timer);
            }
        }, 1);
    };

	return CompanyReq;
})();