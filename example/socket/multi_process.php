<?php
/**
 * 多进程阻塞式--一个master进程，两个worker进程.
 * 其中一个进程挂掉自动启动新的
 * 同时处理的连接数受限于启动的进程数
 */
class Xtgxiso_server
{
    public $socket = false;
    public $onConnect = null;
    public $onMessage = null;
    public $onClose = null;
    public $process_num = 2;
    private $pids = array();

    function __construct($host="0.0.0.0",$port=1215){
        //产生子进程分支
        $pid = pcntl_fork();
        if ($pid == -1) {
            die("could not fork"); //pcntl_fork返回-1标明创建子进程失败
        } else if ($pid) {
            exit(); //父进程中pcntl_fork返回创建的子进程进程号
        } else {
            // 子进程pcntl_fork返回的时0
        }
        // 从当前终端分离
        if (posix_setsid() == -1) {
            die("could not detach from terminal");
        }
        umask(0);
        $this->socket = stream_socket_server("tcp://".$host.":".$port,$errno, $errstr);
        if (!$this->socket) die($errstr."--".$errno);
    }

    private function start_worker_process(){
        $pid = pcntl_fork();
        switch ($pid) {
            case -1:
                echo "fork error : {$pid} \r\n";
                exit;
            case 0:
                while ( 1 ) {
                    echo  "waiting...\n";
                    $conn = stream_socket_accept($this->socket, -1);
                    if($this->onConnect) {
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
                        if($pos === false) {
                            $receive .= $buffer;
                            //echo "received:".$buffer.";not all package,continue recdiveing\n";
                        }else{
                            $receive .= trim(substr ($buffer,0,$pos+1));
                            $buffer = substr($buffer,$pos+1);
                            if($this->onMessage) {
                                call_user_func($this->onMessage, $conn, $receive);
                            }
                            switch ( $receive ){
                                case "quit":
                                    echo "client close conn\n";
                                    fclose($conn);
                                    break 2;
                                default:
                                    //echo "all package:\n";
                                    //echo $receive."\n";
                                    break;
                            }
                            $receive='';
                        }
                    }
                }
            default:
                $this->pids[$pid] = $pid;
                break;
        }
    }

    public function run(){

        for($i = 1; $i <= $this->process_num; $i++){
            $this->start_worker_process();
        }

        while(1){
            foreach ($this->pids as $i => $pid) {
                if($pid) {
                    $res = pcntl_waitpid($pid, $status,WNOHANG);

                    if ( $res == -1 || $res > 0 ){
                        $this->start_worker_process();
                        unset($this->pids[$pid]);
                    }
                }
            }
            sleep(1);
        }
    }

    function __destruct() {
        @fclose($this->socket);
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