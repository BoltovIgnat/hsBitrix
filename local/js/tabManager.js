const hsTabManager = BX.namespace('hsTabManager');

hsTabManager.companyTabManagerFind = function () {

    var detailManagerFinder = setInterval(function(){
        if (BX.Crm.EntityDetailManager?.items) {
            clearInterval(detailManagerFinder);
            hsTabManager.companyTabManagerProcess(BX.Crm.EntityDetailManager.items);
        }
    }, 100);
    
}

hsTabManager.companyTabManagerProcess = function (manager) {

    tabManager = manager[Object.keys(manager)[0]]._tabManager;
    
    var a = $('div[data-field-tag="COMPANY"]').parent().find('a')[0]?.href;
    if (a) {
        a = a.replace("https://crm.highsystem.ru/crm/company/details/", "");
        a = a.replace("/", "");
        var companyID = parseInt(a);
    }

    //console.log(companyID);

    // Сформируем параметры вкладки
    var tabData = {};
    // Идентификатор вкладки
    tabData.id = 'tab_foo';
    // Наименование вкладки
    tabData.name = 'Закупки клиентов';
    // Контент вкладки, если мы хотим чтобы во вкладке был статичный контент передаем его сюда, можно через параметры функции, в противном случае данный параметр можно опустить
    // tabData.html = '<div style="color: green">Foo tab content</div>';
    // Создадим html узел отвечающий за контент вкладки
    var tabContainer = BX.create(
        'div',
        {
            attrs: {
                className: 'crm-entity-section crm-entity-section-info crm-entity-section-tab-content-hide crm-entity-section-above-overlay',
                style:'display:none'
            },
            dataset: {
                'tabId': tabData.id,
            },
        // html: tabData.html,
        }
    );
    // Добавим созданный контейнер к остальным контейнерам вкладок
    BX.append(
        tabContainer,
        tabManager._container
    );
    // Создадим html узел отвечающий за кнопку вкладки в меню навигации карточки
    var tabMenuContainer = BX.create(
        'div',
        {
            attrs: {
                className: 'main-buttons-item',
                id: 'crm_scope_detail_c_company__tab_foo',
                onclick:'BX.onCustomEvent("'+tabManager._id+'_click_tab_foo");',
                draggable:"true",
                tabindex:"-1",
            },
            dataset: {
                tabId: tabData.id,
                item:JSON.stringify({
                    ID:"crm_scope_detail_c_company__tab_foo",
                    TEXT:"Закупки клиентов",
                    ON_CLICK:"BX.onCustomEvent('"+tabManager._id+"_click_tab_foo');",
                    TITLE:"",
                    HTML:"",
                    URL:"",
                    CLASS:"",
                    CLASS_SUBMENU_ITEM:"",
                    DATA_ID:"crm_scope_detail_c_company__tab_foo",
                    MAX_COUNTER_SIZE:99,
                    IS_LOCKED:false,
                    IS_DISABLED:"true",
                    SUB_LINK:false,
                    SUPER_TITLE:false,
                    SORT:11,
                    IS_ACTIVE:false,
                    IS_PASSIVE:false,
                    HAS_MENU:false,
                    HAS_CHILD:false
                }),
                link:"tab_foo"
            },
            html: '<span class=\"main-buttons-item-link\"><span class=\"main-buttons-item-icon\"></span><span class=\"main-buttons-item-text\"><span class=\"main-buttons-item-drag-button\" data-slider-ignore-autobinding=\"true\"></span><span class=\"main-buttons-item-text-title\"><span class=\"main-buttons-item-text-box\">Закупки клиентов<span class=\"main-buttons-item-menu-arrow\"></span></span></span><span class=\"main-buttons-item-edit-button\" data-slider-ignore-autobinding=\"true\"></span><span class=\"main-buttons-item-text-marker\"></span></span><span class=\"main-buttons-item-counter\"></span></span>'
            ,
        }
    );

    // Добавим созданный пункт меню к остальным пунктам меню
    BX.append(
        tabMenuContainer,
        tabManager._menuManager.itemsContainer
    );

    // Если мы хотим подгружать контент вкладки динамически то опишем как надо это делать
    tabData.loader = {};
    // Адрес на который будет делаться запрос при первом показе вкладки
    tabData.loader.serviceUrl = '/ajax/Company/getCustomDocsForDeal/?sessid=' + BX.bitrix_sessid();
    // Параметры которые будут отправлены в ajax запросе, параметры передаются в массиве PARAMS
    tabData.loader.componentData = {id: tabManager._id,companyID: companyID};
    // Контейнер в который будет вставлен ответ сервера
    tabData.loader.container = tabContainer;
    // Идентификатор вкладки, так же попадет в массив PARAMS
    tabData.loader.tabId = tabData.id;
    // Добавим новую вкладку в менеджер вкладок
    tabManager._items.push(
        BX.Crm.EntityDetailTab.create(
            tabData.id,
            {
                manager: tabManager,
                data: tabData,
                container: tabContainer,
                menuContainer: tabMenuContainer,
            }
        )
    );
    //console.log(tabManager);
}
