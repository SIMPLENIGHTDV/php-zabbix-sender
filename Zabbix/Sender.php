<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

#require_once('Agent/Config.php');

# Default configuration
if(!defined('ZABBIX_SENDER_DEFAULT_SERVERNAME')) {
    define('ZABBIX_SENDER_DEFAULT_SERVERNAME','localhost',true);
}
if(!defined('ZABBIX_SENDER_DEFAULT_SERVERPORT')) {
    define('ZABBIX_SENDER_DEFAULT_SERVERPORT',10051,true);
}
if(!defined('ZABBIX_SENDER_DEFAULT_CONNECTION_TIMEOUT')) {
    define('ZABBIX_SENDER_DEFAULT_CONNECTION_TIMEOUT',30,true);
}

# ZABBIX Sender Protocol
if(!defined('ZABBIX_SENDER_PROTOCOL_HEADER_STRING')) {
    define('ZABBIX_SENDER_PROTOCOL_HEADER_STRING','ZBXD',true);
}
if(!defined('ZABBIX_SENDER_PROTOCOL_VERSION')) {
    define('ZABBIX_SENDER_PROTOCOL_VERSION',1,true);
}

class Zabbix_Sender {
    
    var $_servername = ZABBIX_SENDER_DEFAULT_SERVERNAME;
    var $_serverport = ZABBIX_SENDER_DEFAULT_SERVERPORT;
    var $_timeout    = ZABBIX_SENDER_DEFAULT_CONNECTION_TIMEOUT;
    var $_data; 

    function Zabbix_Sender($servername = null,$serverport = null)
    {
        if(! is_null($servername)){
            $this->_servername = $servername;
        }
        if(! is_null($serverport)){
            $this->_serverport = $serverport;
        }
        $this->_data = $this->_createDataTemplate(); 
    }

    function _createDataTemplate()
    {
        return array(
            "request" => "sender data",
            "data" => array()
        );
    }

    function importAgentConfig(Zabbix_Agent_Config $agentConfig){
        $config = $agentConfig->getAgentConfig();
        $this->_servername = $config{'Server'}; 
        $this->_serverport = $config{'ServerPort'}; 
    }
    
    function setTimeout($timeout){
        if(intval($timeout) > 0){
            $this->_timeout = $timeout;
        }
        return $this->_timeout;
    }   

    function getTimeout(){
        return $this->_timeout;
    }
 
    function addData($hostname=null,$key=null,$value=null)
    {
        array_push($this->_data{"data"},array("host"=>$hostname,"value"=>$value,"key"=>$key));
    }

    function send()
    {
        $json_data   = json_encode( array_map( function($t){ return is_string($t) ? utf8_encode($t) : $t; }, $this->_data ) );
        $json_length = strlen($json_data);
        $data_header = pack("aaaaCCCCCCCCC",
                                substr(ZABBIX_SENDER_PROTOCOL_HEADER_STRING,0,1),
                                substr(ZABBIX_SENDER_PROTOCOL_HEADER_STRING,1,1),
                                substr(ZABBIX_SENDER_PROTOCOL_HEADER_STRING,2,1),
                                substr(ZABBIX_SENDER_PROTOCOL_HEADER_STRING,3,1),
                                intval(ZABBIX_SENDER_PROTOCOL_VERSION),
                                ($json_length & 0xFF),
                                ($json_length & 0x00FF)>>8,
                                ($json_length & 0x0000FF)>>16,
                                ($json_length & 0x000000FF)>>24,
                                0x00,
                                0x00,
                                0x00,
                                0x00
                            );
        $sock = fsockopen($this->_servername,intval($this->_serverport),$errno,$errmsg,$this->_timeout);
        fputs($sock,$data_header);
        fputs($sock,$json_data);
        fclose($sock);
    }
}


