$(document).ready(function() {
    "use strict";

    initActCreateForm();

    //Обработчик события нажатия на кнопку "Акты обслуживания"
    $('#btnRepairActs').button().click(function()
    {
        if (menu_code != 3)
        {
            setCookie("menu_code", 3);
            menu_code = 3;
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
    $('#btnCreateAct').button().click(function() {
        $("#error_act_create").hide();
        var actCreateForm = $("#act_create_form");
        actCreateForm.find("#act_number").prop("value","");
        actCreateForm.find("#act_date").prop("value","");
        actCreateForm.find("#reason_for_repair").prop("value","");
        actCreateForm.find("#work_performed").prop("value","");
        actCreateForm.find("#act_odometer").prop("value","");
        actCreateForm.find("#act_wait_start_date").prop("value","");
        actCreateForm.find("#act_wait_end_date").prop("value","");
        actCreateForm.find("#act_repair_start_date").prop("value","");
        actCreateForm.find("#act_repair_end_date").prop("value","");
        $("#act_expended_list option").remove();
        actCreateForm.dialog({
                autoOpen: true,
                modal: true,
                title: 'Создать акт',
                width: $(window).width()/1.3,
                height: "auto",
                resizable: false,
                close: function() {
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

    //Функция инициализации формы создания акта выполненных работ
    function initActCreateForm()
    {
        $("#act_date").inputmask("99.99.9999");
        $("#act_wait_start_date").inputmask("99.99.9999 99:99");
        $("#act_wait_end_date").inputmask("99.99.9999 99:99");
        $("#act_repair_start_date").inputmask("99.99.9999 99:99");
        $("#act_repair_end_date").inputmask("99.99.9999 99:99");
        $("#act_date").datepicker(datePickerSettings);
        $("#act_wait_start_date").datetimepicker(dateTimePickerSettings);
        $("#act_wait_end_date").datetimepicker(dateTimePickerSettings);
        $("#act_repair_start_date").datetimepicker(dateTimePickerSettings);
        $("#act_repair_end_date").datetimepicker(dateTimePickerSettings);

        $("#insert_expended, #delete_expended").button();
        $.ajax( {
                type: "POST",
                url: "inc/cars_list.php",
                success: function(msg)
                {
                    $('#act_car select').remove();
                    $('#act_car').append(msg);
                },
                error: function(msg)
                {
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/respondents_list.php",
                success: function(msg)
                {
                    $('#act_respondent select').remove();
                    $('#act_respondent').append(msg);
                },
                error: function(msg)
                {
                }
            }
        );
        $.ajax( {
                type: "POST",
                url: "inc/chiefs_list.php",
                success: function(msg)
                {
                    $('#car_chief_wrapper select').remove();
                    $('#car_chief_wrapper').append(msg);
                },
                error: function(msg)
                {
                }
            }
        );

        $.ajax({
                type: "POST",
                url: "inc/mechanics_list.php",
                success: function(msg)
                {
                    $('#act_mechanic select').remove();
                    $('#act_mechanic').append(msg);
                },
                error: function(msg)
                {
                }
        });
        $("#insert_expended").click(function() {
            $("#error_add_expended").hide();
            $("#act_expended_edit_name").prop("value","");
            $("#act_expended_edit_count").prop("value","");
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
                            var expended_name = $("#act_expended_edit_name").prop("value");
                            var expended_count = $("#act_expended_edit_count").prop("value");
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
            }).height('auto');
        });
        $("#delete_expended").click(function() {
            $("#act_expended_list option:selected").remove();
        });
    }
});

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

//Создание/изменение акта выполненных работ
function ProcessAct(id_repair)
{
    var form = $('#act_create_form');

    var act_number = form.find("#act_number").prop("value");
    var act_date = form.find("#act_date").prop("value");
    var respondent_id = form.find("select[name='act_respondent_id']").prop("value");
    var car_id = form.find("select[name='car_id']").prop("value");
    var driver_id = form.find("select[name='driver_id']").prop("value");
    var mechanic_id = form.find("select[name='mechanic_id']").prop("value");
    var reason_for_repair = form.find("#reason_for_repair").prop("value");
    var work_performed = form.find("#work_performed").prop("value");
    var act_odometer = form.find("#act_odometer").prop("value");
    var act_wait_start_date = form.find("#act_wait_start_date").prop("value");
    var act_wait_end_date = form.find("#act_wait_end_date").prop("value");
    var act_repair_start_date = form.find("#act_repair_start_date").prop("value");
    var act_repair_end_date = form.find("#act_repair_end_date").prop("value");
    var self_repair = form.find("#self_repair").prop("checked");

    var div = null;
    if (($.trim(act_date) == "") || ($.trim(respondent_id) == "") ||
        ($.trim(car_id) == "") || ($.trim(driver_id) == "") ||
        ($.trim(mechanic_id) == ""))
    {
        div = document.getElementById("error_act_create");
        div.innerHTML = "Не все обязательные поля заполнены";
        $("#error_act_create").show();
        return;
    }
    if (!dateCorrect(act_date))
    {
        div = document.getElementById("error_act_create");
        div.innerHTML = "Дата создания акта заполнена некорректно";
        $("#error_act_create").show();
        return;
    }
    if (($.trim(act_odometer) != "") && (!intCorrect(act_odometer)))
    {
        div = document.getElementById("error_act_create");
        div.innerHTML = "Показание одометра заполнено некорректно";
        $("#error_act_create").show();
        return;
    }

    if (($.trim(act_wait_start_date) != "") && ($.trim(act_wait_start_date) != "__.__.____ __:__") &&
        ((!dateCorrect(act_wait_start_date.split(' ')[0])) || (!timeCorrect(act_wait_start_date.split(' ')[1]))))
    {
        div = document.getElementById("error_act_create");
        div.innerHTML = "Начальная дата ожидания ремонта заполнена некорректно";
        $("#error_act_create").show();
        return;
    }
    if ($.trim(act_wait_start_date) == "__.__.____ __:__")
    {
        act_wait_start_date = "";
    }

    if (($.trim(act_wait_end_date) != "") && ($.trim(act_wait_end_date) != "__.__.____ __:__") &&
        ((!dateCorrect(act_wait_end_date.split(' ')[0])) || (!timeCorrect(act_wait_end_date.split(' ')[1]))))
    {
        div = document.getElementById("error_act_create");
        div.innerHTML = "Конечная дата ожидания ремонта заполнена некорректно";
        $("#error_act_create").show();
        return;
    }
    if ($.trim(act_wait_end_date) == "__.__.____ __:__")
    {
        act_wait_end_date = "";
    }

    if (($.trim(act_repair_start_date) != "") && ($.trim(act_repair_start_date) != "__.__.____ __:__") &&
        ((!dateCorrect(act_repair_start_date.split(' ')[0])) || (!timeCorrect(act_repair_start_date.split(' ')[1]))))
    {
        div = document.getElementById("error_act_create");
        div.innerHTML = "Начальная дата фактического ремонта заполнена некорректно";
        $("#error_act_create").show();
        return;
    }
    if ($.trim(act_repair_start_date) == "__.__.____ __:__")
    {
        act_repair_start_date = "";
    }

    if (($.trim(act_repair_end_date) != "") && ($.trim(act_repair_end_date) != "__.__.____ __:__") &&
        ((!dateCorrect(act_repair_end_date.split(' ')[0])) || (!timeCorrect(act_repair_end_date.split(' ')[1]))))
    {
        div = document.getElementById("error_act_create");
        div.innerHTML = "Конечная дата фактического ремонта заполнена некорректно";
        $("#error_act_create").show();
        return;
    }
    if ($.trim(act_repair_end_date) == "__.__.____ __:__")
    {
        act_repair_end_date = "";
    }

    var expended_list = "";
    $("#act_expended_list option").each(function() {
        expended_list += $(this).prop("value")+"@@";
    });
    var action = "";
    if (id_repair == 0) {
        action = "action=insert_act"; }
    else {
        action = "action=update_act&repair_id="+id_repair; }
    var data = action+"&act_number="+act_number+"&act_date="+act_date+"&responded_id="+respondent_id+"&car_id="+car_id+
        "&driver_id="+driver_id+"&mechanic_id="+mechanic_id+"&reason_for_repair="+reason_for_repair+
        "&work_performed="+work_performed+"&act_odometer="+act_odometer+"&act_wait_start_date="+act_wait_start_date+
        "&act_wait_end_date="+act_wait_end_date+"&act_repair_start_date="+act_repair_start_date+
        "&act_repair_end_date="+act_repair_end_date+"&expended_list="+expended_list+"&self_repair="+self_repair;
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
                $( '#act_create_form' ).dialog( "close" );
                $('#example').dataTable().api().ajax.reload();
            } else
            {
                var div = document.getElementById("error_act_create");
                div.innerHTML = msg;
                $("#error_act_create").show();
            }
        },
        error: function(msg)
        {
        }
    });
}

function show_act_details(oTable, nTr)
{
    oTable.fnOpen( nTr, fnFormatActDetails(oTable, nTr), 'details' );
    allowRefreshCounter += 1;
    $(".btnModifyAct, .btnDeleteAct, .btnReportByAct").button();
    $(".btnModifyAct, .btnDeleteAct, .btnReportByAct").button().unbind('click');
    $(".btnDeleteAct").button().click( function() {
            var id_repair = $(this).prop("value");
            if (!confirm('Вы действительно хотите удалить акт?'))
            {
                return;
            }
            $.ajax( {
                    type: "POST",
                    url: "inc/acts_modify.php",
                    data: "action=delete_act&id_repair="+id_repair,
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
    $('.btnModifyAct').button().click(function() {
        var id_repair = $(this).prop("value");
        $.ajax( {
                type: "POST",
                url: "inc/acts_modify.php",
                data: "action=get_act_info&id_repair="+id_repair,
                async: false,
                success: function(msg)
                {
                    var info = JSON.parse(msg);
                    var form = $('#act_create_form');
                    form.find("#act_number").prop("value", info["repair_act_number"]);
                    form.find("#act_date").prop("value", convert_date(info["act_date"]));
                    if (info["wait_start_date"]) {
                        form.find("#act_wait_start_date").prop("value", convert_datetime(info["wait_start_date"])); }
                    else {
                        form.find("#act_wait_start_date").prop("value", ""); }
                    if (info["wait_end_date"]) {
                        form.find("#act_wait_end_date").prop("value", convert_datetime(info["wait_end_date"])); }
                    else {
                        form.find("#act_wait_end_date").prop("value", ""); }
                    if (info["repair_start_date"]) {
                        form.find("#act_repair_start_date").prop("value", convert_datetime(info["repair_start_date"])); }
                    else {
                        form.find("#act_repair_start_date").prop("value", ""); }
                    if (info["repair_end_date"]) {
                        form.find("#act_repair_end_date").prop("value", convert_datetime(info["repair_end_date"])); }
                    else {
                        form.find("#act_repair_end_date").prop("value", ""); }
                    form.find("select[name='act_respondent_id']").prop("value",info["id_respondent"]);
                    form.find("select[name='car_id']").prop("value", info["id_car"]);
                    form.find("select[name='driver_id']").prop("value", info["id_driver"]);
                    form.find("select[name='mechanic_id']").prop("value", info["id_mechanic"]);
                    form.find("#reason_for_repair").prop("value", info["reason_for_repairs"]);
                    form.find("#work_performed").prop("value", info["work_performed"]);
                    form.find("#act_odometer").prop("value", info["odometer"]);
                    form.find("#self_repair").prop("checked", info["self_repair"] === "1");
                    var expended_array = info["expended"];
                    $("#act_expended_list option").remove();
                    var i = 0;
                    for (i = 0; i < expended_array.length; i++)
                    {
                        var expended_material = expended_array[i]["material"];
                        var expended_count = expended_array[i]["count"];
                        $("#act_expended_list").append("<option value='"+expended_material+"@"+expended_count+"'>"+expended_material+" - "
                            +expended_count+"</option>");
                    }
                    $("#error_act_create").hide();
                    form.dialog( {
                            autoOpen: true,
                            modal: true,
                            title: 'Изменить акт №'+info["repair_act_number"],
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
                },
                error: function(msg)
                {
                }
            }
        );
    });
    $('.btnReportByAct').button().click(function() {
        var id_repair = $(this).prop("value");
        $.fileDownload('inc/acts_report.php?id_repair='+id_repair,
            {failMessageHtml: "Не удалось загрузить файл, попробуйте еще раз."});
        return false;
    });
}

//Функция инициализации формы детальной информации об акте выполненных работ
function fnFormatActDetails ( oTable, nTr )
{
    var msga = "";
    var aData = oTable.fnGetData( nTr );
    var id_repair = $(aData[0]).attr('value');
    $.ajax({
        type: "POST",
        url: "inc/details_by_id.php",
        data: 'action=act&id_repair='+id_repair,
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

//Есть привелегия на создание актов обслуживания
function hasCreateActsPrivilege()
{
    var hasCRP = false;
    $.ajax({
        type: "POST",
        url: "inc/can_create_acts.php",
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