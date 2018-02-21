$(document).ready(
    function() {
        if ($.fn.datepicker != undefined) {
            $("#actDate, #repairStartDate, #repairEndDate, #waitStartDate, #waitEndDate").datepicker(carsDatePickerSettings);
        }

        if ($.fn.mask != undefined)
        {
            $("#repairStartTime, #repairEndTime, #waitStartTime, #waitEndTime").mask("00:00");
        }

        $("#repair-act-add-button").on("click", function(e) {
            e.preventDefault();
            InsertRepairAct();
        });

        $("#repair-act-edit-button").on("click", function(e) {
            e.preventDefault();
            UpdateRepairAct($("#repairActId").prop("value"));
        });

        $(".repair-act-delete-btn").on("click", function(e) {
            var idRepair = $(this).data("id-repair");
            var repairActNumber = $(this).data("repair-act-number");
            $("#delete-repair-act .repair-act-number").text(repairActNumber);
            $("#delete-repair-act-success").data("id-repair", idRepair);
            $("#delete-repair-act").modal('show');
            e.preventDefault();
        });

        $("#delete-repair-act-success").on("click", function() {
            $.ajax({
                    type: "POST",
                    url: "inc/acts_modify.php",
                    data: "action=delete_act&id_repair=" + $(this).data("id-repair"),
                    success: function () {
                        $("#delete-repair-act").modal('hide');
                        location.href="";
                    },
                    error: function () {
                        $("#delete-repair-act").modal('hide');
                        location.href=""
                    }
                }
            );
        });

        $(".repair-act-generate-btn").on("click", function() {
            var id_repair = $(this).data("id-repair");
            $.fileDownload('inc/acts_report.php?id_repair='+id_repair,
                {failMessageHtml: "Не удалось загрузить файл, попробуйте еще раз."});
        });

        addMaterial();

        function addMaterial() {
            getMaterialTemplate(function (materialTemplate) {
                var materialsWrapper = $(".repair-act__materials table tbody");
                materialsWrapper.append(materialTemplate);
            });
        }

        $(".repair-act__materials").on("change",
            ".material-name, .material-count, .material-description", function(e) {
            var materials = $(".repair-act__material");
            var hasNoEmptyString = true;
            for(var i = 0; i < materials.length; i++)
            {
                var materialName = $(materials[i]).find(".material-name");
                var materialCount = $(materials[i]).find(".material-count");
                var materialDescription = $(materials[i]).find(".material-description");
                if (materialName.prop("value") == "" &&
                    materialCount.prop("value") == "" &&
                    materialDescription.prop("value") == "")
                {
                    if (hasNoEmptyString)
                    {
                        hasNoEmptyString = false;
                    } else
                    {
                        $(materials[i]).remove();
                    }
                }
            }
            if (hasNoEmptyString)
            {
                addMaterial();
            }
        });

        var materialTemplateCached = undefined;

        function getMaterialTemplate(callback)
        {
            if (materialTemplateCached != undefined)
            {
                callback(materialTemplateCached);
                return;
            }
            $.get("inc/repair_act_material_template.php", function(data) {
                    materialTemplateCached = data;
                    callback(data);
                }
            );
        }

        function ProcessRepairAct(id_repair)
        {
            var alert = $("#repair-act-error");
            alert.empty().hide();
            var repair_act_number = $("#repairActNumber").prop("value");
            var act_date = $("#actDate").prop("value");
            var repair_start_date = $("#repairStartDate").prop("value");
            var repair_start_time = $("#repairStartTime").prop("value");
            var repair_end_date = $("#repairEndDate").prop("value");
            var repair_end_time = $("#repairEndTime").prop("value");
            var wait_start_date = $("#waitStartDate").prop("value");
            var wait_start_time = $("#waitStartTime").prop("value");
            var wait_end_date = $("#waitEndDate").prop("value");
            var wait_end_time = $("#waitEndTime").prop("value");
            var car_id = $("#repairActCar").prop("value");
            var respondent_id = $("#repairActRespondent").prop("value");
            var driver_id = $("#repairActDriver").prop("value");
            var mechanic_id = $("#repairActMechanic").prop("value");
            var repair_act_mileages = $("#repairActMileages").prop("value");
            var reason_for_repairs = $("#repairActReasonForRepairs").prop("value");
            var work_performed = $("#repairActWorkPerformed").prop("value");
            var is_correct = true;
            if (($.trim(repair_act_number) == "") || ($.trim(respondent_id) == "") ||
                ($.trim(car_id) == "") || ($.trim(driver_id) == "") || ($.trim(mechanic_id) == ""))
            {
                alert.append("<div>Не все обязательные поля заполнены</div>").show();
                return;
            }
            if (!dateCorrect(act_date))
            {
                alert.append("<div>Дата формирования акта задана некорректно</div>");
                is_correct = false;
            }
            if ((repair_start_date != "" && !dateCorrect(repair_start_date)) ||
                (repair_end_date != "" && !dateCorrect(repair_end_date)) ||
                (repair_start_time != "" && !timeCorrect(repair_start_time)) ||
                (repair_end_time != "" && !timeCorrect(repair_end_time)) ||
                (repair_start_date == "" && repair_start_time != "") ||
                (repair_end_date == "" && repair_end_time != ""))
            {
                alert.append("<div>Период ремонта задан некорректно</div>");
                is_correct = false;
            }
            if ((wait_start_date != "" && !dateCorrect(wait_start_date)) ||
                (wait_end_date != "" && !dateCorrect(wait_end_date)) ||
                (wait_start_time != "" && !timeCorrect(wait_start_time)) ||
                (wait_end_time != "" && !timeCorrect(wait_end_time)) ||
                (wait_start_date == "" && wait_start_time != "") ||
                (wait_start_date == "" && wait_start_time != ""))
            {
                alert.append("<div>Период ожидания ремонта задан некорректно</div>");
                is_correct = false;
            }
            if (($.trim(repair_act_number) != "") && (!intCorrect(repair_act_number)))
            {
                alert.append("<div>Номер акта указан неверно</div>");
                is_correct = false;
            }
            if (($.trim(repair_act_mileages) != "") && (!intCorrect(repair_act_mileages)))
            {
                alert.append("<div>Показания одометра указаны неверно</div>");
                is_correct = false;
            }
            var materials = "";
            $(".repair-act__material").each(function() {
                var materialName = $(this).find(".material-name").prop("value");
                var materialCount = $(this).find(".material-count").prop("value").replace(",", ".");
                var materialDescription = $(this).find(".material-description").prop("value");
                if (($.trim(materialCount) == "" && ($.trim(materialName) != "" || $.trim(materialDescription) != "" )) ||
                    ($.trim(materialCount) != "" && !floatCorrect(materialCount)))
                {
                    alert.append("<div>"+materialName+(materialName == "" ? "Н" : ": н")+
                        "екорректно задано количество материала</div>");
                    is_correct = false;
                }
                if (materialName != "" ||
                    materialCount != "" ||
                    materialDescription != "")
                {
                    var material = "";
                    var material_template = materialName+"@"+materialCount+"@"+materialDescription;
                    materials += material_template+"$";
                }
            });
            if (!is_correct)
            {
                alert.show();
                return;
            }
            var action = "";
            if (id_repair == 0) {
                action = "action=insert_act";
            }
            else {
                action = "action=update_act&repair_id="+id_repair;
            }
            if (wait_start_date != "") {
                wait_start_date = wait_start_date + " " + (wait_start_time != "" ? wait_start_time : "00:00");
            }
            if (wait_end_date != "") {
                wait_end_date = wait_end_date + " " + (wait_end_time != "" ? wait_end_time : "00:00");
            }
            if (repair_start_date != "")
            {
                repair_start_date = repair_start_date + " " + (repair_start_time != "" ? repair_start_time : "00:00");
            }
            if (repair_end_date != "") {
                repair_end_date = repair_end_date + " " + (repair_end_time != "" ? repair_end_time : "00:00");
            }
            var data = action+"&act_number="+repair_act_number+"&act_date="+act_date+"&responded_id="+
                respondent_id+"&car_id="+car_id+
                "&driver_id="+driver_id+"&mechanic_id="+mechanic_id+"&reason_for_repair="+reason_for_repairs+
                "&work_performed="+work_performed+"&act_odometer="+repair_act_mileages+"&act_wait_start_date="+
                wait_start_date+"&act_wait_end_date="+wait_end_date+"&act_repair_start_date="+
                repair_start_date+"&act_repair_end_date="+repair_end_date+
                "&expended_list="+materials+"&self_repair="+0;
            $.ajax({
                type: "POST",
                url: "inc/acts_modify.php",
                async: false,
                data: data,
                success: function(msg)
                {
                    if ($.trim(msg).length == 0)
                    {
                        //Если путевой лист успешно подан, закрываем диалог
                        location.href = "car_repair.php?id_car="+car_id;
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

        //Функция добавления акта выполненных работ в базу данных
        function InsertRepairAct()
        {
            ProcessRepairAct(0);
        }

        function UpdateRepairAct(id_repair)
        {
            ProcessRepairAct(id_repair);
        }

    }
);