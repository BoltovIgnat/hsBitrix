const DBBOmenuRights = BX.namespace('DBBOmenuRights');

const changeTaskUpdate = (shopID) => {


    const getHideUrl = (shopID) => {

        BX.ajax({
            url: '/local/ajax/getHideMenuItem.php?shopID=' + shopID,
            method: 'GET',
            dataType: 'json',
            async: true,
            onsuccess: function(data) {
                if (data.status === 'OK' || data.status === 'ADMIN') {
                    $('.main-buttons').addClass('show-menu');
					$('#content-table').addClass('active');
					$('.ui-slider-page').addClass('active');
                } else if(data.status === 'CUSTOM') {
                    changeMenu(data.menu);
                }
            },
            onfailure: function(data) {

            }
        });
    }

    const changeMenu = (menu) => {

		const panelMenu = BX('crm_control_panel_menu');
		const removeMenu = [];
		
        if(panelMenu){
            const items = panelMenu.querySelectorAll('.main-buttons-item');
            items.forEach(elem =>{
				const id = elem.getAttribute('data-id');
				const item = elem.getAttribute('data-item');
				Object.keys(menu).forEach(key => {
					if(id === key && menu[key].HIDE === 'Y') {
						elem.remove();
						return;
					}
				});
            });
        }

		Object.keys(menu).forEach(key => {
			if(menu[key].LINK !== undefined) {
				removeMenu.push(menu[key].LINK);
			}
			if(menu[key].CHILDS !== undefined) {
				menu[key].CHILDS.forEach(value => {
					removeMenu.push(value);
				});
			}
		});

		if(removeMenu) {
			let html = "<style>";
			const body = $('body');
			removeMenu.forEach(url => {
				html += ".main-buttons-menu-item[href='"+ url +"']{display:none !important;}";
				if(document.location.pathname === url) {
					console.log(url);
					$('#content-table').html('<p class="error">Access denied</p>');
					if($('.ui-slider-page').length) {
						$('.ui-slider-page').html('<p class="error">Access denied</p>');
					}
				}
			});
			html += "</style>";
			body.prepend(html);
		}

		$('.main-buttons').addClass('show-menu');
		$('#content-table').addClass('active');
		$('.ui-slider-page').addClass('active');
    }

    const onCustomEvent = () => {
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
	
	//onCustomEvent();
    getHideUrl(shopID);

}

DBBOmenuRights.init = function (shopID) {
    changeTaskUpdate(shopID);
};