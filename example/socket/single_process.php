<?php
/**
 * 单进程阻塞式--同时只能处理一个连接
 */
class Xtgxiso_server
{
    public $socket = false;
    public $onConnect = null;
    public $onMessage = null;
    public $onClose = null;

    function __construct($host="0.0.0.0",$port=1215)
    {
        $this->socket = stream_socket_server("tcp://".$host.":".$port,$errno, $errstr);
        if (!$this->socket) die($errstr."--".$errno);
    }

    public function run(){
        while ( 1 ) {
            echo  "waiting...\n";
            $conn = stream_socket_accept($this->socket, -1);
            if ( !$conn ){
                continue;
            }
            if($this->onConnect)
            {
                call_user_func($this->onConnect, $conn);
            }
            $receive = '';
            $buffer = '';
            while ( 1 ) {
                $buffer = fread($conn, 3);
                if($buffer === '' || $buffer === false)
                {
                    if ( $this->onClose ){
                        call_user_func($this->onClose, $conn);
                    }
                    break;
                }
                $pos = strpos($buffer, "\n");
                if($pos === false)
                {
                    $receive .= $buffer;
                    //echo "received:".$buffer.";not all package,continue recdiveing\n";
                }else{
                    $receive .= trim(substr($buffer,0,$pos+1));
                    $buffer = substr($buffer,$pos+1);
                    if($this->onMessage)
                    {
                        call_user_func($this->onMessage, $conn, $receive);
                    }
                    switch ( $receive ){
                        case "quit":
                            echo "client close conn\n";
                            fclose( $conn );
                            break 2;
                        default:
                            //echo "all package:\n";
                            //echo $receive."\n";
                            break;
                    }
                    $receive = '';
                }
            }
        }
        fclose($this -> socket);
    }
}
$server =  new Xtgxiso_server();

$server -> onConnect = function($conn){
    echo "onConnect -- accepted " . stream_socket_get_name($conn,true) . "\n";
    fwrite($conn,"conn success\n");
};

$server -> onMessage = function($conn,$msg){
    echo "onMessage --" . $msg . "\n";
    fwrite($conn,"received ".$msg."\n");
};

$server -> onClose = function($conn){
    echo "onClose --" . stream_socket_get_name($conn,true) . "\n";
    fwrite($conn,"onClose "."\n");
};

$server->run();