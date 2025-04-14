var BX = window.BX;

BX.namespace('filterStatic');

BX.filterStatic = (function(params) {
    const userId = params.userId;
    const UserName = params.UserName;

    console.
    // Основная функция установки фильтра
    function setAssignedFilter(gridId) {
        const filter = BX.Main.filterManager.getById(gridId);
        if (!filter) return;

        filter.getApi().setFields({
            "ASSIGNED_BY_ID": [userId],
            "ASSIGNED_BY_ID_label": [UserName]
        });
        filter.getApi().apply();
    }

    // Инициализация обработчиков
    function init() {
        BX.addCustomEvent("Grid::ready", function(gridData) {
            setAssignedFilter(gridData.containerId);
        });

        BX.addCustomEvent("Grid::beforeRequest", function(_, args) {
            setAssignedFilter(args.gridId);
        });
    }

    // Публичные методы
    return {
        init: init
    };
});

// Использование:
// BX.filterStatic({userId: 123, UserName: 'Иванов Иван'}).init();