const HSmain = BX.namespace('HSmain');

HSmain.disableAnswerButton = function () {
    let it = 0;
    let replyButtons = setInterval(function(){
        let buttons = $('.crm-timeline__card-top_checkbox');
        if (buttons?.length) {
            clearInterval(replyButtons);
            $.each(buttons, function(key, value){
                console.log($(buttons[key]));
                $(buttons[key]).remove('');
            });
        }
        it++;
        if (it > 20) {
            clearInterval(replyButtons);
        }
    }, 100);

}

var taskTimelineFinder = setInterval(function(){
    if (BX.Crm?.Timeline) {
        clearInterval(taskTimelineFinder);
        $('.crm-entity-stream-section-planned-task').each(function(){
			console.log(BX.Crm.Timeline);
			$(this).find('.crm-timeline__card-top_checkbox').remove();
        });
    }
}, 100);

BX.addCustomEvent("onPullEvent-tasks", function (eventObject, eventParams) {
    removeCheckboxCloseTask();
     setTimeout(function() {
        removeCheckboxCloseTask();
     },1000);
});

function removeCheckboxCloseTask(){
    $('.crm-entity-stream-section-planned-task').each(function(){
        $(this).find('.crm-timeline__card-top_checkbox').remove();
     });
}

/*
let originalBxOnCustomEvent = BX.onCustomEvent;
BX.onCustomEvent = function (eventObject, eventName, eventParams, secureParams)
{
    // onMenuItemHover например выбрасывает в другом порядке
    let realEventName = BX.type.isString(eventName) ?
        eventName : BX.type.isString(eventObject) ? eventObject : null;

    if (realEventName) {
        console.log(
            '%c' + realEventName, 
            'background: #222; color: #bada55; font-weight: bold; padding: 3px 4px;'
        );
    }

    console.dir({
        eventObject: eventObject,
        eventParams: eventParams,
        secureParams: secureParams
    });

    originalBxOnCustomEvent.apply(
        null, arguments
    );
}; 
*/