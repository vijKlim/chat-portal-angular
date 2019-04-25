<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 10.09.2015
 * Time: 16:05
 */

class WSocketConnection extends CApplicationComponent{

    public $servers;
    protected $_instances = [];
    public function getInstance($server) {
        if (!isset($this->_instances[$server])) {
            $this->_instances[$server] = stream_socket_client ($this->servers[$server]['localsocket'], $errno, $errstr);//соединямся с мастер-процессом:
        }
        return $this->_instances[$server];
    }
    public function send($message, $server = null) {
        if (!$server) {
            reset($this->servers);
            $server = key($this->servers);
        }
        return fwrite($this->getInstance($server), $message . "\n");
    }
} 