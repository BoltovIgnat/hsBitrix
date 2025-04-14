var BX = window.BX;

BX.namespace('ContactField');

BX.ContactField = (function ()
{
	var ContactField = function (parameters)
	{
		this.contactId = parameters.contactId || null;
		this.fieldName = parameters.fieldName || null;
        this.fieldValue = parameters.fieldValue || null;
        this.fieldLabel = parameters.fieldLabel || null;
		this.fieldSelectValue = parameters.fieldSelectValue || {};
		this.number = parameters.number || null;
        this.url = parameters.url || null;
        this.currentValue = false;
        if(this.fieldName) {
            this.addEvent();
        }
	};

	ContactField.prototype.addEvent = function() {
        this.addField(this.number, this.fieldValue);

        BX.addCustomEvent("BX.Crm.EntityEditorSection:onLayout", BX.delegate(function(params) {
			if(params._id.indexOf("CONTACT") !== -1) {
				var timer1 = setInterval(BX.delegate(function(){
					var container = $('[data-cid="'+ params._id +'"]');
					var id = params._id.replace(/[^\d;]/g, '');
					if(container.length) {
						clearInterval(timer1);
						var add = container.find();
						if(id === this.contactId) {
							this.addEditFieldSelect(container);
						}
					}
				}, this), 1);
			}
        }, this));

        BX.addCustomEvent("BX.Crm.EntityEditor:onSave", BX.delegate(function(params) {
            //this.addField(this.number, this.fieldValue);
            this.save(this.number);
        }, this));

        BX.addCustomEvent("BX.Crm.EntityEditor:onRelease", BX.delegate(function(params) {
            //this.addField(this.number, this.fieldValue);
            this.save(this.number);
        }, this));

        BX.addCustomEvent("BX.Crm.EntityEditor:onCancel", BX.delegate(function(params) {
            this.addField(this.number, this.fieldValue);
        }, this));

		BX.addCustomEvent("BX.Crm.EntityEditor:onControlModeChange", BX.delegate(function(params) {
            this.addField(this.number, this.fieldValue);
        }, this));
    };

	ContactField.prototype.addField = function(number = 0, fieldValue){
		var me = this;
        var timer = setInterval(function(){
            var container = $('[data-cid="CLIENT"]');
            if(container.length) {
				var field = '';
				var widget = container.find('.crm-entity-widget-client-block');
				var contactWidget = $('[data-field-tag="CONTACT"]');
				var widget = contactWidget.siblings('.crm-entity-widget-client-block');
				field = BX(widget[number]);

                if(field) {
                    var value = '';
                    me.fieldSelectValue.ITEMS.forEach(function(item){
                        if(item.ID === fieldValue) {
                            value = item.VALUE;
                        }
                    }, this);

                    if(me.currentValue !== false) {
                        me.fieldSelectValue.ITEMS.forEach(function(item){
                            if(item.ID === me.currentValue) {
                                value = item.VALUE;
                            }
                        }, this);
                    }

                    var div = field.getElementsByClassName("crm-entity-widget-client-contact-custom");

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
                                        text: me.fieldLabel + ': ' + ((value === false || value === '') ? 'не выбрано' : value)
                                    }
                                )
                            ]
                        }
                    );

                    field.appendChild(
                        child
                    );
                }
				clearInterval(timer);
            }
        }, 1);
    };

    ContactField.prototype.addEditFieldSelect = function(container){
        var selectValue = this.fieldSelectValue;

		var exists = container.find('[data-cid="' + this.fieldName + '"]');

		if(exists.length) {
			return;
		}

        if(selectValue.ITEMS === undefined) {
            return '';
        }

        var items = [
            {
                title: "",
                value: "",
                selected: (this.fieldValue !== '') ? false : true
            }
        ];

        if(this.currentValue !== false) {
            this.fieldValue = this.currentValue;
        }

        selectValue.ITEMS.forEach(function(item){
            var selected = (this.fieldValue === item.ID) ? true : false;
            items.push({
                title: item.VALUE,
                value: item.ID,
                selected: selected
            });
        }, this);

        var select = this.drawSelect(items);
		var me = this;

        var child = BX.create(
            "div",
            {
                props:
                {
                    className: "ui-entity-editor-content-block ui-entity-editor-field-text"
                },
                attrs:
                {
                    "data-cid": me.fieldName
                },
                children: [
                    BX.create("div",
                    {
                        props:
                        {
                            className: "ui-entity-editor-block-title ui-entity-widget-content-block-title-edit"
                        },
                        children: [
                            BX.create("label",
                            {
                                props:
                                {
                                    className: "ui-entity-editor-block-title-text"
                                },
                                text: me.fieldLabel
                            })
                        ]
                    }),
                    BX.create("div",
                    {
                        props:
                        {
                            className: "ui-entity-editor-content-block",
                        },
                        children: [
                            select
                        ]
                    })
                ]
            }
        );

        container[0].appendChild(
            child
        );
    };

    ContactField.prototype.addEditFieldCheckbox = function(container){
        var checked = (parseInt(this.fieldValue) === 1) ? true : false;

        if(this.currentValue !== '') {
            checked = (parseInt(this.currentValue) === 1) ? true : false;
        }

		var me = this;

        var child = BX.create(
            "div",
            {
                props:
                {
                    className: "ui-entity-editor-content-block ui-entity-editor-field-text"
                },
                attrs:
                {
                    "data-cid": me.fieldName
                },
                children: [
                    BX.create("div",
                    {
                        props:
                        {
                            className: "ui-entity-editor-block-title ui-entity-widget-content-block-title-edit"
                        },
                        children: [
                            BX.create("label",
                            {
                                props:
                                {
                                    className: "ui-entity-editor-block-title-text"
                                },
                                text:me.fieldLabel
                            })
                        ]
                    }),
                    BX.create("div",
                    {
                        props:
                        {
                            className: "ui-entity-editor-content-block",
                        },
                        children: [
                            BX.create("span",
                            {
                                props:
                                {
                                    className: "field-wrap fields boolean"
                                },
                                children: [
                                    BX.create("span",
                                    {
                                        props: {
                                            className: "field-item fields boolean"
                                        },
                                        children: [
                                            BX.create("input",
                                            {
                                                props: {
                                                    className: "fields boolean",
                                                    type: "hidden",
                                                    value: 0,
                                                    name: me.fieldName
                                                }
                                            }),
                                            BX.create("label",
                                            {
                                                children: [
                                                   BX.create("input",
                                                   {
                                                       props: {
                                                           type: "checkbox",
                                                           value: 1,
                                                           checked: checked,
                                                           name: me.fieldName
                                                       }
                                                   }),
                                                   BX.create("span",
                                                   {
                                                       text: "да"
                                                   })
                                                ]
                                            })
                                        ]
                                    })
                                ]
                            })
                        ]
                    })
                ]
            }
        );
        container[0].appendChild(
            child
        );
    };

    ContactField.prototype.send = function(data) {
		var me = this;
		this.currentValue = data.fields[0].value;

        BX.ajax({
            url: this.url,
            method: 'POST',
            dataType: 'json',
            data: data,
            onsuccess: function(result){
                me.addField(me.number, data.fields[0].value);
            },
            onfailure: function(){
            },
        });
    };

    ContactField.prototype.drawSelect = function(option = []) {
        var optionValue = '';
        option.forEach(function (optionItem){
            var selected = (optionItem.selected) ? ' selected' : '';
            optionValue += '<option value="'+ optionItem.value +'"'+ selected +'>'+ optionItem.title +'</option>';
        }, this);

		var me = this;

        var html = BX.create("div", {
            props: {
                className: "ui-ctl ui-ctl-after-icon ui-ctl-dropdown"
            },
            children: [
                BX.create("div", {
                    props: {
                        className: "ui-ctl-after ui-ctl-icon-angle"
                    }
                }),
                BX.create("select", {
                    props: {
                        className: "ui-ctl-element",
                        name: me.fieldName
                    },
					dataset: {
						contact: this.contactId
					},
                    html: optionValue
                })
            ]
        });

        return html;
    };
    
    ContactField.prototype.save = function(number = 0) {
        var input = $('[name="'+ this.fieldName +'"]');
		this.addField(this.number, this.fieldValue);

		var me = this;
        input.each(function(){
			if(parseInt($(this).data("contact")) === parseInt(me.contactId)) {
				if($(this).attr("type") == 'checkbox') {
					var checked = $(this).prop("checked");
					var value = 0;
					if(checked) {
						value = 1;
					}
				} else {
					value = $(this).val();
				}

				var data = {
					sessid: BX.bitrix_sessid(),
					id: me.contactId,
					action: "updateContact",
					fields: [
						{
							name: me.fieldName,
							value: value
						}
					]
				};
				me.send(data);
				return;
			}
        });
    };

	return ContactField;
})();