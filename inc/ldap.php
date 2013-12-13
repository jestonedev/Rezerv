<?php
include_once "const.php";
include_once "auth.php";

class LDAP
{
	private $user_login;
	private $link;
	private $bind;
	
	// Соединяемся с сервером LDAP
	public function __Construct($login=""){
		$this->Bind();
		$this->SetLogin($login);
	} 
	
	public function _Destruct(){
			$this->Disconnect();
	}
	
	private function Connect(){
		if (!$this->link){
			$this->link = ldap_connect(LDAP_SERVER, LDAP_PORT) or die("Ошибка соединения с LDAP сервером");
			ldap_set_option($this->link, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($this->link, LDAP_OPT_REFERRALS, 0);
			ldap_set_option($this->link, LDAP_OPT_SIZELIMIT, 1000);  
		}
	}
	
	private function Disconnect(){
		if ($this->link) ldap_unbind($this->link);
	}
	
	private function Bind(){
		if (!$this->link){
			$this->Connect();
		} 
		$this->bind=@ldap_bind($this->link, LDAP_USER, LDAP_PASS) or die("Доступ текущему пользователю запрещен");
		
	}
	
	private function SetLogin($login=""){
		if (isset($login)&&($login!=="")) {
			if ($login!==$this->user_login)
				$this->user_login=$login;}
		else{
			$this->user_login=$this->GetCurrentLogin();	
		}
			
	}
	
	private function GetCurrentLogin()
	{
		$login = $_SERVER['AUTH_USER'];
		$login = explode("\\", $login);
		return $login[1];
	}
	
	public function GetLogin(){
		return $this->user_login;
	}
	
	public function GetUserDepartment(){
		$login=$this->GetLogin();
		$this->GetUserDepartmentByLogin($login);
	}

    public function GetUserDepartmentByLogin($login)
    {
        if($this->bind)
        {
            $filter = "(&(objectClass=user)(objectClass=person)(samaccountname=$login))";
            $basedn=array('ou=UserRoot,DC=pwr,DC=mcs,DC=br','ou=UserRTwo,DC=pwr,DC=mcs,DC=br','ou=UserOther,DC=pwr,DC=mcs,DC=br');
            $info=$this->RunQuery($basedn,$filter);
        }
        return $info[0]['company'][0];
    }

    public function Macro($str){
        $ldap=new LDAP();
        $str=str_replace('@department@',$ldap->GetLoginParam('COMPANY'),$str);
        $str=str_replace('@user@',$ldap->GetLoginParam('FIO'),$str);
        return $str;
    }
	
    public function GetAllDepartments()
    {
		$stuff=array();
		if($this->bind)
		{
			$departments=array();
            //только незаблокированные пользователи
            $filter = "(&(objectClass=user)(objectClass=person)(!(useraccountcontrol:1.2.840.113556.1.4.803:=2)))";
			$basedn=array('ou=UserRoot,DC=pwr,DC=mcs,DC=br','ou=UserRTwo,DC=pwr,DC=mcs,DC=br','ou=UserOther,DC=pwr,DC=mcs,DC=br');
			$info=$this->RunQuery($basedn,$filter);
				foreach($info as $user){
					if (is_array($user)&&(!empty($user))) 
						array_push($stuff,$user['company']);
				}
			$empty=array("");
			$departments=array_unique($stuff);
			$departments=array_diff($departments,$empty);
		}
		return $departments;
    }

    public function GetAllUsers()
    {
        $stuff=array();
        if($this->bind)
        {
            $departments=array();
            //только незаблокированные пользователи
            $filter = "(&(objectClass=user)(objectClass=person))";
            $basedn=array('ou=_DisableUser,DC=pwr,DC=mcs,DC=br','ou=UserRoot,DC=pwr,DC=mcs,DC=br','ou=UserRTwo,DC=pwr,DC=mcs,DC=br','ou=UserOther,DC=pwr,DC=mcs,DC=br');
            $info=$this->RunQuery($basedn,$filter);
        }
        $result = array();
        for($i = 0; $i < sizeof($info);$i++)
        {
            $key = strtoupper($info[$i]['samaccountname']);
            $result[$key] = $info[$i]['displayname'];
        }
        return $result;
    }
	
	//получение списка (возвращается только первые 1000 записей)
	public function RunQuery($basedn,$filter){	
		if ($this->bind){
			if (!is_array($basedn)){
				$basedn=array($basedn);
			}
			//поля вывода (логин,тел,почта,фио,долность,отдел,коммитет)
			$atributes=array('samaccountname','telephonenumber','mail','displayname', 'title', 'department', 'company');
			
			if (is_array($basedn)){
				$connections=array();
				for($i=0;$i<count($basedn);$i++){
					array_push($connections,$this->link);
				}
			}
			else{
				$connections=$this->link;
			}
			
			$sr = ldap_search($connections,$basedn, $filter,$atributes) ;
			$info=array();
			for($i=0;$i<count($sr);$i++){
					if (!$sr[$i]) die('кривой фильтр');
					$info=array_merge($info,ldap_get_entries($this->link,$sr[$i]));
			}
		}
		$entries=array();
		foreach($info as $entry_data){
			if (!is_array($entry_data)) continue;
			if (array_key_exists('samaccountname',$entry_data))
				$ed['samaccountname']=$entry_data['samaccountname'][0];
			else 
				$ed['samaccountname']="";
			if (array_key_exists('displayname',$entry_data))	
				$ed['displayname']=$entry_data['displayname'][0];
			else
				$ed['displayname']="";
			if (array_key_exists('company',$entry_data))
            {
                $ed['company']=$entry_data['company'][0];
            }
			else
				$ed['company']="";
			if (array_key_exists('department',$entry_data))	
				$ed['department']=$entry_data['department'][0];
			else
				$ed['department']="";	
			if (array_key_exists('title',$entry_data))	
				$ed['title']=$entry_data['title'][0];
			else
				$ed['title']="";
			if (array_key_exists('telephonenumber',$entry_data))	
				$ed['phone']=$entry_data['telephonenumber'][0];
			else
				$ed['phone']="";			
			if (array_key_exists('mail',$entry_data))	
				$ed['mail']=$entry_data['mail'][0];
			else
				$ed['mail']="";
			array_push($entries,$ed);
		}
		return $entries;
	}
	
	// Получаем информацию о пользователе
	public function GetInfo()
	{
		if($this->bind){
			$login=$this->GetLogin();


            $filter = "(&(samaccountname=$login)(objectClass=user)(objectClass=person))";
            $basedn=array('ou=UserRoot,DC=pwr,DC=mcs,DC=br','ou=UserRTwo,DC=pwr,DC=mcs,DC=br','ou=UserOther,DC=pwr,DC=mcs,DC=br');
            $info = $this->RunQuery($basedn, $filter);
			$person = array();
			$person['FIO'] 			= $info[0]['displayname'];
			$person['TITLE'] 		= $info[0]['title'];
			$person['DEPARTMENT'] 	= $info[0]['department'];
			$person['COMPANY'] 		= $info[0]['company'];
            $person['MAIL'] 		= $info[0]['mail'];
        }
		return $person;
	}


    public function  GetLoginParamByLogin($param, $login)
    {
        $last_login = $this->user_login;
        $this->user_login = $login;
        $person=$this->GetInfo();
        $this->user_login = $last_login;
        if (is_array($person) && array_key_exists($param,$person))
            return $person[$param];
        else
            return('Некорректный параметр');
    }

	public function GetLoginParam($param){
		$person=$this->GetInfo();
		if (is_array($person) && array_key_exists($param,$person))
			return $person[$param];
		else
			return('Некорректный параметр');
	}

    ///////////////////////
    //Тестовый кусок кода//
    ///////////////////////

    public function getDepartmentsAndSections($department_filter = "")
    {
        $stuff="";
        if($this->bind)
        {
            $departments=array();
            //только незаблокированные пользователи
            $filter = "(&(objectClass=user)(objectClass=person)(!(useraccountcontrol:1.2.840.113556.1.4.803:=2)))";
            $basedn=array('ou=UserRoot,DC=pwr,DC=mcs,DC=br','ou=UserRTwo,DC=pwr,DC=mcs,DC=br','ou=UserOther,DC=pwr,DC=mcs,DC=br');
            $info=$this->RunQuery($basedn,$filter);
            foreach($info as $user){
                if (is_array($user)&&(!empty($user)))
                {
                    $company = trim($user['company']);
                    if (empty($company))
                        continue;
                    if ((!empty($department_filter)) && ($department_filter != $company))
                        continue;
                    if (!array_key_exists($company, $stuff))
                        $stuff[$company] = array();
                    $department = trim($user['department']);
                    if (empty($department))
                        continue;
                    if (!array_key_exists($department, $stuff[$company]))
                        $stuff[$company][$department] = 1;
                    ksort($stuff[$company]);
                }
            }
        }
        ksort($stuff);
        return $stuff;
    }

    public function getDepartments()
    {
        $stuff="";
        if($this->bind)
        {
            $departments=array();
            //только незаблокированные пользователи
            $filter = "(&(objectClass=user)(objectClass=person)(!(useraccountcontrol:1.2.840.113556.1.4.803:=2)))";
            $basedn=array('ou=UserRoot,DC=pwr,DC=mcs,DC=br','ou=UserRTwo,DC=pwr,DC=mcs,DC=br','ou=UserOther,DC=pwr,DC=mcs,DC=br');
            $info=$this->RunQuery($basedn,$filter);
            foreach($info as $user){
                if (is_array($user)&&(!empty($user)))
                {
                    $company = trim($user['company']);
                    if (empty($company))
                        continue;
                    $stuff[$company] = 1;
                }
            }
        }
        ksort($stuff);
        return $stuff;
    }
}