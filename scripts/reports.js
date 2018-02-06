var rep_with_summary = [                                 //отчеты с итоговой информацией [id отчета, номер первой колонки суммирования, количество колонок суммирования]
    [1,1,5],[2,1,5],[3,1,5],[4,1,5],[5,1,5],
    [6,1,5],[7,1,5],[8,1,5],[9,1,5],[10,1,5],
    [11,1,5],[12,1,5],[13,1,5],[14,1,4],[35,4,3],
    [36,3,5],[38,7,1],[41,1,5],[44,3,1],[46,1,5]];

$(document).ready(function() {
    "use strict";
    //Переменные настройки отчетов
    var rep_with_car_id = [15,35,36,37,38,40,44];               //идентификаторы отчетов со списком выбора автомобилей
    var rep_without_dep_and_date_type = [17,35,36,37,38,40, 43, 44]; //идентификаторы отчетов без списка выбора департамента и типа даты
    var rep_with_fuel_type = [35, 36];                       //идентификаторы отчетов со списком видов ГСМ

    initReportForm();

    //Обработчик события нажатия на кнопку "Отчеты"
    $('#btnReports').button().click(function() {
        $("#error_reportSettings").hide();
        $('#reportSettings').dialog( {
                autoOpen: true,
                modal: true,
                title: 'Отчет',
                width: $(window).width()/1.5,
                resizable: false,
                close: function(event, ui) {
                    initButtonsState();
                },
                buttons: [
                    {
                        text: "Создать",
                        click: function() {
                            if (!checkReportSettings())
                            {
                                $("#error_reportSettings").show();
                                return;
                            }
                            $( this ).dialog( "close" );
                            menu_code = 1;
                            setCookie("menu_code", menu_code);
                            report_id = $("#report_id").prop("value");
                            setCookie("report_id", report_id);
                            start_date = $("#start_date").prop("value");
                            setCookie("start_date", start_date);
                            end_date = $("#end_date").prop("value");
                            setCookie("end_date", end_date);
                            department = $("#departments select[name='department']").prop("value");
                            setCookie("department",department);
                            date_id = $("#filter_criteria select[name='date_id']").prop("value");
                            setCookie("date_id",date_id);

                            var title = $('#report_id option[value="'+$("#report_id").prop("value")+'"]').text();
                            if ($("#report_id").prop("value").inList(rep_with_fuel_type))
                            {
                                fuel_type_id = $("#reportSettings select[name='fuel_type_id']").prop("value");
                                setCookie("fuel_type_id",fuel_type_id);
                                var fuel_type = $("#reportSettings select[name='fuel_type_id'] option[value='"+fuel_type_id+"']").text();
                                if ($.trim(fuel_type) != "Все марки горючего") {
                                    title = title + " ("+fuel_type+")"; }
                            }
                            if ($("#report_id").prop("value").inList(rep_with_car_id))
                            {
                                car_id = $("#reportSettings select[name='car_id']").prop("value");
                                setCookie("car_id",car_id);
                                var car = $("#reportSettings select[name='car_id'] option[value='"+car_id+"']").text();
                                var car_arr = car.split('|');
                                if (car_arr.length == 4) {
                                    title = title + " "+
                                        car_arr[2]+" номер "+car_arr[0]; }
                            }
                            title = $.trim(title);
                            header = title+" с "+$("#start_date").prop("value")+" по "+$("#end_date").prop("value");
                            setCookie("header",header);
                            initDataTable();
                            initButtonsState();
                            allowRefreshCounter = 0;
                            forceRefreshCounter = 0;
                        }
                    },
                    {
                        text: "Закрыть",
                        click: function() {
                            $( this ).dialog( "close" );
                        }
                    }]
            }
        ).height("auto");
    });

    function checkReportSettings()
    {
        $("#error_reportSettings div").remove();
        var start_date = $("#start_date").prop("value");
        var end_date = $("#end_date").prop("value");
        var is_correct = true;
        if (!dateCorrect(start_date))
        {
            $("#error_reportSettings").append("<div>Некорректное значение начальной даты</div>");
            is_correct = false;
        }
        if (!dateCorrect(end_date))
        {
            $("#error_reportSettings").append("<div>Некорректное значение конечной даты</div>");
            is_correct = false;
        }
        return is_correct;
    }

    //Функция инициализации формы отчета
    function initReportForm()
    {
        $("#start_date").inputmask("99.99.9999");
        $("#end_date").inputmask("99.99.9999");
        var Now = new Date();
        //календарь
        $("#start_date").datepicker({
            monthNames: [ "Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь" ],
            dayNames:	["Воскресенье","Понедельник","Вторник","Среда","Четверг","Пятница","Суббота"],
            dayNamesMin:["Вс","Пн","Вт","Ср","Чт","Пт","Сб"],
            buttonImageOnly: true,
            buttonImage: "./img/SelCalendar.gif",
            buttonText: "Календарь",
            showOn: "button",
            dateFormat:"dd.mm.yy",
            firstDay: 1,
            defaultDate: Now
        });
        $("#end_date").datepicker({
            monthNames: [ "Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь" ],
            dayNames:	["Воскресенье","Понедельник","Вторник","Среда","Четверг","Пятница","Суббота"],
            dayNamesMin:["Вс","Пн","Вт","Ср","Чт","Пт","Сб"],
            buttonImageOnly: true,
            buttonImage: "./img/SelCalendar.gif",
            buttonText: "Календарь",
            showOn: "button",
            dateFormat:"dd.mm.yy",
            firstDay: 1,
            defaultDate: Now
        });
        $.ajax({
            type: "POST",
            url: "inc/report_names.php",
            data: "report_id=0",
            success: function(msg)
            {
                $("#report_id").append(msg);
            },
            error: function(msg)
            {
            }
        });
        $.ajax({
            type: "POST",
            url: "inc/departments_list.php",
            success: function(msg)
            {
                $("#departments").append(msg);
            },
            error: function(msg)
            {
            }
        });
        $.ajax({
            type: "POST",
            url: "processor.php",
            data: "action=display_all_transport_combobox",
            success: function(msg)
            {
                $("#car").append(msg);
            },
            error: function(msg)
            {
            }
        });
        $.ajax({
            type: "POST",
            url: "processor.php",
            data: "action=display_all_fuel_type_combobox",
            success: function(msg)
            {
                $("#fuel").append(msg);
            },
            error: function(msg)
            {
            }
        });
        $("#car_row").hide();
        $("#fuel_row").hide();
        $("#report_id").bind("change", function() {
            if ($("#report_id").prop("value").inList(rep_with_car_id)) {
                $("#car_row").show();
            } else {
                $("#car_row").hide(); }
            if ($("#report_id").prop("value").inList(rep_with_fuel_type)) {
                $("#fuel_row").show();
            } else {
                $("#fuel_row").hide(); }
            if ($("#report_id").prop("value").inList(rep_without_dep_and_date_type)) {
                $("#department_row").hide();
                $("#date_row").hide();
            } else
            {
                $("#department_row").show();
                $("#date_row").show();
            }
        });
    }
});