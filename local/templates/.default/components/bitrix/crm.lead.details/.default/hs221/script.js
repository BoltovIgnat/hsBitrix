var BX = window.BX;

BX.namespace('HS221');

BX.HS221 = (function () {
    var HS221 = function (params) {
        this.params = params || {};
        this.errorText = {};
        this.addEvent();
        //this.onCustomEvent();
    };

    HS221.prototype.addEvent = function () {
        const this_ = this;
        const data = {
            inn: this.params.fieldCodeInn['value'],
            address: this.params.fieldCodeUridAddress['value']
        };

        this.run(data);

        BX.addCustomEvent("BX.Crm.EntityEditor:onCancel", BX.delegate(function (params) {
            setTimeout(() => {
                this_.action();
            }, 300);
        }, this));

/*         BX.addCustomEvent("onAfterSubmit", BX.delegate(function (params) {
            const form = params._config.data;
            const inn = form.get(this.params.fieldCodeInn['code']);
            const innCode = this.params.fieldCodeUridAddress['code'];
            const addressCode = this.params.fieldCodeUridAddressAll;

            if (inn === '') {
                const data = {
                    sessid: BX.bitrix_sessid(),
                    action: 'clear',
                    id: this.params.id,
                    inn: innCode,
                    address: addressCode
                };
                setTimeout(() => {
                    this_.send(data);
                }, 500);
            }
        }, this)); */

        BX.addCustomEvent("onCrmEntityUpdate", BX.delegate(function (params) {
            const inn = params.entityData[this_.params.fieldCodeInn['code']];
            const address = params.entityData[this_.params.fieldCodeUridAddress['code']];
            const data = {
                inn: inn,
                address: address
            };
            this_.run(data);
        }, this));

        BX.addCustomEvent("BX.UI.EntityEditorField:onLayout", BX.delegate(function (params) {
            if (params._id === this_.params.fieldCodeUridAddress['code']) {
                const input = $('[name="' + this_.params.fieldCodeUridAddress['code'] + '"]');
                if (input.length) {
                    this_.dadata(input);
                }
            }

            if (params._id === this_.params.fieldCodeAddressDelivery) {
                const input = $('[name="' + this_.params.fieldCodeAddressDelivery + '"]');
                if (input.length) {
                    this_.dadata(input);
                }
            }
        }, this));

        $(document).on('input', '[name="' + this_.params.fieldCodeInn['code'] + '"]', function () {
            const value = $(this).val();
            const fieldAddress = $('[data-cid="' + this_.params.fieldCodeUridAddress['code']);
            if (value.length && this_.isValidInn(value)) {
                this_.show(fieldAddress);
            } else {
                this_.hide(fieldAddress);
            }
        });

        $(document).on('mouseover', 'a.crm-entity-widget-client-box-name', function () {
            $('body').addClass('hidden-requisite');
            setTimeout(function () {
                this_.replaceTextJson();
                $('body').removeClass('hidden-requisite');
            }, 1000);
        });

        $(document).on('mouseover', '.ui-entity-editor-content-block', function () {
            $('body').addClass('hidden-requisite');
            setTimeout(function () {
                this_.replaceTextJson();
                $('body').removeClass('hidden-requisite');
            }, 1000);
        });

        $(document).on('focus', '[name="' + this_.params.fieldCodeUridAddress['code'] + '"]', function () {
            const value = $(this).val();
            if (parseInt(value) === 0) {
                $(this).val('');
            }
        });

    };

    HS221.prototype.replaceTextJson = function () {
        const container = $('.crm-rq-org-info-container .crm-rq-org-description');
        if (container.length) {
            const text = container.html();
            const a = text.replace(/Юридический.+}/gi, "");
            const b = a.replace(/Адрес\sдоставки.+}/gi, "");
            container.html(b);
        }
    };

    HS221.prototype.run = function (data) {
        //console.log(data);
        const this_ = this;
        const timer = setInterval(function () {
            if ($('[data-tab-id="main"]').length) {
                clearInterval(timer);
                setTimeout(() => {
                    this_.action(data);
                }, 300);
            }
        }, 1);
    };

    HS221.prototype.action = function (data) {
        if (this.params.fieldCodeUridAddress) {
            this.fieldAddress = $('[data-cid="' + this.params.fieldCodeUridAddress['code'] + '"]');
            this.fieldAddressInput = $('[name="' + this.params.fieldCodeUridAddress['code'] + '"]');
        }

        if (this.params.fieldCodeInn) {
            this.fieldInn = $('[data-cid="' + this.params.fieldCodeInn['code'] + '"]');
        }

        if (this.fieldInn.length && this.fieldAddress.length) {
            this.fieldInn.after(this.fieldAddress);
        }

        if (data !== undefined && (data.inn['IS_EMPTY'] || !this.isValidInn(data.inn['VALUE']))) {
            this.fieldAddress.hide();
        }
    };

    HS221.prototype.hide = function (field) {
        if (field.length) {
            field.hide();
        }
    };

    HS221.prototype.show = function (field) {
        if (field.length) {
            field.show();
        }
    };

    HS221.prototype.isValidInn = function (i) {
        if (i.match(/\D/)) return false;

        var inn = i.match(/(\d)/g);

        if (inn.length == 12) {
            return inn[10] == String(((
                7 * inn[0] + 2 * inn[1] + 4 * inn[2] +
                10 * inn[3] + 3 * inn[4] + 5 * inn[5] +
                9 * inn[6] + 4 * inn[7] + 6 * inn[8] +
                8 * inn[9]
            ) % 11) % 10) && inn[11] == String(((
                3 * inn[0] + 7 * inn[1] + 2 * inn[2] +
                4 * inn[3] + 10 * inn[4] + 3 * inn[5] +
                5 * inn[6] + 9 * inn[7] + 4 * inn[8] +
                6 * inn[9] + 8 * inn[10]
            ) % 11) % 10);
        }

        return false;
    }

    HS221.prototype.dadata = function (field) {
        const this_ = this;
        field.suggestions({
            token: this_.params.apikey,
            type: "ADDRESS",
            /* Вызывается, когда пользователь выбирает одну из подсказок */
            onSelect: function (suggestion) {
                const data = {
                    country: suggestion.data.country || '',
                    city_with_type: suggestion.data.city_with_type || '',
                    region: suggestion.data.region || '',
                    settlement: suggestion.data.settlement || '',
                    settlement_type_full: suggestion.data.settlement_type_full || '',
                    street_with_type: suggestion.data.street_with_type || '',
                    house: suggestion.data.house || '',
                    house_type_full: suggestion.data.house_type_full || '',
                    block_type_full: suggestion.data.block_type_full || '',
                    block: suggestion.data.block || '',
                    postal_code: suggestion.data.postal_code || '',
                    flat: suggestion.data.flat || '',
                    flat_type_full: suggestion.data.flat_type_full || '',
                    fias_id: suggestion.data.fias_id || '',
                    kladr_id: suggestion.data.kladr_id || ''
                };

                if ((8 > parseInt(suggestion.data.fias_level)) || (!suggestion.data.block && !suggestion.data.house)) {
                    this_.setErrorNoFullAddress(field.attr('name'));
                } else {
                    this_.save(data, field.attr('name'));
                    this_.removeError(field.attr('name'));
                }
                this_.showEror();
            },
            onSearchComplete: function (suggestion, res) {
                if (!res.length) {
                    this_.setError(field.attr('name'));
                } else {
                    this_.errorCancel();
                }
                this_.showEror();
            },
            onSearchError: function () {
                this_.showEror('Нет доступа к сервису адресов');
            },
            onSelectNothing: function () {
                this_.setError(field.attr('name'));
                this_.showEror();
            }
        });
    }

    HS221.prototype.save = function (info, name) {
        const data = {
            data: info,
            sessid: BX.bitrix_sessid(),
            id: this.params.id,
            action: "save"
        };

        if (name === this.params.fieldCodeUridAddress['code']) {
            data.addressAll = this.params.fieldCodeUridAddressAll;
            data.address = this.params.fieldCodeUridAddress['code'];
            data.addressValue = $('[name="' + this.params.fieldCodeUridAddress['code'] + '"]').val() || '';
            if ($('[name="' + this.params.fieldCodeUridAddressAll + '"]').length) {
                $('[name="' + this.params.fieldCodeUridAddressAll + '"]').val(JSON.stringify(info));
            }
        } else if (name === this.params.fieldCodeAddressDelivery) {
            data.addressDelivery = this.params.fieldCodeAddressDelivery;
            data.addressDeliveryValue = $('[name="' + this.params.fieldCodeAddressDelivery + '"]').val() || '';
            data.addressDeliveryJson = this.params.fieldCodeAddressDeliveryJson;
            if ($('[name="' + this.params.fieldCodeAddressDeliveryJson + '"]').length) {
                $('[name="' + this.params.fieldCodeAddressDeliveryJson + '"]').val(JSON.stringify(info));
            }
        }

        this.send(data);
    }

    HS221.prototype.send = function (data) {
        var this_ = this;

        BX.ajax({
            url: this_.params.url,
            method: 'POST',
            dataType: 'json',
            data: data,
            onsuccess: function () {
            },
            onfailure: function () {
            },
        });
    };

    HS221.prototype.showEror = function (string = '') {
        const container = $('.crm-section-control-active');

        if (!string.length) {
            let arr = [];
            if (this.errorText.address !== '' && this.errorText.address !== undefined) {
                arr.push(this.errorText.address);
            }
            if (this.errorText.delivery !== '' && this.errorText.delivery !== undefined) {
                arr.push(this.errorText.delivery);
            }

            string = arr.join('<br />');
        }

        if (string !== '') {
            container.find('.ui-entity-section-control-error-block').html('<div class="ui-entity-section-control-error-text">' + string + '</div>').attr('style', '');
        } else {
            this.errorCancel();
        }
    };

    HS221.prototype.setError = function (name) {
        if (this.params.fieldCodeUridAddress['code'] === name) {
            this.errorText.address = 'Не корректно введен юридический адрес';
        } else if (this.params.fieldCodeAddressDelivery === name) {
            this.errorText.delivery = 'Не корректно введен адрес доставки';
        }
    };

    HS221.prototype.setErrorNoFullAddress = function (name) {
        if (this.params.fieldCodeUridAddress['code'] === name) {
            this.errorText.address = 'Вероятно, неполный юридический адрес';
        } else if (this.params.fieldCodeAddressDelivery === name) {
            this.errorText.delivery = 'Вероятно, неполный адрес доставки';
        }
    };

    HS221.prototype.removeError = function (name) {
        if (this.params.fieldCodeUridAddress['code'] === name) {
            this.errorText.address = '';
        } else if (this.params.fieldCodeAddressDelivery === name) {
            this.errorText.delivery = '';
        }
    };

    HS221.prototype.errorCancel = function () {
        const container = $('.crm-section-control-active');
        container.find('.ui-entity-section-control-error-block').html('').css('min-height', '0px');
    };

    HS221.prototype.onCustomEvent = function () {
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

    return HS221;
})();