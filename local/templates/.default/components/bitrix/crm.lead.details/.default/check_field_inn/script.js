var BX = window.BX;

BX.namespace('CheckFieldInn');

BX.CheckFieldInn = (function ()
{
	var CheckFieldInn = function (field,leadID)
	{
		this.field = field || null;
		this.leadID = leadID || null;
        
/*         BX.addCustomEvent("onPullEvent-hs_lead_detail", function(command,params,sys) {
            comm = command || "";
            if (comm == "add_comment_to_lead") {
                if (params.COMMENT?.length > 0) {
                    let cid = params.LEAD_ID+"_"+sys.server_time_unix;
                    Comments = sessionStorage.lead_comments;
                    if (!Comments) {
                        Comments = ['1'];
                    }
                    else {
                        Comments = JSON.parse(Comments);
                    }

                    if (!Comments.includes(cid)) {
                        Comments.push(cid);
                        sessionStorage.lead_comments = JSON.stringify(Comments);
                        BX.rest.callMethod(
                            "crm.timeline.comment.add",
                            {
                                fields:
                                {
                                    "ENTITY_ID": params.LEAD_ID,
                                    "ENTITY_TYPE": "lead",
                                    "COMMENT": params.COMMENT
                                },
                                function(result)
                                {
                                    console.log(result);
                                }
    
                            },
                        );
                    }
                }
            }
        }); */

        $(document).on('click', '.ui-btn-success', BX.delegate(function (params) {
            setTimeout(() => {
                this.hideFields(this.field);
                this.showFields(this.field);
            }, 2000);
        }, this)); 

         BX.addCustomEvent("BX.Crm.EntityEditor:onControlModeChange", BX.delegate(function (params) {
            setTimeout(() => {
                this.hideFields(this.field);
                this.showFields(this.field);
                //$('input[name="'+this.field+'"]').focus();
            }, 300);
        }, this)); 
         BX.addCustomEvent("BX.UI.EntityUserFieldLayoutLoader:onUserFieldDeployed", BX.delegate(function (params) {
            setTimeout(() => {
                this.hideFields(this.field);
                this.showFields(this.field);
                //$('input[name="'+this.field+'"]').focus();
            }, 100);
        }, this)); 

        BX.addCustomEvent("BX.Crm.EntityEditor:onCancel", BX.delegate(function (params) {
            setTimeout(() => {
                this.hideFields(this.field);
                this.showFields(this.field);
            }, 2000);
        }, this));
                
        if(this.field) {
            this.checkField();
            setTimeout(() => {
                this.curInn = $('div[data-cid="'+this.field+'"]').find('.field-item').text().trim();
                this.hideFields(this.field);
                this.showFields(this.field);
            }, 500);
        }

	};

	CheckFieldInn.prototype.checkField = function(){
        var me = this;

        $(document).on('focus keyup', '[name="'+this.field+'"]', function() {
			const value = $(this).val();
			const valid = me.isValidInn(value);
            me.hideFields(me.field);
			if(!valid) {
				$(this).addClass('field-is-error');
				$(this).closest('.field-wrap').find('.field-error').remove();
				$(this).closest('.field-wrap').append('<span class="field-error">Ошибка при вводе ИНН</span>');
			} else {
                $(this).removeClass('field-is-error');
				$(this).closest('.field-wrap').find('.field-error').remove();

                me.showFields(me.field);
			}

		});

    };
	
	CheckFieldInn.prototype.isValidInn = function(i) {
        if ( i.match(/\D/) ) return false;

        var inn = i.match(/(\d)/g);

        if ( inn?.length == 10 )
        {
            return inn[9] == String(((
                        2*inn[0] + 4*inn[1] + 10*inn[2] +
                        3*inn[3] + 5*inn[4] +  9*inn[5] +
                        4*inn[6] + 6*inn[7] +  8*inn[8]
                    ) % 11) % 10);
        }
        else if ( inn?.length == 12 )
        {
            return inn[10] == String(((
                        7*inn[0] + 2*inn[1] + 4*inn[2] +
                        10*inn[3] + 3*inn[4] + 5*inn[5] +
                        9*inn[6] + 4*inn[7] + 6*inn[8] +
                        8*inn[9]
                    ) % 11) % 10) && inn[11] == String(((
                        3*inn[0] +  7*inn[1] + 2*inn[2] +
                        4*inn[3] + 10*inn[4] + 3*inn[5] +
                        5*inn[6] +  9*inn[7] + 4*inn[8] +
                        6*inn[9] +  8*inn[10]
                    ) % 11) % 10);
        }

        return false;
    }

    CheckFieldInn.prototype.showFields = function(field) {
        let me = this;
        let inn = $('div[data-cid="'+field+'"]').find('.field-item').text().trim() || 0;
        let block = $('div[data-cid="'+field+'"]').closest('.ui-entity-editor-section-content');

        if (inn == 0) {
            inn = $('input[name="UF_CRM_1665408959"]').val();
        }
        if (inn) {
            if (inn.length > 2) {
                if (inn?.length > 10) {
                    $('input[name="UF_CRM_1662816497"]').val(" ");
                    $('input[name="UF_CRM_1662816497"]').html(" ");
                    $('div[data-cid="UF_CRM_1681056193342"]').show();
                    $('div[data-cid="UF_CRM_1640606874923"]').show();

                }
                else {
                    $('div[data-cid="UF_CRM_1662816497"]').show();
                    $('input[name="UF_CRM_1681056193342"]').val(" ");
                    $('input[name="UF_CRM_1681056193342"]').html(" ");
                    $('input[name="UF_CRM_1640606874923"]').val(" ");
                    $('input[name="UF_CRM_1640606874923"]').html(" ");
                }
                
                $('div[data-cid="UF_CRM_1679253485"]').show();
                $('div[data-cid="UF_CRM_1663624786260"]').show();
                $('div[data-cid="UF_CRM_1712040814984"]').show();
                $('div[data-cid="UF_CRM_1664452521905"]').show();
                $('div[data-cid="UF_CRM_1711918964929"]').show();
            }
        }

        if (!me.curInn) {
            $('input[name="UF_CRM_1662816497"]').attr('field-is-error');
            $('div[data-name="UF_CRM_1679253485"]').addClass('field-is-error');
            $('input[name="UF_CRM_1663624786260"]').addClass('field-is-error');
            $('input[name="UF_CRM_1681056193342"]').addClass('field-is-error');
            $('input[name="UF_CRM_1640606874923"]').addClass('field-is-error');
            $('input[name="UF_CRM_1712040814984"]').addClass('field-is-error');
        }

        $(block).on('blur', 'input,select', function() {
            if ($(this).val()?.length < 4) {
                $(this).addClass('field-is-error');
                $(this).removeClass('field-is-success');
            }
            else {
                $(this).addClass('field-is-success');
            }      
        });

        $('input',block).each(function(){
            if (parseInt($(this).val()) == 0) {
                $(this).val("");  
            }
            if ($(this).val()?.length < 4) {
                $(this).addClass('field-is-error');
                $(this).removeClass('field-is-success');
            }
            else {
                $(this).addClass('field-is-success');
            }
        });

    }

    CheckFieldInn.prototype.hideFields = function(field) {
        let block = $('div[data-cid="'+field+'"]').closest('.ui-entity-editor-section-content');
        $('.ui-entity-editor-content-block-click-editable', block).each( function() {
            if ($(this).attr('data-cid') != field) {
                if ($(this).attr('data-cid') != "UF_CRM_1664452521905") {
                    $(this).hide();
                }
            }
        }); 
        $('div', block).each( function() {
            if (parseInt($(this).find('.field-item').text()) == 0) {
                $(this).find('.field-item').html("&nbsp;");
            }
            if ($(this).attr('data-cid')) {
                if ($(this).attr('data-cid') != field) {
                    if ($(this).attr('data-cid') != "UF_CRM_1664452521905") {
                        $(this).hide();
                    }
                }
            }
        });

        $('div[data-cid="UF_CRM_1662816497"]').hide();
        //$('div[data-cid="UF_CRM_1664452521905"]').hide();
    }

	return CheckFieldInn;
})();