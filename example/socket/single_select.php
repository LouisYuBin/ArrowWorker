<?php
/**
 * 单进程IO复用select
 * 同时处理多个连接
 */
class Xtgxiso_server
{
    public $socket = false;
    public $master = array();
    public $onConnect = null;
    public $onMessage = null;
    public $onClose = null;

    function __construct($host="0.0.0.0",$port=1215)
    {
        $this->socket = stream_socket_server("tcp://".$host.":".$port,$errno, $errstr);
        if (!$this->socket) die($errstr."--".$errno);
        stream_set_blocking($this->socket,0);
        $id = (int)$this->socket;
        $this->master[$id] = $this->socket;
    }

    public function run(){
        $read = $this->master;
        $receive = array();
        echo  "start run...\n";
        while ( 1 ) {
            $read = $this->master;
            //echo  "waiting...\n";
            $mod_fd = @stream_select($read, $_w = NULL, $_e = NULL, 60);
            if ($mod_fd === FALSE) {
                break;
            }
            foreach ( $read as $k => $v ) {
                if ( $v === $this->socket ) {
                    //echo "new conn\n";
                    $conn = stream_socket_accept($this->socket);
                    if($this->onConnect) {
                        call_user_func($this->onConnect, $conn);
                    }
                    $id = (int)$conn;
                    $this->master[$id] = $conn;
                } else {
                    //echo "read data\n";
                    if ( !isset($receive[$k]) ){
                        $receive[$k]="";
                    }
                    $buffer = fread($v, 10);
                    //echo $buffer."\n";
                    if ( strlen($buffer) === 0 ) {
                        if ( $this->onClose ){
                            call_user_func($this->onClose,$v);
                        }
                        fclose($v);
                        $id = (int)$v;
                        unset($this->master[$id]);
                    } else if ( $buffer === FALSE ) {
                        if ( $this->onClose ){
                            call_user_func($this->onClose, $this->master[$key_to_del]);
                        }
                        fclose($v);
                        $id = (int)$v;
                        unset($this->master[$id]);
                    } else {
                        $pos = strpos($buffer, "\n");
                        if ( $pos === false) {
                            $receive[$k] .= $buffer;
                            //echo "received:".$buffer.";not all package,continue recdiveing\n";
                        }else{
                            $receive[$k] .= trim(substr ($buffer,0,$pos+1));
                            $buffer = substr($buffer,$pos+1);
                            if($this->onMessage) {
                                call_user_func($this->onMessage,$v,$receive[$k]);
                            }
                            switch ( $receive[$k] ){
                                case "quit":
                                    echo "client close conn\n";
                                    fclose($v);
                                    $id = (int)$v;
                                    unset($this->master[$id]);
                                    break;
                                default:
                                    //echo "all package:\n";
                                    //echo $receive[$k]."\n";
                                    break;
                            }
                            $receive[$k]='';
                        }
                    }
                }
            }
            usleep(10000);
        }
    }
}
$server =  new Xtgxiso_server();

$server->onConnect = function($conn){
    echo "onConnect -- accepted " . stream_socket_get_name($conn,true) . "\n";
    fwrite($conn,"conn success\n");
};

$server->onMessage = function($conn,$msg){
    echo "onMessage --" . $msg . "\n";
    fwrite($conn,"received ".$msg."\n");
};

$server->onClose = function($conn){
    echo "onClose --" . stream_socket_get_name($conn,true) . "\n";
    fwrite($conn,"onClose "."\n");
};

$server->run();