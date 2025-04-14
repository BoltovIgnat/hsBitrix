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
                clearInterval(timer);
                var widget = container.find('.crm-entity-widget-client-block');
                var contactWidget = $('[data-field-tag="COMPANY"]');
                var widget = contactWidget.siblings('.crm-entity-widget-client-block');
                container.find('.crm-entity-widget-content-block-inner').addClass('bg-' + me.parameters.class[me.parameters.type]);

                if(widget.length && me.RQ_INN) {
                    var div = widget[0].getElementsByClassName("crm-entity-widget-client-contact-custom");

                    if(div) {
                        BX.remove(div[0]);
                    }

                    if(me.parameters.owners === false || me.parameters.owners === '') {
                        me.parameters.owners = [];
                    }

                    const revenue = "<br />" + 'Выручка: ' + me.parameters.revenue || '';
/*                    const profit = "<br />" + 'Прибыль: ' + me.parameters.profit || '';
                    const reliability = "<br />" + 'Стоимость компании: ' + me.parameters.reliability || '';
                    const defend = "<br />" + 'Выступал в судах, как ответчик: ' + me.parameters.defend;
                    const complain = "<br />" + 'Наличие жалоб: ' + me.parameters.complain || '';
                    const tender = "<br />" + 'Участие в тендере: ' + me.parameters.tender || '';*/
                    const age = "<br />" + 'Период: ' + me.parameters.age || '';
                    const owners = "<br />" + 'Владельцы: ' + me.parameters.owners !== false ? me.parameters.owners.join(', ') : '';
                    const linked = "<br />" + 'Связанные лица: ' + me.parameters.linked || '';
                    const link = "<br />" + 'СБИС: <a href="'+ me.parameters.link +'" target="_blank">Перейти</a>'  || '';
                    const countStaff = "<br />" + 'Количество сотрудников: '+ me.parameters.count_staff || '';
/*                    const address = "<br />" + 'Адрес: '+ me.parameters.address || '';
                    const phone = "<br />" + 'Телефон: '+ me.parameters.phone || '';
                    const email = "<br />" + 'Email: '+ me.parameters.email || '';
                    const inn = "<br />" + 'ИНН (dadata): '+ me.parameters.inn || '';
                    const kpp = "<br />" + 'КПП (dadata): '+ me.parameters.kpp || '';
                    const director = "<br />" + 'Руководитель: '+ me.parameters.director || '';*/
                    const capital = "<br />" + 'Уставной капитал: '+ me.parameters.capital || '';

                    const colDeals = "<br /><b>" + 'Количество сделок в CRM: '+ me.parameters.DealsCount + ' успешно ' + me.parameters.successDealsCount || '';
                    const projectsActive = "</b><br /><b>" + 'Проектов: '+ me.parameters.projectsActive + ' успешно ' + me.parameters.projectsSuccess || '';
                    const projectsSuccess = "</b><br /><b>" + 'Активных сделок: '+ me.parameters.activeDealsCount + ' проектов ' + me.parameters.projectsActive || '' + '</b>';

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
                                        html: 'ИНН: ' + me.RQ_INN + revenue + ''
                                            + age + owners + linked + ''
                                            + countStaff + capital + link + colDeals + projectsActive + projectsSuccess
                                    }
                                )
                            ]
                        }
                    );

                    widget[0].appendChild(
                        child
                    );
                }
            }
        }, 1);
    };

    CompanyReq.prototype.onCustomEvent = function () {
        var originalBxOnCustomEvent = BX.onCustomEvent;
        BX.onCustomEvent = function (eventObject, eventName, eventParams, secureParams) {

            var logData = {
                eventObject: eventObject,
                eventName: eventName,
                eventParams: eventParams,
                eventParamsClassNames: [],
                secureParams: secureParams
            };

            for (var i in eventParams) {
                var param = eventParams;
                if (param !== null && typeof param == 'object' && param.constructor) {
                    logData['eventParamsClassNames'].push(param.constructor.name);
                } else {
                    logData['eventParamsClassNames'].push(null);
                }
            }

            console.log(logData);

            originalBxOnCustomEvent.apply(null, [eventObject, eventName, eventParams, secureParams]);
        };
    };

    return CompanyReq;
})();