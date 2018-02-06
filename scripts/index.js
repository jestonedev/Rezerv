/**
 * Created with JetBrains PhpStorm.
 * User: IgnVV
 * Date: 11.01.13
 * Time: 10:31
 * To change this template use File | Settings | File Templates.
 */

// Когда я начинал это писать, только Бог и я понимали, что я делаю
// Сейчас остался только Бог

//Если счетчик форсированного обновления перевалит за 10, значит пользователь не работал со страницей уже 10 минут и надо сбросить AllowRefreshCounter
var forceRefreshCounter = 0;
//Указывает, разрешено ли обновление таблицы с данными (разрешено, только если значение равно 0)
var allowRefreshCounter = 0;
//Указывает, разрешено ли обновление календаря, разрешено, если 1, запрещено, если 0
var allowRefreshCalendar = 0;
//Параметры для ajax-запросов на сервер
var menu_code, report_id, start_date, end_date, date_id, car_id, fuel_type_id, department, only_my_requests;
//Заголовок
var header = "";

var datePickerSettings = {
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
};

var Now = new Date();
var dateTimePickerSettings = {
    monthNames: [ "Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь" ],
    dayNames:	["Воскресенье","Понедельник","Вторник","Среда","Четверг","Пятница","Суббота"],
    dayNamesMin:["Вс","Пн","Вт","Ср","Чт","Пт","Сб"],
    buttonImageOnly: true,
    buttonImage: "./img/SelCalendar.gif",
    buttonText: "Календарь",
    showOn: "button",
    dateFormat:"dd.mm.yy",
    firstDay: 1,
    defaultDate: Now,
    timeFormat: "HH:mm",
    currentText: "Сейчас",
    closeText: "Принять"
};

//Функция инициализации состояния кнопок главной формы
function initButtonsState()
{
    if (menu_code == 1)
    {
        document.getElementById('btnReports').checked = true;
        $('#ShowMyRequestsOnly').hide();
        $('#ShowMyRequestsOnlyLabel').hide();
        $('#btnCreateRequest').hide();
        $('#btnCreateAct').hide();
        $('#btnCreateWaybill').hide();
        $('#btnShowCalendar').hide();
        $('#btnShowGantt').hide();
    } else
    if (menu_code == 2)
    {
        document.getElementById('btnCarsInfo').checked = true;
        $('#ShowMyRequestsOnly').hide();
        $('#ShowMyRequestsOnlyLabel').hide();
        $('#btnCreateRequest').hide();
        $('#btnCreateAct').hide();
        $('#btnCreateWaybill').hide();
        $('#btnShowCalendar').hide();
        $('#btnShowGantt').hide();
        header = "Информация по пробегу транспортных средств администрации";
        setCookie("header",header);
    } else
    if (menu_code == 3)
    {
        document.getElementById('btnRepairActs').checked = true;
        $('#ShowMyRequestsOnly').hide();
        $('#ShowMyRequestsOnlyLabel').hide();
        $('#btnCreateRequest').hide();
        $('#btnCreateWaybill').hide();
        if (hasCreateActsPrivilege())
        {
            $('#btnCreateAct').show();
        } else
        {
            $('#btnCreateAct').hide();
        }
        $('#btnShowCalendar').hide();
        $('#btnShowGantt').hide();
        header = "Акты выполненных работ по обслуживанию автотранспорта";
        setCookie("header",header);
    } else
    if (menu_code == 4)
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
        $('#btnShowGantt').hide();
        header = "Путевые листы";
        setCookie("header",header);
    }
    else
    {
        switch(id_request.toString())
        {
            case "0":
                header = 'Вид группы заявок не указан';
                setCookie('header',header);
                break;
            case "1":
                document.getElementById('btnTransportRequests').checked = true;
                header = 'Заявки на транспорт';
                setCookie('header',header);
                break;
            case "2":
                document.getElementById('btnGreatHallRequests').checked = true;
                header = 'Заявки на конферец-зал';
                setCookie('header',header);
                break;
            case "3":
                document.getElementById('btnSmallHallRequests').checked = true;
                header = 'Заявки на зал заседания думы';
                setCookie('header',header);
                break;
        }
        $('#btnCreateAct').hide();
        $('#btnCreateWaybill').hide();
        $('#ShowMyRequestsOnly').show();
        $('#ShowMyRequestsOnlyLabel').show();
        if (hasCreateRequestPrivilege()) {
            $('#btnCreateRequest').show(); }
        else {
            $('#btnCreateRequest').hide(); }
        $('#btnShowCalendar').show();
        $('#btnShowGantt').show();

        if (only_my_requests == 1)
        {
            document.getElementById('ShowMyRequestsOnly').checked = true;
        }
    }
    $("#requests_group").buttonset();
    $("#header").text(header);
}

//Начальная инициализация таблицы с данными
function initDataTable()
{
    var ex = document.getElementById('example');
    if ( $.fn.DataTable.fnIsDataTable( ex ) ) {
        $(ex).dataTable().api().clear();
        $(ex).dataTable().api().destroy();
    }
    initColumns();
    var oTable = $('#example').dataTable( {
        "bProcessing": true,
        "bStateSave": true,
        "bDeferRender": true,
        "bServerSide": false,
        "bJQueryUI": true,
        "scrollY": $(window).height() - 290,
        "bFilter": true,
        "iDisplayLength": 25,
        "destroy": $.fn.DataTable.fnIsDataTable( ex ),
        "sAjaxSource": "inc/jsonp.php",
        "fnServerParams": function ( aoData ) {
            aoData.push( { "name": "menu_code", "value": menu_code } );
            if (menu_code == 1)
            {
                aoData.push( { "name": "report_id", "value": report_id } );
                aoData.push( { "name": "start_date", "value": start_date } );
                aoData.push( { "name": "end_date", "value": end_date } );
                aoData.push( { "name": "date_id", "value": date_id } );
                aoData.push( { "name": "car_id", "value": car_id } );
                aoData.push( { "name": "fuel_type_id", "value": fuel_type_id } );
                aoData.push( { "name": "department", "value": department } );
            } else
            if (menu_code == 0)
            {
                aoData.push( { "name": "only_my_requests", "value": only_my_requests } );
                aoData.push( { "name": "id_request", "value": id_request } );
            }
        },
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
            if (menu_code != 1) {
                return; }
            var rep_cfg = "";
            var i = 0, j = 0;
            for (i = 0; i < rep_with_summary.length; i++)
            {
                if (rep_with_summary[i][0] == report_id)
                {
                    rep_cfg = rep_with_summary[i];
                    break;
                }
                if (i == (rep_with_summary.length - 1)) {
                    return; }
            }
            var sum_array = [rep_cfg[2]];
            for (i = 0; i < rep_cfg[2]; i++) {
                sum_array[i] = 0; }
            for (i=0 ; i<aaData.length ; i++ ) {
                for (j = 0; j < rep_cfg[2]; j++) {
                    if (rep_cfg[0] == 46 && aaData[i][0] == "Администрация (всего)")
                    {
                        continue;
                    }
                    sum_array[j] += aaData[i][rep_cfg[1]+j]*Number(1); }
            }
            var nCells = nRow.getElementsByTagName('th');
            nCells[0].innerHTML = 'Итого';
            for (i = 1; i < nCells.length; i++) {
                nCells[i].innerHTML = '-'; }
            for (i = 0; i < rep_cfg[2]; i++) {
                nCells[rep_cfg[1]+i].innerHTML = Math.round(sum_array[i]); }
        }
    } );

    $("#example_length").first().append('<input type="checkbox" id="ShowMyRequestsOnly"><label id="ShowMyRequestsOnlyLabel" for="ShowMyRequestsOnly">Только мои заявки</label>');
    //Обработчик событий нажатия на кнопку "Мои заявки"
    $('#ShowMyRequestsOnly').click(function()
    {
        if ($(this).prop("checked")) {
            only_my_requests = 1;
            setCookie("only_my_requests",1);
        }
        else {
            only_my_requests = 0;
            setCookie("only_my_requests",0); }
        $('#example').dataTable().api().ajax.reload();
        initButtonsState();
    });
}

//Функция инициализации колонок DataTable
function initColumns()
{
    var data = "action=init_columns&menu_code="+menu_code;
    if (menu_code == 1)
    {
        data += "&report_id=" + report_id;
        data += "&start_date=" + start_date;
        data += "&end_date=" + end_date;
        data += "&date_id=" + date_id;
        data += "&car_id=" + car_id;
        data += "&fuel_type_id=" + fuel_type_id;
        data += "&department=" + department;
    }
    $.ajax({
        type: "POST",
        url: "inc/jsonp.php",
        data: data,
        async: false,
        success: function(msg) {
            $("#example thead tr").remove();
            $("#example tfoot tr").remove();
            var headers = JSON.parse(msg);
            $("#example thead").append(headers["head"]);
            $("#example tfoot").append(headers["foot"]);
            if (menu_code == 1)
            {
                var rep_id = report_id;
                $("#example tfoot").hide();
                var i = 0;
                for (i = 0; i < rep_with_summary.length; i++)
                {
                    if (rep_id == rep_with_summary[i][0]) {
                        $("#example tfoot").show(); }
                }
            }
        },
        error: function(msg)
        {
        }
    });
}

function dateToString(date)
{
    var year, month, day, hour, minute;
    year = date.getFullYear();
    month = date.getMonth() + 1;
    if (month < 10) {
        month = '0'+month; }
    day = date.getDate();
    if (day < 10) {
        day = '0'+day; }
    return year+'-'+month+'-'+day+' '+'00:00';
}

//Возвращает cookie если есть или undefined
function getCookie(name) {
    var matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+\^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

//Уcтанавливает cookie
function setCookie(name, value, props) {
    props = props || {};
    var exp = props.expires;
    if (typeof exp == "number" && exp) {
        var d = new Date();
        d.setTime(d.getTime() + exp*1000);
        exp = props.expires = d;
    }
    if(exp && exp.toUTCString) { props.expires = exp.toUTCString(); }
    value = encodeURIComponent(value);
    var updatedCookie = name + "=" + value;
    var propName = null;
    for(propName in props) {
        updatedCookie += "; " + propName;
        var propValue = props[propName];
        if(propValue !== true){ updatedCookie += "=" + propValue; }
    }
    document.cookie = updatedCookie;
}

////////////////////////////////////////////
//Функции проверки состояния форм запросов//
////////////////////////////////////////////

function intCorrect(int_val)
{
    var reg = /^[0-9]+$/i;
    return reg.test(int_val);
}

function floatCorrect(float_val)
{
    var reg = /^[0-9]+[.]{0,1}[0-9]{0,3}$/i;
    return reg.test(float_val);
}

function timeCorrect(time)
{
    var reg = /^([0-1][0-9]|[2][0-3])(:([0-5][0-9])){1,2}$/i;
    return reg.test(time);
}

function dateCorrect(date)
{
    var date_params = date.split('.');
    var dateObj = new Date(date_params[2], date_params[1] - 1, date_params[0]);
    return ((date_params[2] == dateObj.getFullYear()) &&
    (date_params[1] == dateObj.getMonth()+1) &&
    (date_params[0] == dateObj.getDate()));
}

/////////////////////
//Служебные функции//
/////////////////////

//Функция конвертации представления даты из формата MySQL в формат формы данных
function convert_date(date_param)
{
    var date_str = date_param.split(' ')[0];
    var date_arr = date_str.split('-');
    var date = new Date(date_arr[0],date_arr[1] - 1,date_arr[2]);
    var day = date.getDate()<10?"0"+date.getDate():date.getDate();
    var month = (date.getMonth()+1)<10?"0"+(date.getMonth()+1):(date.getMonth()+1);
    var year = date.getFullYear();
    return day+"."+month+"."+year;
}

//Функция конвертации представления даты и времени из формата MySQL в формат формы данных
function convert_datetime(datetime_param)
{
    var date_str = datetime_param.split(' ')[0];
    var time_str = datetime_param.split(' ')[1];
    var date_arr = date_str.split('-');
    var time_arr = time_str.split(':');
    var date = new Date(date_arr[0],date_arr[1] - 1,date_arr[2],time_arr[0], time_arr[1]);
    var day = date.getDate()<10?"0"+date.getDate():date.getDate();
    var month = (date.getMonth()+1)<10?"0"+(date.getMonth()+1):(date.getMonth()+1);
    var year = date.getFullYear();
    var hours = date.getHours()<10?"0"+date.getHours():date.getHours();
    var minutes = date.getMinutes()<10?"0"+date.getMinutes():date.getMinutes();
    return day+"."+month+"."+year+" "+hours+":"+minutes;
}

//Добавление прототипа функции вхождения в массив
String.prototype.inList=function(list){
    var i = 0;
    for(i in list){
        if(this==list[i]){
            return true;
        }
    }
    return false;
};

Number.prototype.inList=String.prototype.inList;

$(document).ready(function(){
    "use strict";

    initServerParams();

    $("#header").text(header);

    //Инициализация компонентов
    $( "input[type=submit], input[type=reset], a, button" ).button();


    initVariables();
    initDataTable();
    initButtonsState();
    initAutoRefresh();

    ///////////////////////////////////
    //Назначение обработчиков событий//
    ///////////////////////////////////

    //Обработчик события нажатия на кнопку "Подробнее"
    $('#example tbody').on('click', 'img', function () {
        //Если это пробег
        if (menu_code == 2)
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
            if (menu_code == 3) {
                show_act_details(oTable, nTr); }
            else
            //Если это путевые листы
            if (menu_code == 4) {
                show_waybill_details(oTable, nTr); }
            else {
            //Если это заявки
                show_request_details(oTable, nTr); }
        }
    });

    /////////////////////////
    //Функции инициализации//
    /////////////////////////

    //Установка Cookie и настройка начального представления формы
    function initServerParams() {
        if (!getCookie("id_request")) {
            id_request = 1;
            setCookie("id_request", 1);
        } else {
            id_request = getCookie("id_request");
        }
        if (!getCookie("menu_code")) {
            menu_code = 0;
            report_id = 1;
            setCookie("menu_code", 0);
            setCookie("report_id", 1);
        }
        else {
            if (!getCookie("report_id")) {
                report_id = 1;
                setCookie("report_id", 1);
            } else {
                report_id = getCookie("report_id");
            }
            menu_code = getCookie("menu_code");
        }
        if (!getCookie("only_my_requests")) {
            only_my_requests = 0;
            setCookie("only_my_requests", 0);
        } else {
            only_my_requests = getCookie("only_my_requests");
        }
        if (!getCookie("header")) {
            header = "Заявки на транспорт";
            setCookie("header", header);
        } else {
            header = getCookie("header");
        }
        if (getCookie("start_date")) {
            start_date = getCookie("start_date");
        }
        if (getCookie("end_date")) {
            end_date = getCookie("end_date");
        }
        if (getCookie("date_id")) {
            date_id = getCookie("date_id");
        }
        if (getCookie("car_id")) {
            car_id = getCookie("car_id");
        }
        if (getCookie("fuel_type_id")) {
            fuel_type_id = getCookie("fuel_type_id");
        }
        if (getCookie("department")) {
            department = getCookie("department");
        }
        if (getCookie("only_my_requests")) {
            only_my_requests = getCookie("only_my_requests");
        }
    }

    //Функция инициализация состояния внутренних переменных
    function initVariables()
    {
        $.ajax({
            type: "POST",
            url: "inc/req_permissions.php",
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
                if ((msg & 2) == 2) {
                    $('#rqlb3').show(); }
                else
                {
                    req_mask -= 2;
                    $('#rqlb3').hide();
                }
                if ((msg & 4) == 4) {
                    $('#rqlb2').show(); }
                else
                {
                    req_mask -= 4;
                    $('#rqlb2').hide();
                }
                if ((msg & 8) == 8) {
                    $('#rqlb4').show(); }
                else
                {
                    req_mask -= 8;
                    $('#rqlb4').hide();
                }
                if ((msg & 16) == 16) {
                    $('#rqlb5').show(); }
                else
                {
                    req_mask -= 16;
                    $('#rqlb5').hide();
                }
                if ((msg & 32) == 32) {
                    $('#rqlb6').show(); }
                else
                {
                    req_mask -= 32;
                    $('#rqlb6').hide();
                }
                if (((req_mask & 1) != 1) && (id_request == 1))
                {
                    setCookie('id_request',2);
                    id_request = 2;
                }
                if (((req_mask & 4) != 4) && (id_request == 2))
                {
                    setCookie('id_request',3);
                    id_request = 3;
                }
                if (((req_mask & 2) != 2) && (id_request == 3))
                {
                    setCookie('menu_code',2);
                    menu_code = 2;
                }
                if (((req_mask & 8) != 8) && (menu_code == 2))
                {
                    setCookie('menu_code',3);
                    menu_code = 3;
                }
                if (((req_mask & 16) != 16) && (menu_code == 3))
                {
                    setCookie('menu_code',4);
                    menu_code = 4;
                }
                if (((req_mask & 32) != 32) && (menu_code == 4))
                {
                    setCookie('menu_code',0);
                    menu_code = 0;
                    setCookie('id_request',0);
                    id_request = 0;
                }
            },
            error: function(msg)
            {
            }
        });
    }

    $(window).resize(
        function() {
            $('.dataTables_scrollBody').css('height', ($(window).height() - 290));
            var table = $('#example').DataTable();
            table.columns.adjust().draw();
        }
    );

    //Функция автообновления таблицы по таймауту
    function initAutoRefresh()
    {
        setTimeout(AutoRefresh, 1000*60); //Обновлять таблицу раз в 1 минуту              // changed by ax
    }

    function AutoRefresh()
    {
       var refresh_time=1000*60;
	   if (allowRefreshCounter == 0)
        {
			$('#example').dataTable().api().ajax.reload();
		    setTimeout(AutoRefresh, refresh_time); //Обновлять таблицу раз в 1 минуту          
        } else
        {
            if (forceRefreshCounter == 10)      //Принудительно обновляем таблицу после 10 минут бездействия
            {
                forceRefreshCounter = 0;
                allowRefreshCounter = 0;
				$('#example').dataTable().api().ajax.reload();
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
});