$(document).ready(function() {
    "use strict";
    var cars_default_fuel = get_cars_default_fuel();         //Тип топлива, выбираемый по умолчанию, при выборе автотранспорта

    initWaybillCreateForm();

    //Обработчик события нажатия на кнопку "Путевые листы"
    $('#btnWaybills').button().click(function()
    {
        if (menu_code != 4)
        {
            setCookie("menu_code", 4);
            menu_code = 4;
            initDataTable();
        } else
        {
            $('#example').dataTable().api().ajax.reload();
        }
        initButtonsState();
        allowRefreshCounter = 0;
        forceRefreshCounter = 0;
    });

    //Обработчик события нажатия на кнопку "Создать акт"
    $('#btnCreateWaybill').button().click(function() {
        $("#error_waybill_create").hide();
        $("#waybill_number").prop("value","");
        var now = new Date();
        var day = now.getDate();
        if (day < 10) day = "0"+day;
        var month = now.getMonth() + 1;
        if (month < 10) month = "0"+month;
        var year = now.getFullYear();
        $("#waybill_start_date").prop("value", day+"."+month+"."+year);
        $("#waybill_end_date").prop("value", day+"."+month+"."+year);
        $("#waybill_address_supply").prop("value","ул. Ленина, 37");
        $("#waybill_mileage_before").prop("value","");
        $("#waybill_mileages").prop("value","");
        $("#waybill_fuel_before").prop("value","");
        $("#waybill_given_fuel").prop("value","");
        $("#waybill_car select").prop("value","22");
        $("#waybill_car select").change();
        $("#ways_list option").remove();
        $('#waybill_create_form').dialog( {
                autoOpen: true,
                modal: true,
                title: 'Создать путевой лист',
                width: $(window).width()/1.3,
                height: "auto",
                resizable: false,
                close: function(event, ui) {
                    initButtonsState();
                },
                buttons: [
                    {
                        text: "Создать",
                        click: function() {
                            InsertWaybill();
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

    // Автокомплит полей при изменении автомобиля в путевом листе
    $('#waybill_car').on('change', 'select', function(idx, val) {
        var idCar = $(this).val();
        $.getJSON('inc/waybills_autocomplete.php?id_car='+idCar+'&rnd='+Math.random(), function(data) {
            $("#waybill_driver select").val(data.id_driver_default || 0);
            $("#waybill_department select").val(data.department_default || "Диспетчер");
            $("#waybill_fuel_type select").val(data.id_fuel_default || 1);
            $("#waybill_number").val(data.waybill_number || 1);
            $("#waybill_mileage_before").val(data.mileage_after || 0);
            $("#waybill_fuel_before").val(data.fuel_after || 0);
        });
    });

    //Функция инициализации формы создания путевых листов
    function initWaybillCreateForm()
    {
        $("#waybill_start_date").inputmask("99.99.9999");
        $("#waybill_end_date").inputmask("99.99.9999");
        var Now = new Date();
        //календарь
        $("#waybill_start_date").datepicker({
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
        $("#waybill_end_date").datepicker({
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
        $("#insert_way, #delete_way").button();
        $.ajax( {
                type: "POST",
                url: "inc/cars_list.php",
                success: function(msg)
                {
                    $('#waybill_car select').remove();
                    $('#waybill_car').append(msg);
                },
                error: function(msg)
                {
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/drivers_list.php",
                success: function(msg)
                {
                    $('#waybill_driver select').remove();
                    $('#waybill_driver').append(msg);
                    $('#act_driver select').remove();
                    $('#act_driver').append(msg);
                },
                error: function(msg)
                {
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/departments_without_stage_list.php",
                success: function(msg)
                {
                    $('#waybill_department select').remove();
                    $('#waybill_department').append(msg);
                    $('#waybill_department select').
                        prepend("<option value=\"Диспетчер\" style=\"background-color: #f9ff9b\" selected>Диспетчер</option>");
                },
                error: function(msg)
                {
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/fuel_types_list.php",
                success: function(msg)
                {
                    $('#waybill_fuel_type select').remove();
                    $('#waybill_fuel_type').append(msg);
                },
                error: function(msg)
                {
                }
            }
        );
        $("#insert_way").click(function() {
            $("#error_add_way").hide();
            $("#way_value_from").prop("value","");
            $("#way_value_to").prop("value","");
            $("#way_out_time").prop("value","");
            $("#way_return_time").prop("value","");
            $("#way_distance").prop("value","");

            $("#way_out_time").inputmask("99:99");
            $("#way_return_time").inputmask("99:99");

            $('#add_way').dialog({
                autoOpen: true,
                modal: true,
                title: 'Добавить маршрут',
                width: $(window).width()/1.5,
                height: 'auto',
                resizable: false,
                buttons: [
                    {
                        text: "Добавить",
                        click: function() {
                            $("#error_add_way div").remove();
                            $("#error_add_way").hide();
                            var way_value_from = $("#way_value_from").prop("value");
                            var way_value_to = $("#way_value_to").prop("value");
                            var way_out_time = $("#way_out_time").prop("value");
                            var way_return_time = $("#way_return_time").prop("value");
                            var way_distance = $("#way_distance").prop("value");
                            var is_correct = true;
                            if ($.trim(way_value_from).length== 0)
                            {
                                $("#error_add_way").append("<div>Необходимо указать маршрут (из)</div>");
                                is_correct = false;
                            }
                            if ($.trim(way_value_to).length== 0)
                            {
                                $("#error_add_way").append("<div>Необходимо указать маршрут (в)</div>");
                                is_correct = false;
                            }
                            if ($.trim(way_out_time).length == 0)
                            {
                                $("#error_add_way").append("<div>Необходимо указать время отправления</div>");
                                is_correct = false;
                            } else
                            if (!timeCorrect(way_out_time))
                            {
                                $("#error_add_way").append("<div>Некорректный формат времени отправления</div>");
                                is_correct = false;
                            }
                            if ($.trim(way_return_time).length == 0)
                            {
                                $("#error_add_way").append("<div>Необходимо указать время возвращения</div>");
                                is_correct = false;
                            } else
                            if (!timeCorrect(way_return_time))
                            {
                                $("#error_add_way").append("<div>Некорректный формат времени возвращения</div>");
                                is_correct = false;
                            }
                            if (!is_correct)
                            {
                                $("#error_add_way").show();
                            }
                            else
                            {
                                var way_distance_str = "";
                                if ($.trim(way_distance).length != 0)
                                {
                                    way_distance_str = " - "+way_distance+" км.";
                                }
                                $("#ways_list").append("<option value='"+way_value_from+" - "+way_value_to+"@"+way_out_time+"@"+
                                    way_return_time+"@"+way_distance+"'>"+"("
                                    +way_out_time+"-"+way_return_time+")"+way_distance_str+" - "+way_value_from+" - "+way_value_to+"</option>");
                                $( this ).dialog( "close" );
                            }
                        }
                    },
                    {
                        text: "Выход",
                        click: function() {
                            $( this ).dialog( "close" );
                        }
                    }
                ]
            }).height('auto');
        });
        $("#delete_way").click(function() {
            $("#ways_list option:selected").remove();
        });
        $("#waybill_create_form select[name='car_id']").bind("change", function() {
            change_fuel_type();
        });
        //Выставляем значение для 1й записи, когда change еще ни разу не сработал
        change_fuel_type();
    }

    //Функция изменения вида топлива
    function change_fuel_type()
    {
        var id_car = $("#waybill_create_form select[name='car_id']").prop("value");
        var i = 0;
        for (i = 0; i < cars_default_fuel.length; i++)
        {
            if (cars_default_fuel[i]["id"] == id_car) {
                $("#waybill_create_form select[name='fuel_type_id']").prop("value", cars_default_fuel[i]["id_fuel_default"]); }
        }
    }

    //Получить массив "машина - топливо по умолчанию"
    function get_cars_default_fuel()
    {
        var array;
        $.ajax({
            type: "POST",
            url: "inc/cars_default_fuel.php",
            async: false,
            success: function(msg)
            {
                array = JSON.parse(msg);
            },
            error: function(msg)
            {
            }
        });
        return array;
    }
});

//Есть привелегия на создание путевых листов
function hasCreateWaybillsPrivilege()
{
    var hasCRP = false;
    $.ajax({
        type: "POST",
        url: "inc/can_create_waybills.php",
        async: false,
        success: function(msg)
        {
            if (msg == '1') {
                hasCRP = true; }
        },
        error: function(msg)
        {
        }
    });
    return hasCRP;
}

function show_waybill_details(oTable, nTr)
{
    oTable.fnOpen( nTr, fnFormatWaybillDetails(oTable, nTr), 'details' );
    allowRefreshCounter += 1;
    $(".btnModifyWaybill, .btnDeleteWaybill, .btnReportByWaybill").button();
    $(".btnModifyWaybill, .btnDeleteWaybill, .btnReportByWaybill").button().unbind('click');
    $(".btnDeleteWaybill").button().click( function() {
            var id_waybill = $(this).prop("value");
            if (!confirm('Вы действительно хотите удалить путевой лист № '+id_waybill+'?'))
            {
                return;
            }
            $.ajax( {
                    type: "POST",
                    url: "inc/waybills_modify.php",
                    data: "action=delete_waybill&id_waybill="+id_waybill,
                    success: function(msg)
                    {
                        $('#example').dataTable().api().ajax.reload();
                    },
                    error: function(msg)
                    {
                    }
                }
            );
        }
    );
    $('.btnModifyWaybill').button().click(function() {
        var id_waybill = $(this).prop("value");
        $.ajax( {
                type: "POST",
                url: "inc/waybills_modify.php",
                data: "action=get_waybill_info&id_waybill="+id_waybill,
                async: false,
                success: function(msg)
                {
                    var info = JSON.parse(msg);
                    var start_date_str = info["start_date"].split(' ')[0];
                    var start_date_arr = start_date_str.split('-');
                    var start_date = new Date(start_date_arr[0],start_date_arr[1] - 1,start_date_arr[2]);
                    var start_day = start_date.getDate()<10?"0"+start_date.getDate():start_date.getDate();
                    var start_month = (start_date.getMonth()+1)<10?"0"+(start_date.getMonth()+1):(start_date.getMonth()+1);
                    var start_year = start_date.getFullYear();
                    $("#waybill_start_date").prop("value",start_day+"."+start_month+"."+start_year);
                    var end_date_str = info["end_date"].split(' ')[0];
                    var end_date_arr = end_date_str.split('-');
                    var end_date = new Date(end_date_arr[0],end_date_arr[1] - 1,end_date_arr[2]);
                    var end_day = end_date.getDate()<10?"0"+end_date.getDate():end_date.getDate();
                    var end_month = (end_date.getMonth()+1)<10?"0"+(end_date.getMonth()+1):(end_date.getMonth()+1);
                    var end_year = end_date.getFullYear();
                    $("#waybill_end_date").prop("value",end_day+"."+end_month+"."+end_year);
                    $("#waybill_create_form select[name='car_id']").prop("value", info["id_car"]);
                    $("#waybill_create_form select[name='driver_id']").prop("value", info["id_driver"]);
                    $("#waybill_create_form select[name='mechanic_id']").prop("value", info["id_mechanic"]);
                    $("#waybill_create_form select[name='dispatcher_id']").prop("value", info["id_dispatcher"]);
                    $("#waybill_create_form select[name='department']").prop("value",info["department"]);
                    $("#waybill_create_form select[name='fuel_type_id']").prop("value",info["id_fuel_type"]);
                    $("#waybill_number").prop("value", info["waybill_number"]);
                    $("#waybill_address_supply").prop("value", info["address_supply"]);
                    $("#waybill_mileage_before").prop("value", info["mileage_before"]);
                    $("#waybill_mileages").prop("value", info["mileage_after"] - info["mileage_before"]);
                    $("#waybill_fuel_before").prop("value", info["fuel_before"]);
                    $("#waybill_given_fuel").prop("value", info["given_fuel"]);

                    var ways_array = info["ways"];
                    $("#ways_list option").remove();
                    var i = 0;
                    for (i =0; i < ways_array.length; i++)
                    {
                        var way = ways_array[i]["way"];
                        var start_time = ways_array[i]["start_time"];
                        var end_time = ways_array[i]["end_time"];
                        var distance = ways_array[i]["distance"];

                        var way_distance_str = "";
                        if ($.trim(distance).length != 0)
                        {
                            way_distance_str = " - "+distance+" км.";
                        }
                        $("#ways_list").append("<option value='"+way+"@"+start_time+"@"+
                            end_time+"@"+distance+"'>"+"("
                            +start_time+"-"+end_time+")"+way_distance_str+" - "+way+"</option>");
                    }
                },
                error: function(msg)
                {
                }
            }
        );
        $("#error_waybill_create").hide();
        $('#waybill_create_form').dialog( {
                autoOpen: true,
                modal: true,
                title: 'Изменить путевой лист №'+id_waybill,
                width: $(window).width()/1.5,
                height: "auto",
                resizable: false,
                close: function(event, ui) {
                    initButtonsState();
                },
                buttons: [
                    {
                        text: "Изменить",
                        click: function() {
                            UpdateWaybill(id_waybill);
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
    $('.btnReportByWaybill').button().click(function() {
        var id_waybill = $(this).prop("value");
        $.fileDownload('inc/waybills_report.php?id_report_type=1&id_waybill='+id_waybill,
            {failMessageHtml: "Не удалось загрузить файл, попробуйте еще раз."});
        return false;
    });
    $('.btnReportByWaybillWithPeriod').button().click(function() {
        var id_waybill = $(this).prop("value");
        $.fileDownload('inc/waybills_report.php?id_report_type=2&id_waybill='+id_waybill,
            {failMessageHtml: "Не удалось загрузить файл, попробуйте еще раз."});
        return false;
    });
}


//Функция инициализации формы детальной информации о путевых листах
function fnFormatWaybillDetails( oTable, nTr )
{
    var msga = "";
    var aData = oTable.fnGetData( nTr );
    var id_waybill = $(aData[0]).attr('value');
    $.ajax({
        type: "POST",
        url: "inc/details_by_id.php",
        data: 'action=waybill&id_waybill='+id_waybill,
        async: false,
        success: function(msg)
        {
            msga = msg;
        },
        error: function(msg)
        {
        }
    });
    return msga;
}

function ProcessWaybill(id_waybill)
{
    $("#error_waybill_create div").remove();
    $("#error_waybill_create").hide();
    var waybill_number = $("#waybill_number").prop("value");
    var waybill_start_date = $("#waybill_start_date").prop("value");
    var waybill_end_date = $("#waybill_end_date").prop("value");
    var car_id = $("#waybill_create_form select[name='car_id']").prop("value");
    var driver_id = $("#waybill_create_form select[name='driver_id']").prop("value");
    var mechanic_id = $("#waybill_create_form select[name='mechanic_id']").prop("value");
    var dispatcher_id = $("#waybill_create_form select[name='dispatcher_id']").prop("value");
    var department = $("#waybill_create_form select[name='department']").prop("value");
    var waybill_mileage_before = $("#waybill_mileage_before").prop("value");
    var waybill_mileages = $("#waybill_mileages").prop("value");
    var waybill_fuel_before = $("#waybill_fuel_before").prop("value").replace(",",".");
    var waybill_given_fuel = $("#waybill_given_fuel").prop("value").replace(",",".");
    var fuel_type_id = $("#waybill_create_form select[name='fuel_type_id']").prop("value");
    var ways_list = "";
    var is_correct = true;
    if (($.trim(waybill_start_date) == "") || ($.trim(waybill_end_date) == "") ||
        ($.trim(car_id) == "") || ($.trim(driver_id) == "") || ($.trim(department) == ""))
    {
        $("#error_waybill_create").append("<div>Не все обязательные поля заполнены</div>").show();
        return;
    }
    if (!dateCorrect(waybill_start_date) || !dateCorrect(waybill_end_date))
    {
        $("#error_waybill_create").append("<div>Период действия путевого листа задан некорректно</div>");
        is_correct = false;
    }
    if (($.trim(waybill_number) != "") && (!intCorrect(waybill_number)))
    {
        $("#error_waybill_create").append("<div>Номер путевого листа указан неверно</div>");
        is_correct = false;
    }
    if (($.trim(waybill_mileage_before) != "") && (!intCorrect(waybill_mileage_before)))
    {
        $("#error_waybill_create").append("<div>Показание спидометра до выезда указано неверно</div>");
        is_correct = false;
    }
    if (($.trim(waybill_mileages) != "") && (!intCorrect(waybill_mileages)))
    {
        $("#error_waybill_create").append("<div>Пробег указан неверно</div>");
        is_correct = false;
    }
    if (($.trim(waybill_fuel_before) != "") && (!floatCorrect(waybill_fuel_before)))
    {
        $("#error_waybill_create").append("<div>Остаток топлива при выезде указан неверно</div>");
        is_correct = false;
    }
    if (($.trim(waybill_given_fuel) != "") && (!floatCorrect(waybill_given_fuel)))
    {
        $("#error_waybill_create").append("<div>Значение выданного топлива указано неверно</div>");
        is_correct = false;
    }
    if (!is_correct)
    {
        $("#error_waybill_create").show();
        return;
    }

    var array = [];
    $("#ways_list option").each(function() {
        ways_list += $(this).prop("value")+"$";
    });
    var action = "";
    if (id_waybill == 0) {
        action = "action=insert_waybill"; }
    else {
        action = "action=update_waybill&waybill_id="+id_waybill; }
    var data = action+"&number="+waybill_number+"&start_date="+waybill_start_date+"&end_date="+waybill_end_date+"&car_id="+car_id+
        "&driver_id="+driver_id+"&mechanic_id="+mechanic_id+"&dispatcher_id="+dispatcher_id+"&department="+department+
        "&mileage_before="+waybill_mileage_before+
        "&mileages="+waybill_mileages+"&fuel_before="+waybill_fuel_before+
        "&given_fuel="+waybill_given_fuel+"&fuel_type_id="+fuel_type_id+
        "&ways_list="+ways_list;
    $.ajax({
        type: "POST",
        url: "inc/waybills_modify.php",
        async: false,
        data: data,
        success: function(msg)
        {
            if ($.trim(msg).length == 0)
            {
                //Если путевой лист успешно подан, закрываем диалог
                $( '#waybill_create_form' ).dialog( "close" );
                $('#example').dataTable().api().ajax.reload();
            } else
            {
                $("#error_waybill_create").append("<div>"+msg+"</div>");
                $("#error_waybill_create").show();
            }
        },
        error: function(msg)
        {
        }
    });
}

//Функция добавления путевого листа в базу данных
function InsertWaybill()
{
    ProcessWaybill(0);
}

function UpdateWaybill(id_waybill)
{
    ProcessWaybill(id_waybill);
}