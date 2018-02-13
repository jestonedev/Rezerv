//Есть привелегия на просмотр транспорта
function hasManageCarsPrivilege()
{
    var hasCRP = false;
    $.ajax({
        type: "POST",
        url: "inc/can_manage_cars.php",
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

$(document).ready(
    function() {
        $("body").on("click", ".select-date-button",
            function() {
                $(this).closest(".date.input-group").find("input").datepicker("show");
            });

        if ($.fn.datepicker != undefined) {
            $("#waybillStartDate, #waybillEndDate").datepicker({
                format: "dd.mm.yyyy",
                weekStart: 1,
                maxViewMode: 2,
                todayBtn: "linked",
                language: "ru",
                orientation: "bottom auto",
                autoclose: true,
                todayHighlight: true,
                startDate: "01/01/1753"
            });
        }

        $("#waybill-add-button").on("click", function(e) {
            e.preventDefault();
            InsertWaybill();
        });

        $("#waybill-edit-button").on("click", function(e) {
            e.preventDefault();
            UpdateWaybill($("#waybillId").prop("value"));
        });

        $(".waybill-delete-btn").on("click", function(e) {
            var idWaybill = $(this).data("id-waybill");
            var waybillNumber = $(this).data("waybill-number");
            $("#delete-waybill .waybill-number").text(waybillNumber);
            $("#delete-waybill-success").data("id-waybill", idWaybill);
            $("#delete-waybill").modal('show');
            e.preventDefault();
        });

        $("#delete-waybill-success").on("click", function(e) {
            $.ajax({
                    type: "POST",
                    url: "inc/waybills_modify.php",
                    data: "action=delete_waybill&id_waybill=" + $(this).data("id-waybill"),
                    success: function () {
                        $("#delete-waybill").modal('hide');
                        location.href="";
                    },
                    error: function (msg) {
                        $("#delete-waybill").modal('hide');
                        location.href=""
                    }
                }
            );
        });

        function ProcessWaybill(id_waybill)
        {
            var alert = $("#waybill-error");
            alert.empty().hide();
            var waybill_number = $("#waybillNumber").prop("value");
            var waybill_start_date = $("#waybillStartDate").prop("value");
            var waybill_end_date = $("#waybillEndDate").prop("value");
            var car_id = $("#waybillCar").prop("value");
            var driver_id = $("#waybillDriver").prop("value");
            var department = $("#waybillDepartment").prop("value");
            var waybill_mileage_before = $("#waybillMileagesBefore").prop("value");
            var waybill_mileages = $("#waybillMileages").prop("value");
            var waybill_fuel_before = $("#waybillFuelBefore").prop("value").replace(",",".");
            var waybill_given_fuel = $("#waybillGivenFuel").prop("value").replace(",",".");
            var fuel_type_id = $("#waybillFuelType").prop("value");
            var ways_list = "";
            var is_correct = true;
            if (($.trim(waybill_start_date) == "") || ($.trim(waybill_end_date) == "") ||
                ($.trim(car_id) == "") || ($.trim(driver_id) == "") || ($.trim(department) == ""))
            {
                alert.append("<div>Не все обязательные поля заполнены</div>").show();
                return;
            }
            if (!dateCorrect(waybill_start_date) || !dateCorrect(waybill_end_date))
            {
                alert.append("<div>Период действия путевого листа задан некорректно</div>");
                is_correct = false;
            }
            if (($.trim(waybill_number) != "") && (!intCorrect(waybill_number)))
            {
                alert.append("<div>Номер путевого листа указан неверно</div>");
                is_correct = false;
            }
            if (($.trim(waybill_mileage_before) != "") && (!intCorrect(waybill_mileage_before)))
            {
                alert.append("<div>Показание спидометра до выезда указано неверно</div>");
                is_correct = false;
            }
            if (($.trim(waybill_mileages) != "") && (!intCorrect(waybill_mileages)))
            {
                alert.append("<div>Пробег указан неверно</div>");
                is_correct = false;
            }
            if (($.trim(waybill_fuel_before) != "") && (!floatCorrect(waybill_fuel_before)))
            {
                alert.append("<div>Остаток топлива при выезде указан неверно</div>");
                is_correct = false;
            }
            if (($.trim(waybill_given_fuel) != "") && (!floatCorrect(waybill_given_fuel)))
            {
                alert.append("<div>Значение выданного топлива указано неверно</div>");
                is_correct = false;
            }
            if (!is_correct)
            {
                alert.show();
                return;
            }
            // TODO: Добавить маршруты
            var array = [];
            $("#ways_list option").each(function() {
                ways_list += $(this).prop("value")+"$";
            });
            var action = "";
            if (id_waybill == 0) {
                action = "action=insert_waybill";
            }
            else {
                action = "action=update_waybill&waybill_id="+id_waybill;
            }
            var data = action+"&number="+waybill_number+"&start_date="+waybill_start_date+"&end_date="+waybill_end_date+"&car_id="+car_id+
                "&driver_id="+driver_id+"&department="+department+
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
                        location.href = "car_waybills.php?id_car="+car_id;
                    } else
                    {
                        alert.append(msg);
                        alert.show();
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

    }
);