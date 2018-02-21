 <?php
 include_once 'ldap.php';
 include_once 'auth.php';
 include_once 'SMTP.php';
 include_once 'const.php';

class Request {
    private $con;
    private $db;
    private $request_data= array();
    private $users_buffer;

    private function fatal_error ( $sErrorMessage = '' )
    {
        header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error ' );
        die( '<div id="request_result_error">'.$sErrorMessage.'</div>' );
    }

    public function __Construct(){
        $this->con=mysqli_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_BASE);
        if (mysqli_connect_errno())
            $this->fatal_error("Ошибка соединения с БД");
        mysqli_query($this->con,"SET NAMES 'utf8'");
        mysqli_autocommit($this->con, false);
        $ldap = new LDAP();
        $this->users_buffer = $ldap->GetAllUsers();
    }

    public function __Destruct() {
        if ($this->con) 
            mysqli_close($this->con);
    }

    public function BuildGanttSettingsForm($id_request)
    {
        $html = "<table>";
        if ($id_request == 1)
            $html .= "<tr><td>Транспорт: </td><td colspan='3'>".$this->CreateCarsComboBox(true)."</td></tr>";
        $html .= "<tr><td>Виды заявок: </td><td colspan='3'>".$this->CreateRequestsStatusCheckBoxGroup()."</td></tr>";
        $html .= "<tr><td>Начальная дата: </td><td><input name='date-from' type='text'></td></tr>";
        $html .= "<tr><td>Конечная дата: </td><td><input name='date-to' type='text'></td></tr>";
        $html .= "</table>";
        return $html;
    }

    public function BuildCalendarSettingsForm($id_request)
    {
        $html = "<table><tr><td width=30%>Департамент: </td><td>".$this->CreateDepartmentsComboBox(true,'','',true)."</td></tr>";
        if ($id_request == 1)
            $html .= "<tr><td>Транспорт: </td><td>".$this->CreateCarsComboBox(true)."</td></tr>";
        $html .= "<tr><td>Виды заявок: </td><td>".$this->CreateRequestsStatusCheckBoxGroup()."</td></tr>";
        $html .= "</table>";
        return $html;
    }

    /////////////////////////////
    //Функции работы с заявками//
    /////////////////////////////

    //Построитель неосновных полей формы создания заявки
    private function BuildField($field_type,$field_id,$field_values){
        $field_html="";
        
        switch ($field_type) {
            case "textarea"  :  
                $field_html='<textarea name="[name]" cols="50" rows="2">[value]</textarea>';
                break;
            case "select":
                $query="select id_field_data,field_data_value from sp_field_data where id_field=$field_id order by field_data_order";
                $query_values = "select * from request_data where id_request_number = ".$field_values['id_request_number'].
                    " AND id_field = ".$field_id;
                $res= mysqli_query($this->con, $query);
                $res_values = mysqli_query($this->con, $query_values);
                if ((!$res) || (!$res_values))
                    $this->fatal_error("Ошибка при выполнении запроса");
                $row = mysqli_fetch_array($res_values, MYSQLI_ASSOC);
                $field_html='<select name="[name]">';
                while ($mysql_data=mysqli_fetch_array($res,MYSQLI_ASSOC)){
                    if ($mysql_data["id_field_data"] == $row["field_value"])
                        $field_html.='<option selected="true" value="'.$mysql_data["id_field_data"].'">'.$mysql_data["field_data_value"].'</option>';
                    else
                        $field_html.='<option value="'.$mysql_data["id_field_data"].'">'.$mysql_data["field_data_value"].'</option>';
                }
				$field_html.='</select>';
                break;  
            case "checkbox":
                $query_fd="select id_field_data,field_data_value from sp_field_data where id_field=$field_id order by field_data_order";
                $query_values = "select * from request_data where id_request_number = ".$field_values['id_request_number'].
                    " AND id_field = ".$field_id;
                $result_fd= mysqli_query($this->con, $query_fd);
                if (!$result_fd)
                    $this->fatal_error("Ошибка при выполнении запроса");
                while ($mysql_data=mysqli_fetch_array($result_fd, MYSQLI_ASSOC)){
                    $res_values= mysqli_query($this->con, $query_values);
                    if (!$res_values)
                        $this->fatal_error("Ошибка при выполнении запроса");
                    $field_value = stripslashes($mysql_data["id_field_data"]);
                    $checked = false;
                    while ($data_values = mysqli_fetch_array($res_values, MYSQLI_ASSOC))
                    {
                        if (stripslashes($data_values["field_value"]) == $field_value)
                            $checked = true;
                    }
                    $field_html.='<input ';
                    if ($checked)
                        $field_html.='checked ';
                    $field_html.='<input  type="checkbox" name="[name][]" value="'.$mysql_data["id_field_data"].'">';
                    $field_html.='<label>'.$mysql_data["field_data_value"].'</label><br>';
                }
                break;
            case "text":
                $field_html='<input class="text_field" type="text" name="[name]" value="[value]">';
                break;
			 case "date":
                $field_html='<input  class="date_field" type="text" name="[name]" value="[value]">';
                break;	
			case "time":
                $field_html='<input  class="time_field" type="text" name="[name]" value="[value]">';
                break;
            default:  
                $field_html="Неизвестный тип поля";
                break;
        }
        $field_html= str_replace("[name]", "param$field_id",$field_html);
        $field_html= str_replace("[value]", stripslashes($field_values[$field_id]), $field_html);
        return $field_html;
    }

    //Проверка, исчерпан ли лимит по заявкам у департамента
    public function CheckRequestLimit($department, $stage, $event_date)
    {
        $record_count = $this->RequestCount($department, $stage, $event_date);
        if ($record_count > 0)
            return "";
        else
            return 'Вы исчерпали лимит по подаче заявок. Обратитесь к диспетчеру транспорта '.$department.' '.$stage.' '.$event_date;
    }

    //Получить количество свободных заявок по департаменту
    public function RequestCount($department, $stage, $event_date)
    {
        //Получаем maxReqCount
        $maxReqCount = 0;      //Максимальное количество разрешенных заявок в месяц по умолчанию
        if ($department == 'Организационно-контрольное управление')
        {
            $department = 'Администрация';
            $stage = 'Организационно-контрольное управление';
        }
        if ($stage !== null)
        {
            $query = "SELECT * FROM limite_req_except_dep WHERE Department = '".$department."' AND Stage = '".$stage."'";
            $res=mysqli_query($this->con,$query);
            if (!$res) {
                $this->fatal_error('Не удалось выполнить запрос к базе данных');
            }
            $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
            if ($row)
            {
                $maxReqCount = $row['MaxReq'];
                //Вычисляем текущее число поданых заявок
                $query = "SELECT COUNT(*) AS CountReq, MONTH(field_value) AS ReqMonth
                        FROM request_data RIGHT JOIN calendar_fields
                            ON (request_data.id_field = calendar_fields.start_date_field)
                            RIGHT JOIN request_number rn USING (id_request_number)
                        WHERE YEAR(str_to_date(field_value, '%d.%m.%Y')) = YEAR(STR_TO_DATE('".$event_date."','%d.%m.%Y'))
                        AND MONTH(str_to_date(field_value, '%d.%m.%Y')) = MONTH(STR_TO_DATE('".$event_date."','%d.%m.%Y'))".
                        ($department == 'Администрация' ?
                            " AND (rn.department IN ('Организационно-контрольное управление','Администрация')) " :
                            " AND (rn.department = '".$department."') ").
                        "AND (rn.stage LIKE '".$stage."%' OR '".$stage."' LIKE CONCAT(rn.stage,'%') ) AND (request_state <> 2) AND (request_state <> 4)
                        AND (alien_department=0) GROUP BY (ReqMonth)";
                $res=mysqli_query($this->con,$query);
                if (!$res)
                    $this->fatal_error("Не удалось выполнить запрос к базе данных");
                $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
                //Возвращаем текущее количество оставшихся заявок
                if (!$row)
                    return $maxReqCount;
                else
                    return $maxReqCount - $row['CountReq'];
            }
            else
            {
                $query = "SELECT * FROM limite_req_except_dep WHERE Department = '".$department."'";
                $res=mysqli_query($this->con,$query);
                if (!$res)
                    $this->fatal_error("Не удалось выполнить запрос к базе данных");
                $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
                if ($row)
                    $maxReqCount = $row['MaxReq'];
                //Вычисляем текущее число поданых заявок
                $query = "SELECT COUNT(*) AS CountReq, MONTH(field_value) AS ReqMonth
                        FROM request_data RIGHT JOIN calendar_fields
                            ON (request_data.id_field = calendar_fields.start_date_field)
                            RIGHT JOIN request_number rn USING (id_request_number)
                        WHERE YEAR(str_to_date(field_value, '%d.%m.%Y')) = YEAR(STR_TO_DATE('".$event_date."','%d.%m.%Y'))
                        AND MONTH(str_to_date(field_value, '%d.%m.%Y')) = MONTH(STR_TO_DATE('".$event_date."','%d.%m.%Y'))".
                        ($department == 'Администрация' ?
                            " AND (rn.department IN ('Организационно-контрольное управление','Администрация')) " :
                            " AND (rn.department = '".$department."') ").
                        "AND (rn.stage IS NULL OR rn.stage NOT IN (SELECT lred.Stage FROM limite_req_except_dep lred
                        WHERE lred.department = '".$department."' AND lred.Stage IS NOT NULL ))
                        AND (request_state <> 2) AND (request_state <> 4)
                        AND (alien_department=0) GROUP BY (ReqMonth)";
                $res=mysqli_query($this->con,$query);
                if (!$res)
                    $this->fatal_error("Не удалось выполнить запрос к базе данных");
                $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
                //Возвращаем текущее количество оставшихся заявок
                if (!$row)
                    return $maxReqCount;
                else
                    return $maxReqCount - $row["CountReq"];
            }
        } else
        {
            $query = "SELECT * FROM limite_req_except_dep WHERE Department = '".$department."'";
            $res=mysqli_query($this->con,$query);
            if (!$res)
                $this->fatal_error("Не удалось выполнить запрос к базе данных");
            $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
            if ($row)
                $maxReqCount = $row['MaxReq'];
            //Вычисляем текущее число поданых заявок
            $query = "SELECT COUNT(*) AS CountReq, MONTH(field_value) AS ReqMonth
                    FROM request_data RIGHT JOIN calendar_fields
                        ON (request_data.id_field = calendar_fields.start_date_field)
                        RIGHT JOIN request_number rn USING (id_request_number)
                    WHERE YEAR(str_to_date(field_value, '%d.%m.%Y')) = YEAR(STR_TO_DATE('".$event_date."','%d.%m.%Y'))
                    AND MONTH(str_to_date(field_value, '%d.%m.%Y')) = MONTH(STR_TO_DATE('".$event_date."','%d.%m.%Y'))".
                ($department == 'Администрация' ?
                    " AND (rn.department IN ('Организационно-контрольное управление','Администрация')) " :
                    " AND (rn.department = '".$department."') ").
                    "AND (rn.stage IS NULL OR rn.stage NOT IN (SELECT lred.Stage FROM limite_req_except_dep lred
                        WHERE lred.department = '".$department."' AND lred.Stage IS NOT NULL ))
                    AND (request_state <> 2) AND (request_state <> 4)
                    AND (alien_department=0) GROUP BY (ReqMonth)";
            $res=mysqli_query($this->con,$query);
            if (!$res)
                $this->fatal_error("Не удалось выполнить запрос к базе данных");
            $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
            //Возвращаем текущее количество оставшихся заявок
            if (!$row)
                return $maxReqCount;
            else
                return $maxReqCount - $row["CountReq"];
        }
    }

    //Несколько кривая функция проверки данных заявки
    public function ProcessRequest($request_array, $id_request_number){
        //Проверка основных полученных от пользователя данных
        $ldap = new LDAP();
        $user_department = $ldap->GetLoginParam('COMPANY');
        $user_stage = $ldap->GetLoginParam('DEPARTMENT');
        $organization = explode(':',stripslashes($request_array['department']));
        $department = $organization[0];
        $stage = $organization[1];
        if ((!Auth::hasPrivilege(AUTH_CHANGE_DEPARTMENT_REQUEST)) && ($user_department != $department)) {
            $this->fatal_error("У вас нет прав на изменение наименования департамента");
        }
        if (!Auth::hasPrivilege(AUTH_ROOT_DEPARTMENT_REQUEST) && ($user_department == $department) &&
            ((!isset($stage)) || (mb_substr($user_stage, 0, mb_strlen($stage)) != mb_substr($stage, 0, mb_strlen($user_stage))))) {
            $this->fatal_error("Вы имеете права на подачу заявок только на свое подразделение/отдел");
        }
        if (!is_array($request_array))
            $this->fatal_error("Переданные данные некорректного формата");
        if (isset($_POST['id_request']))
			$id_request=$_POST['id_request'];
        else
            $this->fatal_error("Не удалось найти идентификатор группы запросов");
        $html="";

        //Проверка преодоления лимита по подаваемым заявкам на месяц
        $event_date =  date_format(new DateTime(), 'd.m.Y');
        $query="select start_date_field from calendar_fields where id_request=$id_request";
        $res_date_field_id = mysqli_query($this->con,$query);
        if (!$res_date_field_id)
            $this->fatal_error("Не удалось выполнить запрос к базе данных");
        $mysql_data=mysqli_fetch_array($res_date_field_id, MYSQLI_ASSOC);
        if (isset($request_array["param".$mysql_data['start_date_field']]))
            $event_date = $request_array["param".$mysql_data['start_date_field']];
        if (!Auth::hasPrivilege(AUTH_UNLIMIT_REQUESTS))
        {
            $html = $this->CheckRequestLimit($department, $stage, $event_date);
        }
        if (!empty($html)) {
            return '<div id="request_result_error">'.$html.'</div>'; }

        $query="select id_field,field_type,field_name,field_value_type,field_required from sp_field where id_request=$id_request";
        $res=mysqli_query($this->con,$query);
        if (!$res)
            $this->fatal_error("Не удалось выполнить запрос к базе данных");

        //Заполнение массивов со значениями и характеристиками(тип, обязательность) полей запроса
        $fields_param=array();
        while($mysql_data=mysqli_fetch_array($res, MYSQLI_ASSOC)) {
            if (!isset($request_array["param".$mysql_data['id_field']]))
                $request_array["param".$mysql_data['id_field']]="";
            $fields_param["param".$mysql_data['id_field']] = array("name"=>$mysql_data['field_name'],"required"=>$mysql_data['field_required'],"type"=>$mysql_data['field_value_type']);
        }
        mysqli_free_result($res);

        //Проверка корректности заполнения полей на стороне сервера
        foreach($request_array as $field_key => $field_value) {
            if (($field_key!='action') && ($field_key!='department') && ($field_key != 'id_request_number') && ($field_key != 'id_car') && ($field_key != 'id_request'))
            {
                $html .= Helper::Check($fields_param[$field_key]['name'], $field_value,
                    $fields_param[$field_key]['type'], $fields_param[$field_key]['required']);
                if ($fields_param[$field_key]['type'] == 'float')
                    $request_array[$field_key] = Helper::TimeToFloat($field_value);
            }
        }
        if (!empty($html)) {
			return '<div id="request_result_error">'.$html.'</div>';
		} else {
            if (isset($_POST['id_request_number']) && ($id_request_number > 0))
            {
                if($this->UpdateRequest($request_array, $id_request_number))
                    return '<div id="request_result_success">Заявка изменена</div>';
                else
                    return '<div id="request_result_error">Заявка не изменена. Обратитесь к адмнистратору</div>';
            } else
            {
                if($this->AddRequest($request_array)) {
                    if (!Auth::hasPrivilege(AUTH_UNLIMIT_REQUESTS))
                        return '<div id="request_result_success">Заявка добавлена.</div><div id="request_count">Лимит ваших заявок на этот месяц составляет ' . $this->RequestCount($department, $stage, $event_date) . ' шт.</div>';
                    else
                        return '<div id="request_result_success">Заявка добавлена.</div>';
                }
                else
                    return '<div id="request_result_error">Заявка не добавлена. Обратитесь к адмнистратору</div>';
            }
		}
    }

    //Добавление запроса. $request_array - массив с данными
    public function AddRequest($request_array){
        $query="insert into request_number(id_request,user,department,stage,request_state, alien_department) values (?,?,?,?,?,?)";
        $pq=mysqli_prepare($this->con,$query);
        $id_request=$_POST["id_request"];
        $user=$_SERVER['REMOTE_USER'];
        $organization = explode(":",$request_array['department']);
        $department = stripslashes($organization[0]);
        $stage = stripslashes($organization[1]);
        if (empty($stage))
            $stage = null;
        $status=1;
        $ldap = new LDAP();
        $user_department = $ldap->GetLoginParam("COMPANY");
        if ($user_department != $department)
            $alien_department = 1;
        else
            $alien_department = 0;
		mysqli_stmt_bind_param($pq,'isssii',$id_request, $user, $department, $stage, $status, $alien_department);
		mysqli_stmt_execute($pq);
		if (mysqli_errno($this->con)!=0)
        {
            mysqli_rollback($this->con);
            return false;
        }
		$lid=mysqli_insert_id($this->con);
		mysqli_stmt_close($pq);
		$query="insert into request_data(id_request_number,id_field,field_value) values (?,?,?)";
		$pq=mysqli_prepare($this->con,$query);
		foreach($request_array as $field=>$field_value) {
            if (($field == 'action') || ($field == 'department') || ($field == 'id_request_number') || ($field == 'id_car'))
                continue;
            if (is_array($field_value))
            {
               for ($i = 0; $i < count($field_value); $i++)
               {
                   $id_request_number=$lid;
                   $id_field=str_replace("param","",$field);
                   $fvalue=$field_value[$i];
                   mysqli_stmt_bind_param($pq,'iis',$id_request_number,$id_field,$fvalue);
                   mysqli_stmt_execute($pq);
                   if (mysqli_errno($this->con)!=0)
                   {
                       mysqli_rollback($this->con);
                       return false;
                   }
               }
            } else
            {
                mysqli_stmt_bind_param($pq,'iis',$id_request_number,$id_field,$fvalue);
                $id_request_number=$lid;
                $id_field=str_replace("param","",$field);
                $fvalue=$field_value;
                mysqli_stmt_execute($pq);
                if (mysqli_errno($this->con)!=0)
                {
                    mysqli_rollback($this->con);
                    return false;
                }
            }
		}
        mysqli_stmt_close($pq);
        if (mysqli_commit($this->con))
        {
            $this->SendNotifyByEmail($id_request_number);
            return true;
        } else
            return false;
    }

    //
    //Изменение запроса. $request_array - массив с данными
    public function UpdateRequest($request_array, $id_request_number){
        $query='update request_number set department = ?, stage = ? where id_request_number = ?';
        $pq=mysqli_prepare($this->con,$query);
        $organization = explode(":",$request_array['department']);
        $department = stripslashes($organization[0]);
        $stage = stripslashes($organization[1]);
        if (empty($stage))
            $stage = null;
        mysqli_stmt_bind_param($pq,'ssi', $department, $stage, $id_request_number);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->con)!=0)
        {
            mysqli_rollback($this->con);
            return false;
        }
        mysqli_stmt_close($pq);
        $delete_query = "delete from request_data where id_request_number = ?";
        $pq=mysqli_prepare($this->con,$delete_query);
        mysqli_stmt_bind_param($pq,'i',$id_request_number);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->con)!=0)
        {
            mysqli_rollback($this->con);
            return false;
        }
        mysqli_stmt_close($pq);

        $query="insert into request_data(id_request_number,id_field,field_value) values (?,?,?)";
        $pq=mysqli_prepare($this->con,$query);
        foreach($request_array as $field=>$field_value){
            if (($field == 'action') || ($field == 'department') || ($field == 'id_request_number') ||
                ($field == 'id_car') || ($field == 'id_request'))
                continue;
            if (is_array($field_value))
            {
                for ($i = 0; $i < count($field_value); $i++)
                {
                    $id_field=str_replace("param","",$field);
                    $fvalue=$field_value[$i];
                    mysqli_stmt_bind_param($pq,'iis',$id_request_number,$id_field,$fvalue);
                    mysqli_stmt_execute($pq);
                    if (mysqli_errno($this->con)!=0)
                    {
                        mysqli_rollback($this->con);
                        return false;
                    }
                }
            } else
            {
                $id_field=str_replace("param","",$field);
                $fvalue=$field_value;
                mysqli_stmt_bind_param($pq,'iis',$id_request_number,$id_field,$fvalue);
                mysqli_stmt_execute($pq);
                if (mysqli_errno($this->con)!=0)
                {
                    mysqli_rollback($this->con);
                    return false;
                }
            }
        }
        mysqli_stmt_close($pq);
        $this->ModifyTransport($id_request_number, $request_array['id_car']);
        return true;
    }

    //Построение формы добавления заявки
    public function BuildRequest($id_request, $id_request_number){
        $request_data = array();
        $request_data['id_request_number'] = $id_request_number;
        if ($id_request_number > 0)
        {
            //Мы производим модификацию, значит надо заполнить массив значений полей заявки
            $title = 'Изменить заявку';
            $query = 'SELECT id_field, field_value, department,stage FROM request_data INNER JOIN request_number USING (id_request_number) WHERE id_request_number = '.$id_request_number;
            $result = mysqli_query($this->con, $query);
            if (!$result)
                $this->fatal_error('Ошибка выполнения запроса к базе данных');
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
            {
                $request_data[$row['id_field']] = $row['field_value'];
                $request_data['department'] = $row['department'];
                $request_data['stage'] = $row['stage'];
            }
            mysqli_free_result($result);
            if (($id_request == 1) && (Auth::isAcceptedRequest($id_request_number)))
            {
                $query = 'SELECT * FROM cars_for_transport_requests WHERE id_request_number = '.$id_request_number;
                $result = mysqli_query($this->con, $query);
                if (!$result)
                    $this->fatal_error('Ошибка выполнения запроса к базе данных');
                if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
                {
                    $request_data['id_car'] = $row['id_car'];
                }
            }
        } else
        {
            //Мы добавляем новую запись
            $title = 'Добавить заявку';
        }

        $html="";
        $query="select id_request,request_name from sp_request where id_request=$id_request";
        $res= mysqli_query($this->con,$query);
        if (!$res)
            $this->fatal_error("Ошибка выполнения запроса к базе данных");
        $data=mysqli_fetch_assoc($res);
        if (!$data)
            $this->fatal_error("Заданный код заявки не существует");
        $this->request_data["request_caption"]= $data["request_name"];
        $this->request_data["id_request"]=$data["id_request"];
        $query="select * from sp_field where id_request=".$this->request_data["id_request"].
            " order by field_order";
        $res=  mysqli_query($this->con,$query);
        $html.='<div id="frmRequest" title="'.$title.'">';
        $html.='<form id="RequestForm" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
        $html.='<table id="ParamTable">';

        //Строим основное поле подразделения
        $html.='<tr><td width="35%">Подразделение<span class="required_field_mark">*</span></td><td>';
        $html.=$this->CreateDepartmentsComboBox(false, $request_data['department'], $request_data['stage']);
        $html.="</td></tr>";

        //Строим поле авто, если это изменение заявки и если это транспортная заявка
        if (($id_request == 1) && ($id_request_number >0) && Auth::isAcceptedRequest($id_request_number))
        {
            $html.='<tr><td width="35%">Транспорт</td><td>';
            $html.=$this->CreateCarsComboBox(false, $request_data['id_car']);
            $html.="</td></tr>";
        }

        //Строим неосновные поля
        while($mysql_data=  mysqli_fetch_array($res, MYSQLI_ASSOC)){
            $html.='<tr><td>';
            $html.=$mysql_data["field_name"];
            $html.=($mysql_data["field_required"]==1 ? '<span class="required_field_mark">*</span>':'');
            $html.='</td><td>';
            if ($mysql_data["field_name"] == 'Звуковое сопровождение')
                $html.='<fieldset>';

            if ($mysql_data["field_value_type"] == 'float')
            {
                $value = $request_data[$mysql_data["id_field"]];
                if (strpos($value,'.'))
                {
                    $float_parts = explode('.',$value);
                    $hour = intval($float_parts[0]);
                    $minute = round(($float_parts[1] / 100)*60);
                    if ($hour < 10)
                        $hour = '0'.$hour;
                    if ($minute < 10)
                        $minute = '0'.$minute;
                    $value = $hour.":".$minute;
                }
                $request_data[$mysql_data["id_field"]] = $value;
            }

            $html.=$this->BuildField($mysql_data["field_type"],$mysql_data["id_field"],$request_data);
            if ($mysql_data["field_name"] == 'Звуковое сопровождение')
                $html.='</fieldset>';
            $html.='</td></tr>';
        }
        $html.='<tr><td></td></tr></table><div id="div_result"></div></form></div>';
        return $html;
    }

    //Получение деталей запроса
    public function GetRequestDetails($id_request_number)
    {
        $query_req_num = "select id_request from request_number where id_request_number = $id_request_number";
        $result_req_num = mysqli_query($this->con, $query_req_num);
        if (!$result_req_num)
            $this->fatal_error("Ошибка при выполнении запроса к базе данных");
        $row = mysqli_fetch_array($result_req_num, MYSQLI_ASSOC);
        $id_request = $row['id_request'];

        $query_fields = "select * from sp_field where id_request = $id_request";
        $result_fields = mysqli_query($this->con, $query_fields);

        $query_values = "select * from request_data where id_request_number = $id_request_number";
        $result_values = mysqli_query($this->con, $query_values);

        $query_department = "select department, stage from request_number where id_request_number = $id_request_number";
        $result_department = mysqli_query($this->con, $query_department);
        if ((!$result_fields) || (!$result_values) || (!$query_department))
            $this->fatal_error("Ошибка при выполнении запроса к базе данных");

        $array = Array();
        while($data = mysqli_fetch_array($result_values, MYSQLI_ASSOC))
            $array[$data['id_field']] = $data['field_value'];
        mysqli_free_result($result_values);

        //Объявляем таблицу
        $result = '<table style="border: #858585 solid 1px;" id="detail_table" cellpadding="5" cellspacing="0">';
        $class = "even";
        //Добавляем поле департамента (ключевое и обязательное)
        $department = mysqli_fetch_array($result_department, MYSQLI_ASSOC);
        $result .= '<tr class="'.$class.'"><td id="detail_header" width="30%">Департамент:</td><td>'.htmlspecialchars($department['department']).'</td></tr>';
        //Добавляем поле отдела, если отдел имеется
        if (!empty($department['stage']))
        {
            $class = ($class == "even")?"odd":"even";
            $result .= '<tr class="'.$class.'"><td id="detail_header" width="30%">Отдел:</td><td>'.htmlspecialchars($department['stage']).'</td></tr>';
        }

        //Если заявка транспортная, выводим транспортное поле, если оно необходимо
        if ($id_request == 1)
        {
            $query_transport = "SELECT c.id, c.id_chief_default, c.id_model, c.number, c.type, cc.name AS owner, c.id_fuel_default,
                  c.id_driver_default, c.department_default, c.is_active, cm.model
                FROM cars_for_transport_requests  cftr
                  INNER JOIN cars c ON c.id = cftr.id_car
                  LEFT JOIN car_models cm ON c.id_model = cm.id_model
                  LEFT JOIN cars_chiefs cc ON c.id_chief_default = cc.id_chief
                WHERE id_request_number = $id_request_number";
            $result_transport = mysqli_query($this->con, $query_transport);
            if (!$result_transport)
                $this->fatal_error("Ошибка при выполнении запроса к базе данных");
            if ($row = mysqli_fetch_array($result_transport, MYSQLI_ASSOC))
            {
                $class = ($class == "even")?"odd":"even";
                $result .= '<tr class="'.$class.'"><td id="detail_header" width="30%">Выделенный транспорт:</td><td>';
                if ($row['type'] == 'Такси')
                {
                    $result .= 'Такси';
                } else {
                    $result_car = '';
                    if ($row['number'] != '')
                        $result_car .= 'Регистрационный номер: "'.htmlspecialchars($row['number']).'"';
                    if ($row['model'] != '')
                    {
                        if ($result_car != '')
                            $result_car .= '<br>';
                        $result_car .= 'Модель: "'.htmlspecialchars($row['model']).'"';
                    }
                    if ($row['type'] != '')
                    {
                        if ($result_car != '')
                            $result_car .= ' - ';
                        $result_car .= htmlspecialchars($row['type']);
                    }
                    if ($row['owner'] != '')
                    {
                        if ($result_car != '')
                            $result_car .= '<br>';
                        $result_car .= 'Ответственный: "'.htmlspecialchars($row['owner']).'"';
                    }
                    $result .= $result_car;
                }
                $result .= '</td></tr>';
            }
        }
        //Добавляем неключевые поля
        while($data = mysqli_fetch_array($result_fields, MYSQLI_ASSOC))
        {
            $field_id = $data["id_field"];
            if (trim($array[$field_id]) == "")
                continue;
            $class = ($class == "even")?"odd":"even";
            $field_html='<tr class="'.$class.'"><td id="detail_header" width="30%">'.htmlspecialchars(stripslashes($data["field_name"])).':</td><td>';
            $field_type = $data["field_type"];
            switch ($field_type){
                case "checkbox":
                    $query="select id_field_data,field_data_value from sp_field_data where id_field=$field_id order by field_data_order";
                    $query_values = "select * from request_data where id_request_number = $id_request_number AND id_field = $field_id";
                    $res= mysqli_query($this->con, $query);
                    if (!$res)
                        $this->fatal_error("Ошибка при выполнении запроса");
                    while ($mysql_data=mysqli_fetch_array($res, MYSQLI_ASSOC)){
                        $res_values= mysqli_query($this->con, $query_values);
                        if (!$res_values)
                            $this->fatal_error("Ошибка при выполнении запроса");
                        $field_value = stripslashes($mysql_data["id_field_data"]);
                        $checked = false;
                        while ($data_values = mysqli_fetch_array($res_values, MYSQLI_ASSOC))
                        {
                            if (stripslashes($data_values["field_value"]) == $field_value)
                                $checked = true;
                        }
                        $field_html.='<input disabled="true" ';
                        if ($checked)
                            $field_html.='checked ';
                        $field_html.='type="checkbox">'.stripslashes($mysql_data["field_data_value"]);
                    }
                    break;
                default:
                    if ($data["field_name"] == 'Требуется транспорт')
                    {
                        $query="select id_field_data,field_data_value from sp_field_data where (id_field=$field_id) AND
                              (id_field_data = $array[$field_id])";
                        $res = mysqli_query($this->con, $query);
                        if (!$res)
                            $this->fatal_error("Ошибка при выполнении запроса");
                        $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
                        $field_html.= htmlspecialchars(stripslashes($row['field_data_value']));
                    }
                    else
                    {
                        if ($data["field_value_type"] == 'float')
                        {
                            $value = $array[$field_id];
                            if (strpos($value,'.'))
                            {
                                $float_parts = explode('.',$value);
                                $hour = intval($float_parts[0]);
                                $minute = round(($float_parts[1] / 100)*60);
                                if ($hour < 10)
                                    $hour = '0'.$hour;
                                if ($minute < 10)
                                    $minute = '0'.$minute;
                                $value = $hour.":".$minute;
                            }
                        }
                        else
                            $value = $array[$field_id];
                        $field_html.= htmlspecialchars(stripslashes($value));
                    }
                    break;
            }
            $result.=$field_html."</td></tr>";
        }
        $result.='</table>';

        //Если обращаемся за детальной информацией с календаря, не выводить кнопки управления
        if (isset($_POST['from_calendar']) && $_POST['from_calendar'] == 1)
            return $result;

        //Если пользователь имеет права на модификацию заявки, добавляем кнопки управления статусом
        if ((Auth::hasModifyStatusRequestsPrivileges($id_request_number) || Auth::hasCancelYourselfRequestPrivileges($id_request_number)) &&
            (Auth::isLookingRequest($id_request_number) || Auth::isAcceptedRequest($id_request_number)))
        {
            $result.='<table id="modify_buttons_table"><tr><td id="modify_buttons">';

            if (Auth::hasModifyStatusRequestsPrivileges($id_request_number))
            {
                $result.='<button value="'.$id_request_number.'" class="btnChangeRequest">Изменить заявку</button>';
                if (Auth::isLookingRequest($id_request_number))
                {
                    $result.='<button value="'.$id_request_number.'" class="btnAcceptRequest">Принять заявку</button>';
                    $result.='<button value="'.$id_request_number.'" class="btnRejectRequest">Отклонить заявку</button>';
                } else
                    if (Auth::isAcceptedRequest($id_request_number))
                    {
                        $result.='<button value="'.$id_request_number.'" class="btnCompleteRequest">Заявка выполнена</button>';
                        $result.='<button value="'.$id_request_number.'" class="btnUnCompleteRequest">Заявка не выполнена</button>';
                    }
            }
            if (Auth::hasCancelYourselfRequestPrivileges($id_request_number))
                $result.='<button value="'.$id_request_number.'" class="btnCancelRequest">Отменить заявку</button>';
            $result.='</td></tr></table>';
        }
        mysqli_free_result($result_fields);
        return $result;
    }

    //Отправка сообщения пользователю об изменении статуса его заявки
    private function SendNotifyByEmail($id_request_number)
    {
        //Узнаем всю информацию о пользователе и заявке, для отправки сообщения
        $query = 'SELECT user, id_request_status, request_status, alien_department, email_notify_template
                  FROM request_number
                    INNER JOIN sp_request_status ON (request_number.request_state = sp_request_status.id_request_status)
                  WHERE id_request_number = '.$id_request_number;
        $result = mysqli_query($this->con, $query);
        if (!$result)
            $this->fatal_error("Ошибка при выполнении запроса к базе данных");
        if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
        {
            //Если заявка подана диспетчером по служебной записке, то не отправлять ему уведомление
            $smtp = new SMTP();
            $smtp->Connect();
            $ldap = new LDAP();
            $user_login = explode('\\', $row['user']);
            if ($row['alien_department'] != 1)
            {
                $email_template = $row['email_notify_template'];
                if (!empty($email_template))
                {
                    $email = $ldap->GetLoginParamByLogin("MAIL", $user_login[1]);
                    $user_name = $ldap->GetLoginParamByLogin("FIO", $user_login[1]);
                    $date = new DateTime();
                    $email_template = str_replace('%user_name%',$user_name, $email_template);
                    $email_template = str_replace('%req_num%',$id_request_number, $email_template);
                    $email_template = str_replace('%date%',date_format($date, 'd.m.Y'), $email_template);
                    $email_body = str_replace('%time%',date_format($date, 'H:i'), $email_template);
                    if ($row['id_request_status'] == 1)
                        $title = 'Ваша заявка успешно принята';
                    else
                        $title = 'Статус вашей заявки изменен';
					try
					{
						$smtp->SendEmail(SMTP_FROM, $email, $title, $email_body);
					} catch(Exception $e)
					{
						// Stub
					}
                }
            }
            //Оповестить всех broadcast-notify-пользователей о появлении новой заявки
            if ($row['id_request_status'] == 1)
            {
                $query_bnu = 'SELECT user FROM broadcast_notify_users WHERE id_request = '.$_POST['id_request'];

                $result_bnu = mysqli_query($this->con, $query_bnu);
                if (!$result_bnu)
                    $this->fatal_error("Ошибка при выполнении запроса к базе данных");
                while ($row_bnu = mysqli_fetch_array($result_bnu, MYSQLI_ASSOC))
                {
                    $disp_login = explode('\\', $row_bnu['user']);
                    $date = new DateTime();
                    //Не отправлять уведомление диспетчеру, если он сам отправил заявку
                    if ($user_login == $disp_login)
                        continue;
                    $disp_email = $ldap->GetLoginParamByLogin("MAIL", $disp_login[1]);
                    $disp_name = $ldap->GetLoginParamByLogin("FIO", $disp_login[1]);
					try
					{
						$smtp->SendEmail(SMTP_FROM, $disp_email, "Новая заявка №".$id_request_number,
							"Здравствуйте, ".$disp_name."!<br>Подана новая заявка №".$id_request_number.
								". Дата подачи ".date_format($date, 'd.m.Y H:i'));
					} catch(Exception $e)
					{
						// Stub
					}
                }
            }
            //Оповестить всех broadcast-notify-пользователей об отмене заявки пользователем
            if ($row['id_request_status'] == 2)
            {
                $query_bnu = 'SELECT user FROM broadcast_notify_users WHERE id_request = '.$_POST['id_request'];

                $result_bnu = mysqli_query($this->con, $query_bnu);
                if (!$result_bnu)
                    $this->fatal_error("Ошибка при выполнении запроса к базе данных");
                while ($row_bnu = mysqli_fetch_array($result_bnu, MYSQLI_ASSOC))
                {
                    $disp_login = explode('\\', $row_bnu['user']);
                    $date = new DateTime();
                    //Не отправлять уведомление диспетчеру, если он сам отправил заявку
                    if ($user_login == $disp_login)
                        continue;
                    $disp_email = $ldap->GetLoginParamByLogin("MAIL", $disp_login[1]);
                    $disp_name = $ldap->GetLoginParamByLogin("FIO", $disp_login[1]);
					try
					{
						$smtp->SendEmail(SMTP_FROM, $disp_email, "Заявка №".$id_request_number,
							"Здравствуйте, ".$disp_name."!<br>Заявка №".$id_request_number.
								" была <span style='color: red; font-weight: 600'>отменена</span> пользователем. Дата отмены заявки ".date_format($date, 'd.m.Y H:i'));
					} catch(Exception $e)
					{
						// Stub
					}
                }
            }
            $smtp->Quit();
        } else
            $this->fatal_error("Отсутствует заявка с указанным идентификатором");
    }

    //Функци изменения транспорта
    public  function ModifyTransport($id_request_number, $id_car)
    {
        $query = 'UPDATE cars_for_transport_requests SET id_car= ? WHERE id_request_number= ?';
        $pq = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($pq, 'ii', $id_car, $id_request_number);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->con) != 0)
        {
            mysqli_rollback($this->con);
            $this->fatal_error('Ошибка при выполнении запроса к базе данных.'.$id_car.' '.$id_request_number);
        }
        mysqli_commit($this->con);
    }

    //Изменить статус заявки
    private function ModifyRequestStatus($id_request_number, $status)
    {
        $query = "UPDATE request_number SET request_state = $status WHERE id_request_number = $id_request_number";
        $pq = mysqli_prepare($this->con, $query);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->con)!=0)
        {
            mysqli_rollback($this->con);
            $this->fatal_error('Ошибка при выполнении запроса к базе данных');
        }
        mysqli_stmt_close($pq);
        mysqli_commit($this->con);
    }

    //Отказать в заявке
    public function RejectRequest($id_request_number)
    {
        if (Auth::hasModifyStatusRequestsPrivileges($id_request_number) && Auth::isLookingRequest($id_request_number))
            $this->ModifyRequestStatus($id_request_number, 4);
        else
            $this->fatal_error("У вас нет привилегий на изменение статуса запроса");
        $this->SendNotifyByEmail($id_request_number);
    }

    //Подтвердить заявку
    public function AcceptRequest($id_request_number)
    {
        if (Auth::hasModifyStatusRequestsPrivileges($id_request_number) && Auth::isLookingRequest($id_request_number))
        {
            if ($_POST['id_request'] == 1)
            {
                if (!isset($_POST['id_car']))
                    $this->fatal_error("Не указан автомобиль для транспортной заявки");
                $car_id = $_POST['id_car'];
                $query = "INSERT INTO cars_for_transport_requests(id_request_number, id_car) VALUES (?,?)";
                $pq = mysqli_prepare($this->con, $query);
                if (!$pq)
                    $this->fatal_error("Не удалось произвести разбор запроса SQL");
                mysqli_stmt_bind_param($pq, 'ii', $id_request_number, $car_id);
                if (!mysqli_stmt_execute($pq))
                    $this->fatal_error("Ошибка при исполнении запроса к базе данных");
                mysqli_stmt_free_result($pq);
            }
            $this->ModifyRequestStatus($id_request_number, 3);
        }
        else
            $this->fatal_error("У вас нет привилегий на изменение статуса запроса");
        $this->SendNotifyByEmail($id_request_number);
    }

    //Отметить заявку как выполненную
    public function CompleteRequest($id_request_number)
    {
        if (Auth::hasModifyStatusRequestsPrivileges($id_request_number) && Auth::isAcceptedRequest($id_request_number))
            $this->ModifyRequestStatus($id_request_number, 5);
        else
            $this->fatal_error("У вас нет привилегий на изменение статуса запроса");
    }

    //Отметить заявку как невыполненную
    public function UnCompleteRequest($id_request_number)
    {
        if (Auth::hasModifyStatusRequestsPrivileges($id_request_number) && Auth::isAcceptedRequest($id_request_number))
            $this->ModifyRequestStatus($id_request_number, 6);
        else
            $this->fatal_error("У вас нет привилегий на изменение статуса запроса");
    }

    //Отменить заявку
    public function CancelRequest($id_request_number)
    {
        if (Auth::hasCancelYourselfRequestPrivileges($id_request_number))
            $this->ModifyRequestStatus($id_request_number, 2);
        else
            $this->fatal_error("У вас нет привилегий на изменение статуса запроса");
        $this->SendNotifyByEmail($id_request_number);
    }

    /////////////////////////////////////////
    //Функции построения выпадающих списков//
    /////////////////////////////////////////

    //Функция построения и заполнения ComboBox департаментов
    public function CreateDepartmentsComboBox($include_all_marker = false, $current_department = '', $current_stage = '', $include_all_marker_force = false)
    {
        $html ='<select name="department">';
        $ldap = new LDAP();
        if ($current_department == '')
            $user_department = $ldap->GetLoginParam('COMPANY');
        else
            $user_department = $current_department;
        if (!$include_all_marker && !Auth::hasPrivilege(AUTH_CHANGE_DEPARTMENT_REQUEST))
            $departments = $ldap->getDepartmentsAndSections($user_department);
        else
        if (!Auth::hasPrivilege(AUTH_ALL_DEPARTMENTS_READ_DATA))
            $departments = $ldap->getDepartmentsAndSections($user_department);
        else
            $departments = $departments = $ldap->getDepartmentsAndSections("");

        if ((($include_all_marker) && (Auth::hasPrivilege(AUTH_ALL_DEPARTMENTS_READ_DATA))) || ($include_all_marker_force))
            $html .= '<option value="Все департаменты">Все департаменты</option>';

        foreach ($departments as $department => $department_parts )
        {
            if (($department == $user_department) && (empty($current_stage)))
                $html.='<option class="department" selected="true" value="'.htmlspecialchars($department).'">'.$department.'</option>';
            else
                $html.='<option class="department" value="'.htmlspecialchars($department).'">'.$department.'</option>';
            foreach($department_parts as $department_part => $index)
            {
                if (($department == $user_department) && (($current_stage == $department_part)))
                    $html.='<option class="department_part" selected="true" value="'.htmlspecialchars($department).':'.htmlspecialchars($department_part).'">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp'.$department_part.'</option>';
                else
                    $html.='<option class="department_part" value="'.htmlspecialchars($department).':'.htmlspecialchars($department_part).'">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp'.$department_part.'</option>';
            }
        }
        $html.="</select>";
        return $html;
    }

    //Функция построения и заполнения ComboBox департаментов (без отделов)
    public function CreateDepartmentsWithoutStageComboBox()
    {
        $html ='<select name="department">';
        $ldap = new LDAP();
        $departments = $ldap->getDepartments("");
        $is_first = true;
        foreach ($departments as $department => $value )
        {
            if ($is_first)
                $html.='<option selected="true" value="'.htmlspecialchars($department).'">'.$department.'</option>';
            else
                $html.='<option value="'.htmlspecialchars($department).'">'.$department.'</option>';
            $is_first = false;
        }
        $html.="</select>";
        return $html;
    }

    //Функция построения и заполнения ComboBox машин
    public function CreateCarsComboBox($include_all_marker = false, $id_car = 0)
    {
        $query = "SELECT id, id_chief_default, c.id_model, cm.model, number, type, cc.name AS owner, id_fuel_default,
              id_driver_default, department_default, c.is_active
            FROM cars c
              LEFT JOIN car_models cm ON c.id_model = cm.id_model
              LEFT JOIN cars_chiefs cc ON c.id_chief_default = cc.id_chief
            WHERE c.is_active = 1 ORDER BY number";
        $result = mysqli_query($this->con, $query);
        if (!$result)
            $this->fatal_error("Ошибка при выполнении запроса к базе данных");
        $is_first_row = true;
        $html = "<select name='car_id'>";
        if ($include_all_marker)
        {
            $html .= '<option selected="true" value="Весь транспорт">Весь транспорт</option>';
            $is_first_row = false;
        }
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
        {
            if ($row['owner'] == 'диспетчер')
                $class="dispetcher_car";
            else
            if ($row['owner'] != '')
                $class="owner_car";
            else
                $class = "taxi_car";
            $value = "";
            if ($row['number'] != "")
                $value .= $row['number'];
            if ($row['type'] != "")
            {
                if ($value != "")
                    $value .= " | ";
                $value .= $row['type'];
            }
            if ($row['model'] != "")
            {
                if ($value != "")
                    $value .= " | ";
                $value .= $row['model'];
            }
            if ($row['owner'] != "")
            {
                if ($value != "")
                    $value .= " | ";
                $value .= $row['owner'];
            }
            if ($is_first_row && (($id_car == $row['id']) || ($id_car == 0)))
            {
                $html.='<option class="'.$class.'" selected="true" value="'. $row['id'].'">'.$value.'</option>';
                $is_first_row = false;
            } else
                $html.='<option class="'.$class.'" value="'.$row['id'].'">'.$value.'</option>';
        }
        $html.="</select>";
        return $html;
    }

    //Функция построения и заполнения ComboBox руководителей/владельцев транспорта
    public function CreateChiefsComboBox()
    {
        $query = "SELECT * FROM cars_chiefs WHERE is_active = 1 ORDER BY name";
        $result = mysqli_query($this->con, $query);
        if (!$result)
            $this->fatal_error("Ошибка при выполнении запроса к базе данных");
        $is_first_row = true;
        $html = "<select id='car_chief' name='car_chief'>";
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
        {
            $value = $row['name'];
            if ($is_first_row)
            {
                $html.='<option selected="true" value="'. $row['id_chief'].'">'.$value.'</option>';
                $is_first_row = false;
            } else
                $html.='<option value="'.$row['id_chief'].'">'.$value.'</option>';
        }
        $html.="</select>";
        return $html;
    }


    //Функция создания группы CheckBox'ов для выбора статусов заявок, которые необходимо отобразить на календаре
    public function CreateRequestsStatusCheckBoxGroup()
    {
        $query = "SELECT * FROM sp_request_status";
        $result = mysqli_query($this->con, $query);
        if (!$result)
            $this->fatal_error('Ошибка при выполнении запроса к базе данных');
        $html = "<fieldset>";
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
        {
            $html .= "<input checked='true' type='checkbox' class='requestState' value='".$row['id_request_status']."'>".$row['request_status']."<br>";
        }
        $html .= "</fieldset>";
        mysqli_free_result($result);
        return $html;
    }

    ///////////////////////////////////////////////////////////////////////////
    //Интерфейс IQuery. Декларирует параметры для корректной работы DataTable//
    ///////////////////////////////////////////////////////////////////////////

    public function Columns()
    {
        return array( 'edit_lbl','id_request_number', 'user', 'request_date', 'request_status');
    }

    public function Table()
    {
        return "(SELECT '<img src=\'img/details_open.png\'>' AS edit_lbl, requests.* FROM requests) t";
    }

    public function Where()
    {
        $sWhere = "";
        if (isset($_POST['id_request'])) {
            $sWhere = "WHERE (id_request = ".$_POST['id_request'].")"; }
        else {
            $sWhere = "WHERE (id_request = 1)"; }

        if (isset($_POST['only_my_requests']) && ($_POST['only_my_requests'] == 1))
        {
            if ( $sWhere == "" )
            {
                $sWhere = "WHERE ";
            }
            else
            {
                $sWhere .= " AND ";
            }
            $user = $_SERVER['REMOTE_USER'];
            $sWhere .= "(user = '".mysqli_real_escape_string($this->con, $user)."')";
        }
        if (!Auth::hasPrivilege(AUTH_ALL_DEPARTMENTS_READ_DATA))
        {
            if ( $sWhere == "" )
            {
                $sWhere = "WHERE ";
            }
            else
            {
                $sWhere .= " AND ";
            }
            $ldap = new LDAP();
            $department = $ldap->GetLoginParam("COMPANY");
            $sWhere .= "(department = '".$department."')";
        }
        if ( $sWhere == "" )
        {
            $sWhere = "WHERE ";
        }
        else
        {
            $sWhere .= " AND ";
        }
        $sWhere .= "(request_date > DATE_SUB(NOW(), INTERVAL 6 MONTH))";
        return $sWhere;
    }

    public function IndexColumn()
    {
        return "id_request_number";
    }

    public function DisplayColumnNames()
    {
        return '{"head":"<tr><th></th><th>Номер заявки</th><th>Пользователь</th><th>Дата подачи заявки</th><th>Статус заявки</th></tr>",
                 "foot":"<tr><th></th><th>Номер заявки</th><th>Пользователь</th><th>Дата подачи заявки</th><th>Статус заявки</th></tr>"}';
    }

    public function FilterColumnsData($column, $data)
    {
        $sOutput = "";
        if ( $column == "request_status")
        {
            switch ($data)
            {
                case "Принята к исполнению":
                    $sOutput .= '<span class=\'req_accepted_status\'>'.Helper::ClearJsonString($data).'</span>';
                    break;
                case "Выполнена":
                    $sOutput .= '<span class=\'req_complete_status\'>'.Helper::ClearJsonString($data).'</span>';
                    break;
                case "Отказано диспетчером":case "Не выполнена":
                $sOutput .= '<span class=\'req_canceled_status\'>'.Helper::ClearJsonString($data).'</span>';
                break;
                case "Отменена пользователем":
                    $sOutput .= '<span class=\'req_canceled_by_user\'>'.Helper::ClearJsonString($data).'</span>';
                    break;
                default:
                    $sOutput .= Helper::ClearJsonString($data);
                    break;
            }
        } else
        if ( $column == "user")
        {
            $login = $data;
            $login = explode(" ", $login, 2);
            if (sizeof($login) > 1)
                $postfix = $login[1];
            $login = explode("\\", $login[0]);
            $FIO = $this->users_buffer[strtoupper($login[1])];
            $sOutput .= Helper::ClearJsonString($FIO);
        } else
        if ( $column == "edit_lbl")
        {
            $sOutput .= $data;
        }
        else
            $sOutput .= Helper::ClearJsonString($data);

        return $sOutput;
    }
}