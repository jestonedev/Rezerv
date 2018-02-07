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
                        var from = $("#ganttSettings input[name='date-from']").val();
                        var to = $("#ganttSettings input[name='date-to']").val();
                        if (!dateCorrect(from) || !dateCorrect(to))
                        {
                            alert('Некорректно задан период');
                            return;
                        }
                        $(this).dialog("close");
                        var title = "";
                        if (id_request == 1) {
                            title = "Диаграмма Ганта заявок на транспорт";
                            if (from == to)
                            {
                                title += " ("+from+")";
                            } else
                            {
                                title += " ("+from+" - "+to+")";
                            }
                        }
                        else if (id_request == 2) {
                            title = "Диаграмма Ганта заявок на большой зал";
                        }
                        else if (id_request == 3) {
                            title = "Диаграмма Ганта заявок на малый зал";
                        }

                        var requestStates = [];
                        $('#ganttSettings input[type=checkbox]').each(function () {
                            if ($(this).prop("checked")) {
                                requestStates.push($(this).val());
                            }
                        });

                        initGantt(
                            from,
                            to,
                            $("#ganttSettings select[name='car_id']").val(),
                            requestStates,
                            id_request,
                            function() {
                                var gantt = $('#gantt');
                                gantt.dialog({
                                    autoOpen: true,
                                    modal: true,
                                    title: title,
                                    width: "90%",
                                    height: "auto",
                                    resizable: false,
                                    buttons: [{
                                        text: "Закрыть",
                                        click: function () {
                                            $(this).dialog("close");
                                        }
                                    }]
                                });
                                gantt.dialog("option", "position", {at: "center center"});
                            }
                        );
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

    function initGantt(dateFrom, dateTo, idCar, requestStates, requestTypeId, callback)
    {
        $.getJSON('inc/get_gantt_info.php?date_from=' + dateFrom +
            '&date_to='+dateTo+
            '&id_car='+idCar+
            '&request_states='+requestStates+
            '&request_type_id='+requestTypeId, function(data) {
            var categories = createCategories(data);
            var series = createSeries(data);

            Highcharts.chart('gantt-diagram', {
                chart: {
                    type: 'xrange'
                },
                title: {
                    text: ''
                },
                xAxis: {
                    type: 'datetime'
                },
                yAxis: {
                    title: {
                        text: ''
                    },
                    categories: categories,
                    reversed: true,
                    visible: false
                },
                plotOptions: {
                    xrange: {
                        dataLabels: {
                            enabled: false
                        },
                        tooltip: {
                            headerFormat: '',
                            pointFormatter:
                                function() {
                                    console.log(this);
                                    return '<span style="color:'+this.color+'">●</span> ' +
                                        '<b> Заявка №'+this.ext_data.id_request_number+' ('+
                                        this.ext_data.request_state_text+'), '+this.yCategory+'</b>';
                                }
                        }
                    }
                },
                series: series
            });

            callback();
        });
    }

    function createCategories(data)
    {
        return $.map(data, function(val) { return val['date_label']; });
    }

    function createSeries(data)
    {
        var colors = ["#7cb5ec", "#434348", "#90ed7d", "#f7a35c",
            "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"];

        var groupedData = {};
        for(var idx in data)
        {
            var name = data[idx].car_number;
            if (name == "") name = data[idx].car_type;
            if (groupedData[name] == undefined)
            {
                groupedData[name] = [];
            }
            groupedData[name].push(data[idx]);
        }

        var current_color_index = -1;
        var current_row_index = -1;
        return $.map(groupedData, function(group, idx) {
            current_color_index += 1;
            current_color_index %= colors.length;
            return {
                name: idx,
                pointWidth: 20,
                color: colors[current_color_index],
                colorByPoint: false,
                data: $.map(group, function(val) {
                    current_row_index++;
                    return {
                        x: Date.UTC(val['date_from_year'], val['date_from_month'], val['date_from_day'],
                            val['date_from_hour'], val['date_from_minute']),
                        x2: Date.UTC(val['date_to_year'], val['date_to_month'], val['date_to_day'],
                            val['date_to_hour'], val['date_to_minute']),
                        y: current_row_index,
                        ext_data: {
                            id_request_number: val['id_request_number'],
                            request_state_text: val['request_state_text']
                        }
                    }
                })
            };
        });
    }
});