//���� ���������� �� �������� ����������
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