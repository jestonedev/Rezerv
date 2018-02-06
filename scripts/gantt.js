/**
 * Created by Ignatov on 06.02.2018.
 */
$(document).ready(function () {
    "use strict";

    //Обработчик события нажатия на кнопку диаграммы Ганта
    $("#btnShowGantt").click(function () {
        var has_form_data = false;
        $.ajax({
            type: "POST",
            url: "processor.php",
            data: "action=display_gantt_settings_form",
            async: false,
            success: function (msg) {
                $('#ganttSettings').html(msg);

                $("#ganttSettings input[name='date-from']").inputmask("99.99.9999");
                $("#ganttSettings input[name='date-to']").inputmask("99.99.9999");
                $("#ganttSettings input[name='date-from']").datepicker(datePickerSettings);
                $("#ganttSettings input[name='date-to']").datepicker(datePickerSettings);
                has_form_data = true;
            },
            error: function (msg) {
            }
        });
        if (!has_form_data) {
            return;
        }
        $('#ganttSettings').dialog({
            autoOpen: true,
            modal: true,
            title: "Настройки отображаемой информации",
            width: $(window).width() / 1.5,
            resizable: false,
            buttons: [
                {
                    text: "Принять",
                    click: function () {
                        var checked = false;
                        $('#ganttSettings input[type=checkbox]').each(function () {
                            if ($(this).prop("checked")) {
                                checked = true;
                            }
                        });
                        if (!checked) {
                            alert('Необходимо выбрать хотя бы один статус заявки');
                            return;
                        }
                        $(this).dialog("close");
                        var title = "";
                        if (id_request == 1) {
                            title = "Диаграмма Ганта заявок на транспорт";
                        }
                        else if (id_request == 2) {
                            title = "Диаграмма Ганта заявок на большой зал";
                        }
                        else if (id_request == 3) {
                            title = "Диаграмма Ганта заявок на малый зал";
                        }
                        var gantt = $('#gantt');
                        gantt.dialog({
                            autoOpen: true,
                            modal: true,
                            title: title,
                            width: "auto",
                            height: $(window).height() - 10,
                            buttons: [{
                                text: "Закрыть",
                                click: function () {
                                    $(this).dialog("close");
                                }
                            }]
                        });
                        gantt.dialog("option", "position", {at: "center center"});
                    }
                },
                {
                    text: "Закрыть",
                    click: function () {
                        $(this).dialog("close");
                    }
                }
            ]
        }).height('auto');
    });
});