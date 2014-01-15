/**
 * Created with JetBrains PhpStorm.
 * User: IgnVV
 * Date: 11.01.13
 * Time: 10:31
 * To change this template use File | Settings | File Templates.
 */

// Когда я начинал это писать, только Бог и я понимали, что я делаю
// Сейчас остался только Бог

$(document).ready(function(){

    //Переменные настройки отчетов
    var rep_with_car_id = [15,35,36];                  //идентификаторы отчетов со списком выбора автомобилей
    var rep_without_dep_and_date_type = [17,35,36,37]; //идентификаторы отчетов без списка выбора департамента и типа даты
    var rep_with_fuel_type = [35, 36];                 //идентификаторы отчетов со списком видов ГСМ
    var rep_with_summary = [                           //отчеты с итоговой информацией [id отчета, номер первой колонки суммирования, количество колонок суммирования]
        [1,1,5],[2,1,5],[3,1,5],[4,1,5],[5,1,5],
        [6,1,5],[7,1,5],[8,1,5],[9,1,5],[10,1,5],
        [11,1,5],[12,1,5],[13,1,5],[14,1,4],[35,4,3],
        [36,3,5],[37,2,1]];
    var cars_default_fuel = get_cars_default_fuel();    //Тип топлива, выбираемый по умолчанию, при выборе автотранспорта

    //Указывает, разрешено ли обновление таблицы с данными (разрешено, только если значение равно 0)
    var allowRefreshCounter = 0;

    //Если счетчик форсированного обновления перевалит за 10, значит пользователь не работал со страницей уже 10 минут и надо сбросить AllowRefreshCounter
    var forceRefreshCounter = 0;

    //Указывает, разрешено ли обновление календаря, разрешено, если 1, запрещено, если 0
    var allowRefreshCalendar = 0;

    //Установка Cookie и настройка начального представления формы
    if (!getCookie("id_request"))
        setCookie("id_request",1);
    if (!getCookie("menu_code"))
    {
        setCookie("menu_code", 0);
        setCookie("report_id",1);
    }
    else
    {
        if (!getCookie("report_id"))
            setCookie("report_id",1);
    }
    if (!getCookie("only_my_requests"))
        setCookie("only_my_requests",0);
    if (!getCookie("header"))
        setCookie("header","Заявки на транспорт");
    $("#header").text(getCookie("header"));

    //Добавление прототипа функции вхождения в массив
    String.prototype.inList=function(list){
        for(i in list){
            if(this==list[i]){
                return true;
            }
        }
        return false;
    };

    Number.prototype.inList=String.prototype.inList;

    //Инициализация компонентов
    $( "input[type=submit], input[type=reset], a, button" ).button();

    initVariables();
    initDataTable();
    initButtonsState();
    initMileagesEditor();

    initCalendar();
    initReportForm();
    initCarSelectForm();
    initActCreateForm();
    initWaybillCreateForm();
    initAutoRefresh();

    $('#calendar').hide();
    $('#calendar_details').hide();
    $('#reportSettings').hide();
    $('#select_car_form').hide();
    $('#mileagesEditor').hide();
    $('#act_create_form').hide();
    $('#add_expended').hide();
    $('#waybill_create_form').hide();
    $('#add_way').hide();

    ///////////////////////////////////
    //Назначение обработчиков событий//
    ///////////////////////////////////

    //Обработчик события нажатия на кнопку календаря
    $("#btnShowCalendar").click(function () {
        var has_form_data = false;
        $.ajax({
            type: "POST",
            url: "processor.php",
            data: params="action=display_calendar_settings_form",
            async: false,
            success: function(msg)
            {
                $('#calendarSettings').html(msg);
                has_form_data = true;
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 19');
            }
        });
        if (!has_form_data)
            return;
        $('#calendarSettings').dialog({
            autoOpen: true,
            modal: true,
            title: "Настроки отображаемой информации",
            width: $(window).width()/1.5,
            resizable: false,
            buttons: [
                {
                    text: "Принять",
                    click: function() {
                        var checked = false;
                        $('#calendarSettings input[type=checkbox]').each( function() {
                            if ($(this).attr("checked"))
                                checked = true;
                        });
                        if (!checked)
                        {
                            alert('Необходимо выбрать хотя бы один статус заявки');
                            return;
                        }
                        $( this ).dialog( "close" );
                        var title = "";
                        if (getCookie('id_request') == 1)
                            title = "Календарь заявок на транспорт";
                        else if (getCookie('id_request') == 2)
                            title = "Календарь заявок на большой зал";
                        else if (getCookie('id_request') == 3)
                            title = "Календарь заявок на малый зал";
                        $('#calendar').dialog({
                            autoOpen: true,
                            modal: true,
                            title: title,
                            width: "auto",
                            close: function(event, ui) {
                                allowRefreshCalendar = 0;
                            },
                            buttons: [{text: "Закрыть",
                                click: function() { $( this ).dialog( "close" ); }}]
                        }).height($(window).height()-175);
                        $('#calendar').dialog("option","position",{ at: "center center" });
                        $('#calendar').fullCalendar( 'refetchEvents' );
                        $('#calendar').fullCalendar( 'rerenderEvents' );
                        allowRefreshCalendar = 1;
                    }
                },
                {
                    text: "Закрыть",
                    click: function() {
                        $( this ).dialog( "close" ); }
                }
            ]
        }).height('auto');
    });

    //Обработчик события нажатия на кнопку "Пробег автомобилей"
    $('#btnCarsInfo').button().click(function()
    {
        if (getCookie("menu_code") != 2)
        {
            setCookie("menu_code", 2);
            initDataTable();
        } else
        {
            $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
        }
        initButtonsState();
        allowRefreshCounter = 0;
        forceRefreshCounter = 0;
    });

    //Обработчик события нажатия на кнопку "Акты обслуживания"
    $('#btnRepairActs').button().click(function()
    {
        if (getCookie("menu_code") != 3)
        {
            setCookie("menu_code", 3);
            initDataTable();
        } else
        {
            $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
        }
        initButtonsState();
        allowRefreshCounter = 0;
        forceRefreshCounter = 0;
    });

    //Обработчик события нажатия на кнопку "Путевые листы"
    $('#btnWaybills').button().click(function()
    {
        if (getCookie("menu_code") != 4)
        {
            setCookie("menu_code", 4);
            initDataTable();
        } else
        {
            $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
        }
        initButtonsState();
        allowRefreshCounter = 0;
        forceRefreshCounter = 0;
    });

    //Обработчики событий нажатия на кнопки выбора вида заявки
    $('#btnTransportRequests').button().click(function()
    {
        setCookie("id_request",1);
        if (getCookie("menu_code") > 0)
        {
            setCookie("menu_code",0);
            initDataTable();
        } else
            $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
        initButtonsState();
        allowRefreshCounter = 0;
        forceRefreshCounter = 0;
    });

    $('#btnGreatHallRequests').button().click(function()
    {
        setCookie("id_request",2);
        if (getCookie("menu_code") > 0)
        {
            setCookie("menu_code",0);
            initDataTable();
        } else
            $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
        initButtonsState();
        allowRefreshCounter = 0;
        forceRefreshCounter = 0;
    });

    $('#btnSmallHallRequests').button().click(function()
    {
        setCookie("id_request",3);
        if (getCookie("menu_code") > 0)
        {
            setCookie("menu_code",0);
            initDataTable();
        } else
            $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
        initButtonsState();
        allowRefreshCounter = 0;
        forceRefreshCounter = 0;
    });

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
                        setCookie("menu_code", 1);
                        setCookie("report_id", $("#report_id").attr("value"));
                        setCookie("start_date",$("#start_date").attr("value"));
                        setCookie("end_date",$("#end_date").attr("value"));
                        setCookie("department",$("#departments select[name='department']").attr("value"));
                        setCookie("date_id",$("#filter_criteria select[name='date_id']").attr("value"));
                        var title = $('#report_id option[value="'+$("#report_id").attr("value")+'"]').text();
                        if ($("#report_id").attr("value").inList(rep_with_fuel_type))
                        {
                            setCookie("fuel_type_id",$("#reportSettings select[name='fuel_type_id']").attr("value"));
                            var fuel_type = $("#reportSettings select[name='fuel_type_id'] option[value='"+getCookie("fuel_type_id")+"']").text();
                            if ($.trim(fuel_type) != "Все марки горючего")
                                title = title + " ("+fuel_type+")";
                        }
                        if ($("#report_id").attr("value").inList(rep_with_car_id))
                        {
                            setCookie("car_id",$("#reportSettings select[name='car_id']").attr("value"));
                            var car = $("#reportSettings select[name='car_id'] option[value='"+getCookie("car_id")+"']").text();
                            var car_arr = car.split('|');
                            if (car_arr.length == 4)
                                title = title + " "+
                                    car_arr[2]+" номер "+car_arr[0];
                        }
                        title = $.trim(title);
                        setCookie("header",title+" с "+$("#start_date").attr("value")+" по "+$("#end_date").attr("value"));
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

    //Обработчик события нажатия на кнопку создания новой заявки
    $('#btnCreateRequest').button().click(function() {
        $('#frmRequest').remove();
        $.ajax({
            type: "POST",
            url: "processor.php",
            data: params="action=display_request_form",
            success: function(msg)
            {
                $('body').append(msg);
                $(".time_field").mask("99:99");
                $(".date_field").mask("99.99.9999");
                Now = new Date();
                //календарь
                $(".date_field").datepicker({
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
                $('#frmRequest').dialog({
                    autoOpen: true,
                    modal:true,
                    width: $(window).width() / 1.3,
                    height: "auto",
					resizable: false,
                    buttons: [
                        {
                            text: "Подать заявку",
                            click: function() {
                                SendRequest(0);
								$(this).dialog("option","height","auto");
                            }
                        },
                        {
                            text: "Выход",
                            click: function() {
                                $( this ).dialog( "close" );
                            }
                        }
                    ]
                });
            },
            error: function(msg) {
                alert('Ошибка при обращении к серверу. Код 18');
            }
        })
    });

    //Обработчик события нажатия на кнопку "Создать акт"
    $('#btnCreateAct').button().click(function() {
        $("#error_act_create").hide();
        $("#act_create_form #act_date").attr("value","");
        $("#act_create_form #reason_for_repair").attr("value","");
        $("#act_create_form #work_performed").attr("value","");
        $("#act_expended_list option").remove();
        $('#act_create_form').dialog( {
                autoOpen: true,
                modal: true,
                title: 'Создать акт',
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
                            InsertAct();
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

    //Обработчик события нажатия на кнопку "Создать акт"
    $('#btnCreateWaybill').button().click(function() {
        $("#error_waybill_create").hide();
        $("#waybill_start_date").attr("value","");
        $("#waybill_end_date").attr("value","");
        $("#waybill_address_supply").attr("value","ул. Ленина, 37");
        $("#waybill_mileage_before").attr("value","");
        $("#waybill_mileage_after").attr("value","");
        $("#waybill_fuel_before").attr("value","");
        $("#waybill_given_fuel").attr("value","");
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

    //Обработчик события нажатия на кнопку "Подробнее"
    $('#example tbody td img').live('click', function () {
        //Если это пробег
        if (getCookie("menu_code") == 2)
        {
            show_mileage_details(this);
            return;
        }

        var oTable = $('#example').dataTable();
        var nTr = $(this).parents('tr')[0];
        if ( oTable.fnIsOpen(nTr) )
        {
            this.src = "img/details_open.png";
            oTable.fnClose( nTr );
            allowRefreshCounter -= 1;
        }
        else
        {
            this.src = "img/details_close.png";

            //Если это акты выполненных работ
            if (getCookie("menu_code") == 3)
                show_act_details(oTable, nTr);
            else
            //Если это путевые листы
            if (getCookie("menu_code") == 4)
                show_waybill_details(oTable, nTr);
            else
            //Если это заявки
                show_request_details(oTable, nTr);
        }
    } );

    ////////////////////////////////////////////
    //Функции отображения детальной информации//
    ////////////////////////////////////////////

    function show_request_details(oTable, nTr)
    {
        oTable.fnOpen( nTr, fnFormatReqDetails(oTable, nTr), 'details' );
        allowRefreshCounter += 1;
        $(".btnAcceptRequest, .btnRejectRequest, .btnCancelRequest, .btnCompleteRequest, .btnUnCompleteRequest, .btnChangeRequest").button();
        $(".btnAcceptRequest, .btnRejectRequest, .btnCancelRequest, .btnCompleteRequest, .btnUnCompleteRequest, .btnChangeRequest").button().unbind('click');
        $(".btnAcceptRequest").button().click(
            function()
            {
                var id_request_number = $(this).attr("value");
                if (getCookie("id_request") == 1)
                {
                    $('#select_car_form').dialog({
                        autoOpen: true,
                        modal: true,
                        title: "Выбор автомобиля",
                        width: $(window).width()/2,
                        resizable: false,
                        buttons: [
                            {text: "Выбрать",
                                click: function() {
                                    var id_car = $("#select_car_form select[name='car_id']").attr("value");
                                    $.ajax( {
                                            type: "POST",
                                            url: "inc/moderation_request.php",
                                            data: params="action=accept&id_request_number="+id_request_number+"&id_car="+id_car,
                                            success: function(msg)
                                            {
                                                $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
                                                $('#select_car_form').dialog("close");
                                            },
                                            error: function(msg)
                                            {
                                                alert('Ошибка при обращении к серверу. Код 17');
                                            }
                                        }
                                    );
                                }},
                            {text: "Отменить",
                                click: function() {
                                    $( this ).dialog( "close" );
                                }}
                        ]
                    }).height("auto");
                } else
                {
                    if (confirm("Вы действительно хотите подтвердить заявку?"))
                    {
                        $.ajax( {
                                type: "POST",
                                url: "inc/moderation_request.php",
                                data: params="action=accept&id_request_number="+id_request_number,
                                success: function(msg)
                                {
                                    $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
                                },
                                error: function(msg)
                                {
                                    alert('Ошибка при обращении к серверу. Код 16');
                                }
                            }
                        );
                    }
                }
            }
        );
        $(".btnRejectRequest").button().click(
            function()
            {
                if (confirm("Вы действительно хотите отклонить заявку?"))
                {
                    var id_request_number = $(this).attr("value");
                    $.ajax( {
                            type: "POST",
                            url: "inc/moderation_request.php",
                            data: params="action=reject&id_request_number="+id_request_number,
                            success: function(msg)
                            {
                                $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
                            },
                            error: function(msg)
                            {
                                alert('Ошибка при обращении к серверу. Код 15');
                            }
                        }
                    );
                }
            }
        );
        $(".btnCancelRequest").button().click(
            function()
            {
                if (confirm("Вы действительно хотите отменить свою заявку?"))
                {
                    var id_request_number = $(this).attr("value");
                    $.ajax( {
                            type: "POST",
                            url: "inc/moderation_request.php",
                            data: params="action=cancel&id_request_number="+id_request_number,
                            success: function(msg)
                            {
                                $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
                            },
                            error: function(msg)
                            {
                                alert('Ошибка при обращении к серверу. Код 14');
                            }
                        }
                    );
                }
            }
        );
        $(".btnCompleteRequest").button().click(
            function()
            {
                if (confirm("Вы действительно хотите отметить заявку, как выполненную?"))
                {
                    var id_request_number = $(this).attr("value");
                    $.ajax( {
                            type: "POST",
                            url: "inc/moderation_request.php",
                            data: params="action=complete&id_request_number="+id_request_number,
                            success: function(msg)
                            {
                                $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
                            },
                            error: function(msg)
                            {
                                alert('Ошибка при обращении к серверу. Код 13');
                            }
                        }
                    );
                }
            }
        );
        $(".btnUnCompleteRequest").button().click(
            function()
            {
                if (confirm("Вы действительно хотите отметить заявку как невыполненную?"))
                {
                    var id_request_number = $(this).attr("value");
                    $.ajax( {
                            type: "POST",
                            url: "inc/moderation_request.php",
                            data: params="action=uncomplete&id_request_number="+id_request_number,
                            success: function(msg)
                            {
                                $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
                            },
                            error: function(msg)
                            {
                                alert('Ошибка при обращении к серверу. Код 12');
                            }
                        }
                    );
                }
            }
        );
        $(".btnChangeRequest").button().click(
            function()
            {
                var id_request_number = $(this).attr("value");

                $('#frmRequest').remove();
                $.ajax({
                    type: "POST",
                    url: "processor.php",
                    data: params="action=modify_request_form&id_request_number="+id_request_number,
                    success: function(msg)
                    {
                        $('body').append(msg);
                        $(".time_field").mask("99:99");
                        $(".date_field").mask("99.99.9999");
                        Now = new Date();
                        //календарь
                        $(".date_field").datepicker({
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

                        $('#frmRequest').dialog({
                            autoOpen: true,
                            modal:true,
                            width: $(window).width() / 1.3,
                            height: "auto",
                            resizable: false,
                            buttons: [
                                {
                                    text: "Изменить заявку",
                                    click: function() {
                                        SendRequest(id_request_number);
                                        $(this).dialog("option","height","auto");
                                    }
                                },
                                {
                                    text: "Выход",
                                    click: function() {
                                        $( this ).dialog( "close" );
                                    }
                                }
                            ]
                        });
                    },
                    error: function(msg) {
                        alert('Ошибка при обращении к серверу. Код 11');
                    }
                })
            }
        );
    }

    function show_act_details(oTable, nTr)
    {
        oTable.fnOpen( nTr, fnFormatActDetails(oTable, nTr), 'details' );
        allowRefreshCounter += 1;
        $(".btnModifyAct, .btnDeleteAct, .btnReportByAct").button();
        $(".btnModifyAct, .btnDeleteAct, .btnReportByAct").button().unbind('click');
        $(".btnDeleteAct").button().click( function() {
                var id_repair = $(this).attr("value");
                if (!confirm('Вы действительно хотите удалить акт № '+id_repair+'?'))
                {
                    return;
                }
                $.ajax( {
                        type: "POST",
                        url: "inc/acts_modify.php",
                        data: params="action=delete_act&id_repair="+id_repair,
                        success: function(msg)
                        {
                            $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
                        },
                        error: function(msg)
                        {
                            alert('Ошибка при обращении к серверу. Код 12.1');
                        }
                    }
                );
            }
        );
        $('.btnModifyAct').button().click(function() {
            var id_repair = $(this).attr("value");
            $.ajax( {
                    type: "POST",
                    url: "inc/acts_modify.php",
                    data: params="action=get_act_info&id_repair="+id_repair,
                    async: false,
                    success: function(msg)
                    {
                        var info = JSON.parse(msg);
                        var date_str = info["act_date"].split(' ')[0];
                        var date_arr = date_str.split('-');
                        var date = new Date(date_arr[0],date_arr[1] - 1,date_arr[2]);
                        var day = date.getDate()<10?"0"+date.getDate():date.getDate();
                        var month = (date.getMonth()+1)<10?"0"+(date.getMonth()+1):(date.getMonth()+1);
                        var year = date.getFullYear();
                        $("#act_create_form #act_date").attr("value",day+"."+month+"."+year);
                        $("#act_create_form select[name='act_respondent_id']").attr("value",info["id_respondent"]);
                        $("#act_create_form select[name='car_id']").attr("value", info["id_car"]);
                        $("#act_create_form select[name='driver_id']").attr("value", info["id_driver"]);
                        $("#act_create_form select[name='mechanic_id']").attr("value", info["id_mechanic"]);
                        $("#act_create_form #reason_for_repair").attr("value", info["reason_for_repairs"]);
                        $("#act_create_form #work_performed").attr("value", info["work_performed"]);

                        var expended_array = info["expended"];
                        $("#act_expended_list option").remove();
                        for (var i =0; i < expended_array.length; i++)
                        {
                            var expended_material = expended_array[i]["material"];
                            var expended_count = expended_array[i]["count"];
                            $("#act_expended_list").append("<option value='"+expended_material+"@"+expended_count+"'>"+expended_material+" - "
                                +expended_count+"</option>");
                        }
                    },
                    error: function(msg)
                    {
                        alert('Ошибка при обращении к серверу. Код 12.1');
                    }
                }
            );
            $("#error_act_create").hide();
            $('#act_create_form').dialog( {
                    autoOpen: true,
                    modal: true,
                    title: 'Изменить акт №'+id_repair,
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
                                UpdateAct(id_repair);
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
        $('.btnReportByAct').button().click(function() {
            var id_repair = $(this).attr("value");
            $.fileDownload('inc/acts_report.php?id_repair='+id_repair,
                {failMessageHtml: "Не удалось загрузить файл, попробуйте еще раз."});
            return false;
        });
    }

    function show_waybill_details(oTable, nTr)
    {
        oTable.fnOpen( nTr, fnFormatWaybillDetails(oTable, nTr), 'details' );
        allowRefreshCounter += 1;
        $(".btnModifyWaybill, .btnDeleteWaybill, .btnReportByWaybill").button();
        $(".btnModifyWaybill, .btnDeleteWaybill, .btnReportByWaybill").button().unbind('click');
        $(".btnDeleteWaybill").button().click( function() {
                var id_waybill = $(this).attr("value");
                if (!confirm('Вы действительно хотите удалить путевой лист № '+id_waybill+'?'))
                {
                    return;
                }
                $.ajax( {
                        type: "POST",
                        url: "inc/waybills_modify.php",
                        data: params="action=delete_waybill&id_waybill="+id_waybill,
                        success: function(msg)
                        {
                            $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
                        },
                        error: function(msg)
                        {
                            alert('Ошибка при обращении к серверу. Код 12.1');
                        }
                    }
                );
            }
        );
        $('.btnModifyWaybill').button().click(function() {
            var id_waybill = $(this).attr("value");
            $.ajax( {
                    type: "POST",
                    url: "inc/waybills_modify.php",
                    data: params="action=get_waybill_info&id_waybill="+id_waybill,
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
                        $("#waybill_start_date").attr("value",start_day+"."+start_month+"."+start_year);
                        var end_date_str = info["end_date"].split(' ')[0];
                        var end_date_arr = end_date_str.split('-');
                        var end_date = new Date(end_date_arr[0],end_date_arr[1] - 1,end_date_arr[2]);
                        var end_day = end_date.getDate()<10?"0"+end_date.getDate():end_date.getDate();
                        var end_month = (end_date.getMonth()+1)<10?"0"+(end_date.getMonth()+1):(end_date.getMonth()+1);
                        var end_year = end_date.getFullYear();
                        $("#waybill_end_date").attr("value",end_day+"."+end_month+"."+end_year);
                        $("#waybill_create_form select[name='car_id']").attr("value", info["id_car"]);
                        $("#waybill_create_form select[name='driver_id']").attr("value", info["id_driver"]);
                        $("#waybill_create_form select[name='mechanic_id']").attr("value", info["id_mechanic"]);
                        $("#waybill_create_form select[name='dispatcher_id']").attr("value", info["id_dispatcher"]);
                        $("#waybill_create_form select[name='department']").attr("value",info["department"]);
                        $("#waybill_create_form select[name='fuel_type_id']").attr("value",info["id_fuel_type"]);
                        $("#waybill_address_supply").attr("value", info["address_supply"]);
                        $("#waybill_mileage_before").attr("value", info["mileage_before"]);
                        $("#waybill_mileage_after").attr("value", info["mileage_after"]);
                        $("#waybill_fuel_before").attr("value", info["fuel_before"]);
                        $("#waybill_given_fuel").attr("value", info["given_fuel"]);

                        var ways_array = info["ways"];
                        $("#ways_list option").remove();
                        for (var i =0; i < ways_array.length; i++)
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
                        alert('Ошибка при обращении к серверу. Код 12.1');
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
            var id_waybill = $(this).attr("value");
            $.fileDownload('inc/waybills_report.php?id_report_type=1&id_waybill='+id_waybill,
                {failMessageHtml: "Не удалось загрузить файл, попробуйте еще раз."});
            return false;
        });
        $('.btnReportByWaybillWithPeriod').button().click(function() {
            var id_waybill = $(this).attr("value");
            $.fileDownload('inc/waybills_report.php?id_report_type=2&id_waybill='+id_waybill,
                {failMessageHtml: "Не удалось загрузить файл, попробуйте еще раз."});
            return false;
        });
    }

    function show_mileage_details(sender)
    {
        //Отобразить форму внесения данных о пробеге
        var id_car = $(sender).attr("value");
        $("#error_mileagesEditor").hide();
        $('#mileagesEditor').dialog({
            autoOpen: true,
            modal: true,
            title: "Добавить пробег",
            width: $(window).width()/2,
            resizable: false,
            buttons: [
                {text: "Добавить",
                    click: function() {
                        if (!confirm('Вы действительно хотите добавить пробег?'))
                        {
                            return;
                        }
                        var milage_date = $("#mileage_date").attr("value");
                        var milage_value = $("#mileage_value").attr("value");
                        $("#error_mileagesEditor div").remove();
                        var is_error = false;
                        if (milage_date.length == 0)
                        {
                            $("#error_mileagesEditor").append("<div>Дата регистрации пробега не указана или указана неверно</div>");
                            is_error = true;
                        }
                        if (milage_value.match(/\d+/) === null) {
                            $("#error_mileagesEditor").append("<div>Некорректно указано значение пробега</div>");
                            is_error = true;
                        }
                        if (is_error)
                        {
                            $("#error_mileagesEditor").show();
                            return;
                        }
                        var mileage_type = $("#mileagesEditor select[name='mileage_type']").attr("value");
                        $.ajax( {
                                type: "POST",
                                url: "inc/add_mileage.php",
                                data: params="id_car="+id_car+"&milage_date="+milage_date+"&milage_value="+milage_value+
                                    "&mileage_type="+mileage_type,
                                success: function(msg)
                                {
                                    if (msg == '')
                                    {
                                        $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
                                        $('#mileagesEditor').dialog("close");
                                    } else
                                    {
                                        $("#error_mileagesEditor").append("<div>$msg</div>");
                                    }
                                },
                                error: function(msg)
                                {
                                    alert('Ошибка при обращении к серверу. Код 18');
                                }
                            }
                        );
                    }},
                {text: "Отменить",
                    click: function() {
                        $( this ).dialog( "close" );
                    }}
            ]
        }).height("auto");
    }

    /////////////////////////
    //Функции инициализации//
    /////////////////////////

    //Функция инициализация состояния внутренних переменных
    function initVariables()
    {
        $.ajax({
            type: "POST",
            url: "inc/req_permissions.php",
            async: false,
            success: function(msg)
            {
                var req_mask = 63;
                if ((msg & 1) == 1)
                {
                    $('#rqlb1').show();
                }
                else
                {
                    req_mask -= 1;
                    $('#rqlb1').hide();
                }
                if ((msg & 2) == 2)
                    $('#rqlb3').show();
                else
                {
                    req_mask -= 2;
                    $('#rqlb3').hide();
                }
                if ((msg & 4) == 4)
                    $('#rqlb2').show();
                else
                {
                    req_mask -= 4;
                    $('#rqlb2').hide();
                }
                if ((msg & 8) == 8)
                    $('#rqlb4').show();
                else
                {
                    req_mask -= 8;
                    $('#rqlb4').hide();
                }
                if ((msg & 16) == 16)
                    $('#rqlb5').show();
                else
                {
                    req_mask -= 16;
                    $('#rqlb5').hide();
                }
                if ((msg & 32) == 32)
                    $('#rqlb6').show();
                else
                {
                    req_mask -= 32;
                    $('#rqlb6').hide();
                }
                if (((req_mask & 1) != 1) && (getCookie('id_request') == 1))
                {
                    setCookie('id_request',2);
                }
                if (((req_mask & 4) != 4) && (getCookie('id_request') == 2))
                {
                    setCookie('id_request',3);
                }
                if (((req_mask & 2) != 2) && (getCookie('id_request') == 3))
                {
                    setCookie('menu_code',2);
                }
                if (((req_mask & 8) != 8) && (getCookie('menu_code') == 2))
                {
                    setCookie('menu_code',3);
                }
                if (((req_mask & 16) != 16) && (getCookie('menu_code') == 3))
                {
                    setCookie('menu_code',4);
                }
                if (((req_mask & 32) != 32) && (getCookie('menu_code') == 4))
                {
                    setCookie('menu_code',0);
                    setCookie('id_request',0);
                }
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 10');
            }
        })
    }

    //Функция инициализации состояния кнопок главной формы
    function initButtonsState()
    {
        if (getCookie("menu_code") && (getCookie("menu_code") == 1))
        {
            document.getElementById('btnReports').checked = true;
            $('#ShowMyRequestsOnly').hide();
            $('#ShowMyRequestsOnlyLabel').hide();
            $('#btnCreateRequest').hide();
            $('#btnCreateAct').hide();
            $('#btnCreateWaybill').hide();
            $('#btnShowCalendar').hide();
        } else
        if (getCookie("menu_code") && (getCookie("menu_code") == 2))
        {
            document.getElementById('btnCarsInfo').checked = true;
            $('#ShowMyRequestsOnly').hide();
            $('#ShowMyRequestsOnlyLabel').hide();
            $('#btnCreateRequest').hide();
            $('#btnCreateAct').hide();
            $('#btnCreateWaybill').hide();
            $('#btnShowCalendar').hide();
            setCookie("header","Информация по пробегу транспортных средств администрации");
        } else
        if (getCookie("menu_code") && (getCookie("menu_code") == 3))
        {
            document.getElementById('btnRepairActs').checked = true;
            $('#ShowMyRequestsOnly').hide();
            $('#ShowMyRequestsOnlyLabel').hide();
            $('#btnCreateRequest').hide();
            $('#btnCreateWaybill').hide();
            if (hasCreateActsPrivilege())
            {
                $('#btnCreateAct').show();
            }
            $('#btnShowCalendar').hide();
            setCookie("header","Акты выполненных работ по обслуживанию автотранспорта");
        } else
        if (getCookie("menu_code") && (getCookie("menu_code") == 4))
        {
            document.getElementById('btnWaybills').checked = true;
            $('#ShowMyRequestsOnly').hide();
            $('#ShowMyRequestsOnlyLabel').hide();
            $('#btnCreateRequest').hide();
            $('#btnCreateAct').hide();
            if (hasCreateWaybillsPrivilege())
            {
                $('#btnCreateWaybill').show();
            }
            $('#btnShowCalendar').hide();
            setCookie("header","Путевые листы");
        }
        else
        {
            switch(getCookie("id_request"))
            {
                case "0":
                    setCookie('header','Вид группы заявок не указан');
                    break;
                case "1":
                    document.getElementById('btnTransportRequests').checked = true;
                    setCookie('header','Заявки на транспорт');
                    break;
                case "2":
                    document.getElementById('btnGreatHallRequests').checked = true;
                    setCookie('header','Заявки на конферец-зал');
                    break;
                case "3":
                    document.getElementById('btnSmallHallRequests').checked = true;
                    setCookie('header','Заявки на зал заседания думы');
                    break;
            }
            $('#btnCreateAct').hide();
            $('#btnCreateWaybill').hide();
            $('#ShowMyRequestsOnly').show();
            $('#ShowMyRequestsOnlyLabel').show();
            if (hasCreateRequestPrivilege())
                $('#btnCreateRequest').show();
            else
                $('#btnCreateRequest').hide();
            $('#btnShowCalendar').show();
            if (getCookie("only_my_requests") == 1)
            {
                document.getElementById('ShowMyRequestsOnly').checked = true;
            }
        }
        $("#requests_group").buttonset();
        $("#header").text(getCookie("header"));
    }

    //Функция инициализации формы детальной информации о заявке
    function fnFormatReqDetails ( oTable, nTr )
    {
        var msga = "";
        var aData = oTable.fnGetData( nTr );
        var id_request_number = aData[1];
        $.ajax({
            type: "POST",
            url: "inc/details_by_id.php",
            data: params='action=request&id_request_number='+id_request_number,
            async: false,
            success: function(msg)
            {
                msga = msg;
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 9');
            }
        })
        return msga;
    }

    //Функция инициализации формы детальной информации об акте выполненных работ
    function fnFormatActDetails ( oTable, nTr )
    {
        var msga = "";
        var aData = oTable.fnGetData( nTr );
        var id_repair = aData[1];
        $.ajax({
            type: "POST",
            url: "inc/details_by_id.php",
            data: params='action=act&id_repair='+id_repair,
            async: false,
            success: function(msg)
            {
                msga = msg;
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 9');
            }
        })
        return msga;
    }

    //Функция инициализации формы детальной информации о путевых листах
    function fnFormatWaybillDetails( oTable, nTr )
    {
        var msga = "";
        var aData = oTable.fnGetData( nTr );
        var id_waybill = aData[1];
        $.ajax({
            type: "POST",
            url: "inc/details_by_id.php",
            data: params='action=waybill&id_waybill='+id_waybill,
            async: false,
            success: function(msg)
            {
                msga = msg;
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 9');
            }
        })
        return msga;
    }

    //Функция инициализации колонок DataTable
    function initColumns()
    {
        $.ajax({
            type: "POST",
            url: "inc/jsonp.php",
            data: "action=init_columns",
            async: false,
            success: function(msg) {
                $("#example thead tr").remove();
                $("#example tfoot tr").remove();
                var headers = JSON.parse(msg);
                $("#example thead").append(headers["head"]);
                $("#example tfoot").append(headers["foot"]);
                if (getCookie("menu_code") == 1)
                {
                    var rep_id = getCookie("report_id");
                    $("#example tfoot").hide();
                    for (var i = 0; i < rep_with_summary.length; i++)
                    {
                        if (rep_id == rep_with_summary[i][0])
                            $("#example tfoot").show();
                    }
                }
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 8');
            }
        });
    }

    //Начальная инициализация таблицы с данными
    function initDataTable()
    {
        var ex = document.getElementById('example');
        if ( $.fn.DataTable.fnIsDataTable( ex ) ) {
            $(ex).dataTable().fnDestroy();                      
        }
        initColumns();
       // TableTools.DEFAULTS.aButtons = [ "xls" ];
        var oTable = $('#example').dataTable( {
            "bProcessing": true,
			"bStateSave": true,
			"bDeferRender": true,
            "bServerSide": false,
            "bJQueryUI": true,
            "sScrollY": $(window).height() - 300,
            "bFilter": true,
            "iDisplayLength": 25,
            "sAjaxSource": "inc/jsonp.php",
            "sServerMethod": "POST",
            "sPaginationType": "full_numbers",
            "sDom": '<"H"lTfr>t<"F"ip>',
            "oTableTools": {
                "sSwfPath": "swf/copy_csv_xls_pdf.swf",
                "aButtons": [
                    {
                        "sExtends": "xls",
                        "sButtonText": "Экспорт",
                        "sFileName": "*.xls"
                    }
                ]
            },
            "oLanguage": {
                "sLengthMenu": "Отображено _MENU_ записей на страницу",
                "sSearch": "Поиск:",
                "sZeroRecords": "Ничего не найдено - извините",
                "sInfo": "Показано с _START_ по _END_ из _TOTAL_ записей",
                "sInfoEmpty": "Показано с 0 по 0 из 0 записей",
                "sInfoFiltered": "(Отфильтровано из _MAX_ записей)",
                "sProcessing": "Загрузка...",
                "oPaginate": {
                    "sFirst": "Первая",
                    "sLast":"Посл.",
                    "sNext":"След.",
                    "sPrevious":"Пред."
                }
            },
            "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull)
            {
                $('td', nRow).addClass('alignCenter');
            },
            "fnFooterCallback": function ( nRow, aaData, iStart, iEnd, aiDisplay ) {
                if (getCookie('menu_code') != 1)
                    return;
                var report_id = getCookie('report_id');
                var rep_cfg = "";
                for (var i = 0; i < rep_with_summary.length; i++)
                {
                    if (rep_with_summary[i][0] == report_id)
                    {
                        rep_cfg = rep_with_summary[i];
                        break;
                    }
                    if (i == (rep_with_summary.length - 1))
                        return;
                }
                var sum_array = new Array(rep_cfg[2]);
                for (var i = 0; i < rep_cfg[2]; i++)
                    sum_array[i] = 0;
                for ( var i=0 ; i<aaData.length ; i++ )
                    for (var j = 0; j < rep_cfg[2]; j++)
                        sum_array[j] += aaData[i][rep_cfg[1]+j]*1;
                var nCells = nRow.getElementsByTagName('th');
                nCells[0].innerHTML = 'Итого';
                for (var i = 1; i < nCells.length; i++)
                    nCells[i].innerHTML = '-';
                for (var i = 0; i < rep_cfg[2]; i++)
                    nCells[rep_cfg[1]+i].innerHTML = sum_array[i];
            }
        } );

        $("#example_length").first().append('<input type="checkbox" id="ShowMyRequestsOnly"><label id="ShowMyRequestsOnlyLabel" for="ShowMyRequestsOnly">Только мои заявки</label>');
        //Обработчик событий нажатия на кнопку "Мои заявки"
        $('#ShowMyRequestsOnly').click(function()
        {
            if ($(this).attr("checked"))
                setCookie("only_my_requests",1);
            else
                setCookie("only_my_requests",0);
            $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
            initButtonsState();
        });
        //В браузере Chrome некорректно просчитывается ширина datatable, из-за чего едет разметка колонок.
        //Костыль позволяет немного сгладить этот кошмар. Надеюсь в будущем поправят либо datatable, либо chrome
        /*if ( $.browser.webkit ) {
            setTimeout( function () {
                oTable.fnAdjustColumnSizing();
            }, 10 );
        }*/
    }

    //Функция инициализации календаря
    function initCalendar()
    {
        $('#calendar').fullCalendar({
            editable: false,
            header: {
                left: 'prevYear,prev,next,nextYear today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            dayNames: ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'],
            dayNamesShort: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
            monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль',
                'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
            monthNamesShort: ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля',
                'августа', 'сентября', 'октября', 'ноября', 'декабря'],
            titleFormat: {
                month: 'MMMM yyyy',
                week: "d [ MMM] [ yyyy] { '&#8212;' [d] MMM yyyy }",
                day: 'dddd, d MMM, yyyy' },
            buttonText: {
                prev:     '&nbsp;&#9668;&nbsp;',  // left triangle
                next:     '&nbsp;&#9658;&nbsp;',  // right triangle
                prevYear: '&nbsp;&lt;&lt;&nbsp;', // <<
                nextYear: '&nbsp;&gt;&gt;&nbsp;', // >>
                today:    'Сегодня',
                month:    'Месяц',
                week:     'Неделя',
                day:      'День'
            },
            firstDay: 1,
            firstHour: 7,
            axisFormat: "HH:mm",
            allDaySlot: false,
            height: $(window).height() - 200,
            cache: true,
            timeFormat: 'H:mm',
            events: function(start, end, callback) {
                //Формируем данные для фильтрации
                var department = $('#calendarSettings select[name=department]').attr("value");
                if (department == undefined)
                    return;
                if (getCookie('id_request') == 1)
                    var transport = $('#calendarSettings select[name=car_id]').attr("value");
                var requestStates = new Array();
                $("#calendarSettings .requestState").each(
                    function() {
                        if ($(this).attr("checked"))
                            requestStates.push($(this).attr("value"));
                    }
                );
                //Преобразуем полученные данные в строку запроса
                var paramstr = "";
                var startStr = dateToString(start);
                var endStr = dateToString(end);
                for (var i = 0; i < requestStates.length; i++)
                    paramstr += 'requestStates[]='+requestStates[i]+'&';
                if (getCookie('id_request') == 1)
                    paramstr += 'transport='+transport+'&';
                paramstr += 'department='+department+'&start='+startStr+'&end='+endStr;
                $.ajax({
                    url: "inc/calendar_source.php",
                    type: "POST",
                    data: params=paramstr,
                    success: function(msg) {
                        var events = [];
                        var xmlDoc = $.parseXML(msg);
                        var xml = $(xmlDoc);
                        xml.find("event").each(function() {
                            events.push({
                                title: $(this).attr('title'),
                                start: $(this).attr('start'),
                                end: $(this).attr('end'),
                                allDay: $(this).attr('allDay') == "true",
                                color: $(this).attr('color')
                            });
                        });
                        callback(events);
                    },
                    error: function(msg)
                    {
                        alert('Ошибка при обращении к серверу. Код 7');
                    }
                });
            },
            eventClick: function(calEvent, jsEvent, view) {
                if (calEvent.title == ' Занято')
                {
                    alert('Вы не можете просматривать заявки других департаментов');
                    return;
                }
                var id_request_number = calEvent.title.slice(2, calEvent.title.indexOf(','));
                var msge = "";
                var has_msge = false;
                $.ajax({
                    type: "POST",
                    url: "inc/details_by_id.php",
                    data: params='action=request&id_request_number='+id_request_number+'&from_calendar=1',
                    async: false,
                    success: function(msg)
                    {
                        msge = msg;
                        has_msge = true;
                    },
                    error: function(msg)
                    {
                        alert('Ошибка при обращении к серверу. Код 6');
                    }
                })
                if (!has_msge)
                    return;
                $('#calendar_details table').remove();
                $('#calendar_details').append(msge);
                $('#calendar_details').attr('title',calEvent.title);
                $('#calendar_details').dialog({
                    autoOpen: true,
                    modal:true,
                    width: $(window).width() / 1.3,
                    title: 'Подробности заявки №'+id_request_number,
                    height:  $('#calendar_details').height,
                    resizable: false,
                    buttons: [
                        {
                            text: "Закрыть",
                            click: function() {
                                $( this ).dialog( "close" );
                            }
                        }
                    ]
                }).height("auto");
            },
            dayClick: function(date, allDay, jsEvent, view) {
                if ((view.name == 'month') || (view.name == 'agendaWeek'))
                {
                    $("#calendar").fullCalendar('gotoDate',date.getFullYear(), date.getMonth(), date.getDate());
                    $("#calendar").fullCalendar('changeView','agendaDay');
                }
            }
        });
    }

    //Функция инициализации формы редактирования пробега по транспорту
    function initMileagesEditor()
    {
        $("#mileage_date").mask("99.99.9999");
        Now = new Date();
        //календарь
        $("#mileage_date").datepicker({
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
    }

    //Функция инициализации формы отчета
    function initReportForm()
    {
        $("#start_date").mask("99.99.9999");
        $("#end_date").mask("99.99.9999");
        Now = new Date();
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
            success: function(msg)
            {
                $("#report_id").append(msg);
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 1');
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
                alert('Ошибка при обращении к серверу. Код 2');
            }
        });
        $.ajax({
            type: "POST",
            url: "processor.php",
            data: params="action=display_all_transport_combobox",
            async: false,
            success: function(msg)
            {
                $("#car").append(msg);
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 1');
            }
        });
        $.ajax({
            type: "POST",
            url: "processor.php",
            data: params="action=display_all_fuel_type_combobox",
            async: false,
            success: function(msg)
            {
                $("#fuel").append(msg);
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 1');
            }
        });
        $("#car_row").hide();
        $("#fuel_row").hide();
        $("#report_id").bind("change", function() {
            if ($("#report_id").attr("value").inList(rep_with_car_id)) {
                $("#car_row").show();
            } else
                $("#car_row").hide();
            if ($("#report_id").attr("value").inList(rep_with_fuel_type)) {
                $("#fuel_row").show();
            } else
                $("#fuel_row").hide();
            if ($("#report_id").attr("value").inList(rep_without_dep_and_date_type)) {
                $("#department_row").hide();
                $("#date_row").hide();
            } else
            {
                $("#department_row").show();
                $("#date_row").show();
            }
        });
    }

    //Функция инициализации формы выбора автомобиля
    function initCarSelectForm()
    {
        $.ajax( {
                type: "POST",
                url: "inc/cars_list.php",
                async: false,
                success: function(msg)
                {
                    $('#select_car_form').append(msg);
                },
                error: function(msg)
                {
                    alert('Ошибка при обращении к серверу. Код 3');
                }
            }
        );
    }

    //Функция инициализации формы создания акта выполненных работ
    function initActCreateForm()
    {
        $("#act_date").mask("99.99.9999");
        Now = new Date();
        //календарь
        $("#act_date").datepicker({
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
        $("#insert_expended, #delete_expended").button();
        $.ajax( {
                type: "POST",
                url: "inc/cars_list.php",
                async: false,
                success: function(msg)
                {
                    $('#act_car select').remove();
                    $('#act_car').append(msg);
                },
                error: function(msg)
                {
                    alert('Ошибка при обращении к серверу. Код 3.1');
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/respondents_list.php",
                async: false,
                success: function(msg)
                {
                    $('#act_respondent select').remove();
                    $('#act_respondent').append(msg);
                },
                error: function(msg)
                {
                    alert('Ошибка при обращении к серверу. Код 3.2');
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/drivers_list.php",
                async: false,
                success: function(msg)
                {
                    $('#act_driver select').remove();
                    $('#act_driver').append(msg);
                },
                error: function(msg)
                {
                    alert('Ошибка при обращении к серверу. Код 3.3');
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/mechanics_list.php",
                async: false,
                success: function(msg)
                {
                    $('#act_mechanic select').remove();
                    $('#act_mechanic').append(msg);
                },
                error: function(msg)
                {
                    alert('Ошибка при обращении к серверу. Код 3.4');
                }
            }
        );
        $("#insert_expended").click(function() {
            $("#error_add_expended").hide();
            $("#act_expended_edit_name").attr("value","");
            $("#act_expended_edit_count").attr("value","");
            $('#add_expended').dialog({
                autoOpen: true,
                modal: true,
                title: 'Добавить расходный материал',
                width: $(window).width()/1.5,
                height: 'auto',
                resizable: false,
                buttons: [
                    {
                        text: "Добавить",
                        click: function() {
                            $("#error_add_expended div").remove();
                            $("#error_add_expended").hide();
                            var expended_name = $("#act_expended_edit_name").attr("value");
                            var expended_count = $("#act_expended_edit_count").attr("value");
                            var is_correct = true;
                            if ($.trim(expended_name).length== 0)
                            {
                                $("#error_add_expended").append("<div>Необходимо указать название материала</div>");
                                is_correct = false;
                            }
                            if ($.trim(expended_count).length == 0)
                            {
                                $("#error_add_expended").append("<div>Необходимо указать количество материала</div>");
                                is_correct = false;
                            } else
                            {
                                expended_count = expended_count.replace(",", ".");
                                if (!floatCorrect(expended_count))
                                {
                                    $("#error_add_expended").append("<div>Некорректный формат количества материала. Разрешено использовать только числа в целом или вещественном виде точностью не более 3 знаков после запятой</div>");
                                    is_correct = false;
                                }
                            }
                            if (!is_correct)
                            {
                                $("#error_add_expended").show();
                            }
                            else
                            {
                                $("#act_expended_list").append("<option value='"+expended_name+"@"+expended_count+"'>"+expended_name+" - "
                                    +expended_count+"</option>");
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
            }).height('auto')
        });
        $("#delete_expended").click(function() {
            $("#act_expended_list option:selected").remove();
        });
    }

    //Функция инициализации формы создания путевых листов
    function initWaybillCreateForm()
    {
        $("#waybill_start_date").mask("99.99.9999");
        $("#waybill_end_date").mask("99.99.9999");
        Now = new Date();
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
                async: false,
                success: function(msg)
                {
                    $('#waybill_car select').remove();
                    $('#waybill_car').append(msg);
                },
                error: function(msg)
                {
                    alert('Ошибка при обращении к серверу. Код 3.1');
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/drivers_list.php",
                async: false,
                success: function(msg)
                {
                    $('#waybill_driver select').remove();
                    $('#waybill_driver').append(msg);
                },
                error: function(msg)
                {
                    alert('Ошибка при обращении к серверу. Код 3.3');
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/mechanics_list.php",
                async: false,
                success: function(msg)
                {
                    $('#waybill_mechanic select').remove();
                    $('#waybill_mechanic').append(msg);
                },
                error: function(msg)
                {
                    alert('Ошибка при обращении к серверу. Код 3.4');
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/dispatchers_list.php",
                async: false,
                success: function(msg)
                {
                    $('#waybill_dispatcher select').remove();
                    $('#waybill_dispatcher').append(msg);
                },
                error: function(msg)
                {
                    alert('Ошибка при обращении к серверу. Код 3.5');
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/departments_without_stage_list.php",
                async: false,
                success: function(msg)
                {
                    $('#waybill_department select').remove();
                    $('#waybill_department').append(msg);
                },
                error: function(msg)
                {
                    alert('Ошибка при обращении к серверу. Код 3.1');
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/fuel_types_list.php",
                async: false,
                success: function(msg)
                {
                    $('#waybill_fuel_type select').remove();
                    $('#waybill_fuel_type').append(msg);
                },
                error: function(msg)
                {
                    alert('Ошибка при обращении к серверу. Код 3.1');
                }
            }
        );
        $("#insert_way").click(function() {
            $("#error_add_way").hide();
            $("#way_value").attr("value","");
            $("#way_out_time").attr("value","");
            $("#way_return_time").attr("value","");
            $("#way_distance").attr("value","");

            $("#way_out_time").mask("99:99");
            $("#way_return_time").mask("99:99");

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
                            var way_value = $("#way_value").attr("value");
                            var way_out_time = $("#way_out_time").attr("value");
                            var way_return_time = $("#way_return_time").attr("value");
                            var way_distance = $("#way_distance").attr("value");
                            var is_correct = true;
                            if ($.trim(way_value).length== 0)
                            {
                                $("#error_add_way").append("<div>Необходимо указать маршрут</div>");
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
                                $("#ways_list").append("<option value='"+way_value+"@"+way_out_time+"@"+
                                    way_return_time+"@"+way_distance+"'>"+"("
                                    +way_out_time+"-"+way_return_time+")"+way_distance_str+" - "+way_value+"</option>");
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
            }).height('auto')
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

    //Функция автообновления таблицы по таймауту
    function initAutoRefresh()
    {
        setTimeout(AutoRefresh, 1000*60); //Обновлять таблицу раз в 1 минуту              // changed by ax
    }

    ////////////////////////////////////////////
    //Функции проверки состояния форм запросов//
    ////////////////////////////////////////////
    function intCorrect(int_val)
    {
        var reg = /^[0-9]+$/i;
        if (reg.test(int_val)) return true;
        else return false;
    }

    function floatCorrect(float_val)
    {
        var reg = /^[0-9]+[.]{0,1}[0-9]{0,3}$/i;
        if (!reg.test(float_val)) return false;
        else return true;
    }

    function timeCorrect(time)
    {
        var reg = /^([0-1][0-9]|[2][0-3])(:([0-5][0-9])){1,2}$/i;
        if(reg.test(time)) return true;
        else return false;
    }

    function dateCorrect(date)
    {
        var date_params = date.split('.');
        var dateObj = new Date(date_params[2], date_params[1] - 1, date_params[0]);
        if ((date_params[2] == dateObj.getFullYear()) &&
            (date_params[1] == dateObj.getMonth()+1) &&
            (date_params[0] == dateObj.getDate()))
            return true;
        else
            return false;
    }

    function checkReportSettings()
    {
        $("#error_reportSettings div").remove();
        var start_date = $("#start_date").attr("value");
        var end_date = $("#end_date").attr("value");
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

    /////////////////////
    //Служебные функции//
    /////////////////////

    //Функция изменения вида топлива
    function change_fuel_type()
    {
        var id_car = $("#waybill_create_form select[name='car_id']").attr("value");
        for (var i = 0; i < cars_default_fuel.length; i++)
        {
            if (cars_default_fuel[i]["id"] == id_car)
                $("#waybill_create_form select[name='fuel_type_id']").attr("value", cars_default_fuel[i]["id_fuel_default"]);
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
                alert('Ошибка при обращении к серверу. Код 6');
            }
        });
        return array;
    }

    function AutoRefresh()
    {
       var refresh_time=1000*60;
	   if (allowRefreshCounter == 0)
        {
			$('#example').dataTable().fnReloadAjax(null,"inc/jsonp.php",true);
		    setTimeout(AutoRefresh, refresh_time); //Обновлять таблицу раз в 1 минуту          
        } else
        {
            if (forceRefreshCounter == 10)      //Принудительно обновляем таблицу после 10 минут бездействия
            {
                forceRefreshCounter = 0;
                allowRefreshCounter = 0;
				$('#example').dataTable().fnReloadAjax(null,"inc/jsonp.php",true);
                setTimeout(AutoRefresh, refresh_time);                                         
            } else
            {
                forceRefreshCounter += 1;
                setTimeout(AutoRefresh, refresh_time); //Если нам не удается обновить, то ставим таймаут на 1 минуту и ждем разрешения          
            }
        }
        if (allowRefreshCalendar)
        {
            $('#calendar').fullCalendar( 'refetchEvents' );
            $('#calendar').fullCalendar( 'rerenderEvents' );
        }
    }

    //Есть привелегия на создание заявок
    function hasCreateRequestPrivilege()
    {
        hasCRP = false;
        $.ajax({
            type: "POST",
            url: "inc/can_create_request.php",
            async: false,
            success: function(msg)
            {
                if (msg == '1')
                    hasCRP = true;
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 4.0');
            }
        });
        return hasCRP;
    }

    //Есть привелегия на создание актов обслуживания
    function hasCreateActsPrivilege()
    {
        hasCRP = false;
        $.ajax({
            type: "POST",
            url: "inc/can_create_acts.php",
            async: false,
            success: function(msg)
            {
                if (msg == '1')
                    hasCRP = true;
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 4.1');
            }
        });
        return hasCRP;
    }

    //Есть привелегия на создание путевых листов
    function hasCreateWaybillsPrivilege()
    {
        hasCRP = false;
        $.ajax({
            type: "POST",
            url: "inc/can_create_waybills.php",
            async: false,
            success: function(msg)
            {
                if (msg == '1')
                    hasCRP = true;
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 4.2');
            }
        });
        return hasCRP;
    }

    //Добавление (если номер заявки 0) и изменение (если номер заявки > 0) заявки
    function SendRequest(id_request_number){
        var frm = document.getElementById('ParamTable');
        var elems = frm.getElementsByTagName('*');
        var params='';
        for(var i=0;i<elems.length;i++) {
            if ((elems[i].name!=null)&&((elems[i].name.indexOf("param")==0) || (elems[i].name == "department")))
                if (elems[i].type == "checkbox")
                {
                    if (elems[i].checked)
                        params=params+elems[i].name+'='+elems[i].value+'&'; }
                else
                if ((elems[i].value!=null) && (elems[i].value!=""))
                    params=params+elems[i].name+'='+elems[i].value+'&';
        }
        var data = params+'action=process_request&id_request_number='+id_request_number;
        if (getCookie('id_request') == 1)
        {
            var id_car = $("#ParamTable select[name='car_id']").attr("value");
            if (!isNaN(id_car))
                data = data + '&id_car='+id_car;
        }
        $.ajax({
            type: "POST",
            url: "processor.php",
			async:false,
            data: data,
            success: function(msg)
            {
                var div = document.getElementById("div_result");
                div.innerHTML = msg;
                $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
                if ((msg.indexOf('Заявка изменена') > -1) ||
                    (msg.indexOf('Заявка добавлена') > -1))
                {
                    //Если заявка успешно подана, закрываем диалог через 2000 мс
                    setTimeout(closeRequestDialog, 2000);
                }
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 5');
            }
        })
    }

    //Создание/изменение акта выполненных работ
    function ProcessAct(id_repair)
    {
        var act_date = $("#act_create_form #act_date").attr("value");
        var respondent_id = $("#act_create_form select[name='act_respondent_id']").attr("value");
        var car_id = $("#act_create_form select[name='car_id']").attr("value");
        var driver_id = $("#act_create_form select[name='driver_id']").attr("value");
        var mechanic_id = $("#act_create_form select[name='mechanic_id']").attr("value");
        var reason_for_repair = $("#act_create_form #reason_for_repair").attr("value");
        var work_performed = $("#act_create_form #work_performed").attr("value");
        var expended_list = "";
        if (($.trim(act_date) == "") || ($.trim(respondent_id) == "") ||
            ($.trim(car_id) == "") || ($.trim(driver_id) == "") ||
            ($.trim(mechanic_id) == ""))
        {
            var div = document.getElementById("error_act_create");
            div.innerHTML = "Не все обязательные поля заполнены";
            $("#error_act_create").show();
            return;
        }
        if (!dateCorrect(act_date))
        {
            var div = document.getElementById("error_act_create");
            div.innerHTML = "Дата создания акта заполнена некорректно";
            $("#error_act_create").show();
            return;
        }
        var array = new Array();
        $("#act_expended_list option").each(function() {
            expended_list += $(this).attr("value")+"@@";
        })
        var action = "";
        if (id_repair == 0)
            action = "action=insert_act";
        else
            action = "action=update_act&repair_id="+id_repair;
        var data = action+"&act_date="+act_date+"&responded_id="+respondent_id+"&car_id="+car_id+
            "&driver_id="+driver_id+"&mechanic_id="+mechanic_id+"&reason_for_repair="+reason_for_repair+
            "&work_performed="+work_performed+"&expended_list="+expended_list;
        $.ajax({
            type: "POST",
            url: "inc/acts_modify.php",
            async: false,
            data: data,
            success: function(msg)
            {
                if ($.trim(msg).length == 0)
                {
                    //Если акт успешно подан, закрываем диалог
                    $( '#act_create_form' ).dialog( "close" )
                    $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
                } else
                {
                    var div = document.getElementById("error_act_create");
                    div.innerHTML = msg;
                    $("#error_act_create").show();
                }
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 5.1');
            }
        })
    }

    //Функция изменения акта в базе данных
    function UpdateAct(id_repair)
    {
        ProcessAct(id_repair);
    }

    //Функция добавления акта в базу данных
    function InsertAct()
    {
        ProcessAct(0);
    }

    function ProcessWaybill(id_waybill)
    {
        $("#error_waybill_create div").remove();
        $("#error_waybill_create").hide();
        var waybill_start_date = $("#waybill_start_date").attr("value");
        var waybill_end_date = $("#waybill_end_date").attr("value");
        var car_id = $("#waybill_create_form select[name='car_id']").attr("value");
        var driver_id = $("#waybill_create_form select[name='driver_id']").attr("value");
        var mechanic_id = $("#waybill_create_form select[name='mechanic_id']").attr("value");
        var dispatcher_id = $("#waybill_create_form select[name='dispatcher_id']").attr("value");
        var department = $("#waybill_create_form select[name='department']").attr("value");
        var address_supply = $("#waybill_address_supply").attr("value");
        var waybill_mileage_before = $("#waybill_mileage_before").attr("value");
        var waybill_mileage_after = $("#waybill_mileage_after").attr("value");
        var waybill_fuel_before = $("#waybill_fuel_before").attr("value").replace(",",".");
        var waybill_given_fuel = $("#waybill_given_fuel").attr("value").replace(",",".");
        var fuel_type_id = $("#waybill_create_form select[name='fuel_type_id']").attr("value");
        var ways_list = "";
        var is_correct = true;
        if (($.trim(waybill_start_date) == "") || ($.trim(waybill_end_date) == "") ||
            ($.trim(car_id) == "") || ($.trim(driver_id) == "") ||
            ($.trim(mechanic_id) == "") || ($.trim(department) == "") || ($.trim(dispatcher_id) == ""))
        {
            $("#error_waybill_create").append("<div>Не все обязательные поля заполнены</div>");
            $("#error_waybill_create").show();
            return;
        }
        if (!dateCorrect(waybill_start_date) || !dateCorrect(waybill_end_date))
        {
            $("#error_waybill_create").append("<div>Период действия путевого листа задан некорректно</div>");
            is_correct = false;
        }
        if (($.trim(waybill_mileage_before) != "") && (!intCorrect(waybill_mileage_before)))
        {
            $("#error_waybill_create").append("<div>Показание спидометра до выезда указано неверно</div>");
            is_correct = false;
        }
        if (($.trim(waybill_mileage_after) != "") && (!intCorrect(waybill_mileage_after)))
        {
            $("#error_waybill_create").append("<div>Показание спидометра после возвращения указано неверно</div>");
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

        var array = new Array();
        $("#ways_list option").each(function() {
            ways_list += $(this).attr("value")+"$";
        })
        var action = "";
        if (id_waybill == 0)
            action = "action=insert_waybill";
        else
            action = "action=update_waybill&waybill_id="+id_waybill;
        var data = action+"&start_date="+waybill_start_date+"&end_date="+waybill_end_date+"&car_id="+car_id+
            "&driver_id="+driver_id+"&mechanic_id="+mechanic_id+"&dispatcher_id="+dispatcher_id+"&department="+department+
            "&address_supply="+address_supply+"&mileage_before="+waybill_mileage_before+
            "&mileage_after="+waybill_mileage_after+"&fuel_before="+waybill_fuel_before+
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
                    $( '#waybill_create_form' ).dialog( "close" )
                    $('#example').dataTable().fnReloadAjax("inc/jsonp.php");
                } else
                {
                    $("#error_waybill_create").append("<div>"+msg+"</div>");
                    $("#error_waybill_create").show();
                }
            },
            error: function(msg)
            {
                alert('Ошибка при обращении к серверу. Код 5.2');
            }
        })
    }

    //Функция добавления путевого листа в базу данных
    function InsertWaybill()
    {
        ProcessWaybill(0);
    }

    function UpdateWaybill(id_waybill)
    {
        ProcessWaybill(id_waybill)
    }

    function closeRequestDialog()
    {
        $( '#frmRequest' ).dialog( "close" );
    }

    function dateToString(date)
    {
        var year, month, day, hour, minute;
        year = date.getFullYear();
        month = date.getMonth() + 1;
        if (month < 10)
            month = '0'+month;
        day = date.getDate();
        if (day < 10)
            day = '0'+day;
        hour = date.getHours();
        if (hour < 10)
            hour = '0'+hour;
        minute = date.getMinutes();
        if (minute < 10)
            minute = '0'+minute;
        return year+'-'+month+'-'+day+' '+hour+':'+minute;
    }
	
    //Функция перезагрузки данных в таблицу DataTable
    $.fn.dataTableExt.oApi.fnReloadAjax = function ( oSettings, sNewSource, fnCallback, bStandingRedraw )
    {
        if ( sNewSource !== undefined && sNewSource !== null ) {
            oSettings.sAjaxSource = sNewSource;
        }

        // Server-side processing should just call fnDraw
        if ( oSettings.oFeatures.bServerSide ) {
            this.fnDraw();
            return;
        }

        this.oApi._fnProcessingDisplay( oSettings, true );
        var that = this;
        var iStart = oSettings._iDisplayStart;
        var aData = [];
        var pos=$('.dataTables_scrollBody').scrollTop(); //позиция полосы прокрутки
        this.oApi._fnServerParams( oSettings, aData );

        oSettings.fnServerData.call( oSettings.oInstance, oSettings.sAjaxSource, aData, function(json) {
            /* Clear the old information from the table */
            that.oApi._fnClearTable( oSettings );

            /* Got the data - add it to the table */
            var aData =  (oSettings.sAjaxDataProp !== "") ?
                that.oApi._fnGetObjectDataFn( oSettings.sAjaxDataProp )( json ) : json;

            for ( var i=0 ; i<aData.length ; i++ )
            {
                that.oApi._fnAddData( oSettings, aData[i] );
            }

            oSettings.aiDisplay = oSettings.aiDisplayMaster.slice();

            that.fnDraw ();

            if ( bStandingRedraw === true )
            {
                //alert("sdraw");
                oSettings._iDisplayStart = iStart;
                that.oApi._fnCalculateEnd( oSettings );
                that.fnDraw (false);
                $('.dataTables_scrollBody').scrollTop(pos);
            }

            that.oApi._fnProcessingDisplay( oSettings, false );

            /* Callback user function - for event handlers etc */
            if ( typeof fnCallback == 'function' && fnCallback !== null )
            {
                fnCallback( oSettings );
            }
        }, oSettings );

    };

    //Возвращает cookie если есть или undefined
    function getCookie(name) {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ))
        return matches ? decodeURIComponent(matches[1]) : undefined
    }

    //Уcтанавливает cookie
    function setCookie(name, value, props) {
        props = props || {}
        var exp = props.expires
        if (typeof exp == "number" && exp) {
            var d = new Date()
            d.setTime(d.getTime() + exp*1000)
            exp = props.expires = d
        }
        if(exp && exp.toUTCString) { props.expires = exp.toUTCString() }
        value = encodeURIComponent(value)
        var updatedCookie = name + "=" + value
        for(var propName in props) {
            updatedCookie += "; " + propName
            var propValue = props[propName]
            if(propValue !== true){ updatedCookie += "=" + propValue }
        }
        document.cookie = updatedCookie
    }

    //Удаляет cookie
    function deleteCookie(name) {
        setCookie(name, null, { expires: -1 })
    }
});