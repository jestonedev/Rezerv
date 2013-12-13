<?php

include_once "const.php";
include_once "ldap.php";

class SMTP
{
    private $server_socket;

    private function fatal_error ( $sErrorMessage = '' )
    {
        header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error ' );
        die( $sErrorMessage );
    }

    public function __construct()
    {
        $this->server_socket = fsockopen(SMTP_SERVER, SMTP_PORT) or $this->fatal_error('Не могу соединиться с сервером');
        $smtp_msg = '';
        while ($line = fgets($this->server_socket, 515)) {
            $smtp_msg .= $line;
            if (substr($line, 3, 1) == " ") break;
        }
        // приняли ответ сервера, если он начинается на 220 - значит всё ок, сервер работает
        $answer = substr($smtp_msg, 0, 3);
        if($answer != '220') $this->fatal_error($smtp_msg);
    }

    public function __destruct()
    {
        fclose($this->server_socket);
    }

    private function SendMessage($socket, $cmd)
    {
        $smtp_msg  = "";
        $smtp_code = "";
        fputs( $socket, $cmd."\r\n" );
        while ($line = fgets($socket, 515)) {
            $smtp_msg .= $line;
            if (substr($line, 3, 1) == " ") break;
        }
        $smtp_code = substr( $smtp_msg, 0, 3 );
        return $smtp_code=="" ? false : $smtp_code;
    }

    private function SendHelo()
    {
        // посылаем серверу приветствие и адрес клиента (в данном случае клиентом является web-сервер)
        $answer = $this->SendMessage($this->server_socket, 'HELO '.$_SERVER["REMOTE_ADDR"]);
        if($answer != '250') $this->fatal_error('Ошибка '.$answer.' при отправке команды HELO');
    }

    private function SendAuthInfo()
    {
        $answer = $this->SendMessage($this->server_socket, 'AUTH LOGIN');
        if($answer != '334') $this->fatal_error('Ошибка '.$answer.' при отправке команды AUTH LOGIN');
        // если сервер работает через smtp авторизацию на исходящие, посылаем ему логин от ящика $smtp_user
        $answer = $this->SendMessage($this->server_socket, base64_encode(SMTP_LOGIN));
        if($answer != '334') $this->fatal_error('Ошибка '.$answer.' при отправке логина');
        // и пароль $smtp_pass
        $answer = $this->SendMessage($this->server_socket, base64_encode(SMTP_PASSWORD));
        if($answer != '235') $this->fatal_error('Ошибка '.$answer.' при отправке пароля');
    }

    private function SendFrom($from)
    {
        $answer = $this->SendMessage($this->server_socket, 'MAIL FROM:'.$from);
        if($answer != '250') $this->fatal_error('Ошибка '.$answer.' при отправке команды MAIL FROM');
    }

    private function SendTo($to)
    {
        $answer = $this->SendMessage($this->server_socket, 'RCPT TO:'.$to);
        if($answer != '250') $this->fatal_error('Ошибка '.$answer.' при отправке команды RCPT TO');
    }

    private function SendData($from, $to, $subject, $content)
    {
        // сообщаем, что начинаем вводить данные:
        $answer = $this->SendMessage($this->server_socket, "DATA");
        if($answer != '354') $this->fatal_error('Ошибка '.$answer.' при отправке команды DATA');
        // собственно сам процесс введения данных:
        $data = 'Subject: '.$subject;
        fputs($this->server_socket, $data."\r\n");
        $data = 'From: Служба сервиса подачи заявок';
        fputs($this->server_socket, $data."\r\n");
        $data = 'To: <'.$to.'>';
        fputs($this->server_socket, $data."\r\n");
        $data = 'Content-Type: text/html; charset="utf-8"';
        fputs($this->server_socket, $data."\r\n");
        $data = $content;
        fputs($this->server_socket, $data."\r\n");
        // говорим, что закончили посылать данные:
        $answer = $this->SendMessage($this->server_socket, ".");
        if($answer != '250') die ('Не удалось отослать команду .');
    }

    private function SendQuit()
    {
        $answer = $this->SendMessage($this->server_socket, "QUIT");
        if($answer != '221') die ('Не удалось отослать команду QUIT');
    }

    public function Connect()
    {
        $this->SendHelo();
        $this->SendAuthInfo();
    }

    public function SendEmail($from, $to, $subject, $content)
    {
        $this->SendFrom($from);
        $this->SendTo($to);
        $this->SendData($from, $to, $subject, $content);
    }

    public function Quit()
    {
        $this->SendQuit();
    }
}
