(function() {

    $(".hs-excel-file").change(function() {
        filename = this.files[0].name;
        $('.ui-ctl-label-text').text(filename)
        //console.log();
    });

    $(document).on("click",".hs-excel-file-upload",function() {
        let $this = $(this)
        let formData = new FormData();
        // Готовим имя пользователя к отправке
        formData.append('profile_picture', $('.hs-excel-file')[0].files[0]);
        formData.append('COMPETITOR_ID', $(this).attr('hs-data-COMPETITOR_ID'));
        $this.attr('disabled', 'disabled');

        var request = BX.ajax.runComponentAction('highsystem:uploadexcel', 'test', {
            mode: "class",
            data: formData
        });
// промис в который прийдет ответ
        request.then(function (response) {
            BX.SidePanel.Instance.close()
            //console.log(response);
        });
    });

})();