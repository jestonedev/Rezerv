$(document).ready(function() {
    "use strict";

    initCalendar();

    //Обработчик события нажатия на кнопку календаря
    $("#btnShowCalendar").click(function () {
        var has_form_data = false;
        $.ajax({
            type: "POST",
            url: "processor.php",
            data: "action=display_calendar_settings_form",
            async: false,
            success: function(msg)
            {
                $('#calendarSettings').html(msg);
                has_form_data = true;
            },
            error: function(msg)
            {
            }
        });
        if (!has_form_data) {
            return;
        }
        $('#calendarSettings').dialog({
            autoOpen: true,
            modal: true,
            title: "Настройки отображаемой информации",
            width: $(window).width()/1.5,
            resizable: false,
            buttons: [
                {
                    text: "Принять",
                    click: function() {
                        var checked = false;
                        $('#calendarSettings input[type=checkbox]').each( function() {
                            if ($(this).prop("checked")) {
                                checked = true; }
                        });
                        if (!checked)
                        {
                            alert('Необходимо выбрать хотя бы один статус заявки');
                            return;
                        }
                        $( this ).dialog( "close" );
                        var title = "";
                        if (id_request == 1) {
                            title = "Календарь заявок на транспорт"; }
                        else if (id_request == 2) {
                            title = "Календарь заявок на большой зал"; }
                        else if (id_request == 3) {
                            title = "Календарь заявок на малый зал"; }
                        $('#calendar').dialog({
                            autoOpen: true,
                            modal: true,
                            title: title,
                            width: "auto",
                            height: $(window).height() - 10,
                            close: function(event, ui) {
                                allowRefreshCalendar = 0;
                            },
                            buttons: [{text: "Закрыть",
                                click: function() { $( this ).dialog( "close" ); }}]
                        });
                        $('#calendar').dialog("option","position",{ at: "center center" });
                        $('#calendar').fullCalendar("option", "height",
                            $('#calendar').dialog("option","height") - 150);
                        $('#calendar').fullCalendar( "refetchEvents" );
                        $('#calendar').fullCalendar( "rerenderEvents" );
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
                month: 'MMMM YYYY',
                day: 'dddd, DD MMM, YYYY' },
            buttonText: {
                today:    'Сегодня',
                month:    'Месяц',
                week:     'Неделя',
                day:      'День'
            },
            firstDay: 1,
            firstHour: 7,
            axisFormat: "HH:mm",
            allDaySlot: false,
            cache: true,
            timeFormat: 'H:mm',
            theme: true,
            events: function(start, end, timezone, callback) {
                //Формируем данные для фильтрации
                var department = $('#calendarSettings select[name=department]').prop("value");
                if (department == undefined) {
                    return; }
                if (id_request == 1) {
                    var transport = $('#calendarSettings select[name=car_id]').prop("value"); }
                var requestStates = [];
                $("#calendarSettings .requestState").each(
                    function() {
                        if ($(this).prop("checked")) {
                            requestStates.push($(this).prop("value")); }
                    }
                );
                //Преобразуем полученные данные в строку запроса
                var paramstr = "";
                var startStr = dateToString(start._d);
                var endStr = dateToString(end._d);
                var i = 0;
                for (i = 0; i < requestStates.length; i++) {
                    paramstr += 'requestStates[]='+requestStates[i]+'&'; }
                if (id_request == 1) {
                    paramstr += 'transport='+transport+'&'; }
                paramstr += 'department='+department+'&start='+startStr+'&end='+endStr;
                $.ajax({
                    url: "inc/calendar_source.php",
                    type: "POST",
                    data: paramstr,
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
                    data: 'action=request&id_request_number='+id_request_number+'&from_calendar=1',
                    async: false,
                    success: function(msg)
                    {
                        msge = msg;
                        has_msge = true;
                    },
                    error: function(msg)
                    {
                    }
                });
                if (!has_msge) {
                    return; }
                $('#calendar_details table').remove();
                $('#calendar_details').append(msge);
                $('#calendar_details').prop('title',calEvent.title);
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
                if ((view != undefined) && ((view.name == 'month') || (view.name == 'agendaWeek')))
                {
                    $("#calendar").fullCalendar('gotoDate',date.getFullYear(), date.getMonth(), date.getDate());
                    $("#calendar").fullCalendar('changeView','agendaDay');
                }
            }
        });
    }
});