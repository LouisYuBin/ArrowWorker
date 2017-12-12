<?php
class Pipe
{
    /**
     * @var resource
     */
    protected $read;

    /**
     * @var resource
     */
    protected $write;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var bool
     */
    protected $block;

    /**
     * @param string $filename fifo filename
     * @param int $mode
     * @param bool $block if blocking
     */
    public function __construct($filename = '/home/louis/github/ArrowWorker/App/Runtime/ArrowWorker.pipe', $mode = 0666, $block = false)
    {
       /* if (!file_exists($filename) && !posix_mkfifo($filename, $mode)) {
            throw new \RuntimeException("create pipe failed");
        }
        if (filetype($filename) != "fifo") {
            throw new \RuntimeException("file exists and it is not a fifo file");
        }*/

        $this->filename = $filename;
        $this->block = $block;
        $this -> _setSignalHandler('monitorHandler');
    }

    private function _setSignalHandler($type = 'parentsQuit',$lifecycle=0)
    {
        switch($type)
        {
            case 'workerHandler':
                pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGALRM, array(__CLASS__, "signalHandler"),false);
                pcntl_alarm($lifecycle);
                break;
            default:
                pcntl_signal(SIGCHLD, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGINT, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGQUIT, array(__CLASS__, "signalHandler"),false);
        }
    }

    public function signalHandler($signal)
    {
        echo "signalHandler";
        echo $signal;
        switch($signal)
        {
            case SIGUSR1:
            case SIGALRM:
                self::$terminate = true;
            case SIGTERM:
            case SIGHUP:
            case SIGINT:
            case SIGQUIT:
                echo "quit";
                //exit(0);
                break;
            default:
                return false;
        }

    }

    public function setBlock($block = true)
    {
        if (is_resource($this->read)) {
            $set = stream_set_blocking($this->read, $block);
            if (!$set) {
                throw new \RuntimeException("stream_set_blocking failed");
            }
        }

        if (is_resource($this->write)) {
            $set = stream_set_blocking($this->write, $block);
            if (!$set) {
                throw new \RuntimeException("stream_set_blocking failed");
            }
        }

        $this->block = $block;
    }

    /**
     * if the stream is blocking, you would better set the value of size,
     * it will not return until the data size is equal to the value of param size
     *
     * @param int $size
     * @return string
     */
    public function read($size = 1024)
    {
        if (!is_resource($this->read)) {
            $this->read = fopen($this->filename, 'r+');
            if (!is_resource($this->read)) {
                throw new \RuntimeException("open file failed");
            }
            if (!$this->block) {
                $set = stream_set_blocking($this->read, false);
                if (!$set) {
                    throw new \RuntimeException("stream_set_blocking failed");
                }
            }
        }

        return fread($this->read, $size);
    }

    /**
     * @param $message
     * @return int
     */
    public function write($message)
    {
        if (!is_resource($this->write)) {
            $this->write = fopen($this->filename, 'w+');
            if (!is_resource($this->write)) {
                throw new \RuntimeException("open file failed");
            }
            if (!$this->block) {
                $set = stream_set_blocking($this->write, false);
                if (!$set) {
                    throw new \RuntimeException("stream_set_blocking failed");
                }
            }
        }

        return fwrite($this->write, $message);
    }

    /**
     *
     */
    public function close()
    {
        if (is_resource($this->read)) {
            fclose($this->read);
        }
        if (is_resource($this->write)) {
            fclose($this->write);
        }
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->close();
    }

    public function remove()
    {
        return unlink($this->filename);
    }
}
global $terminal;
$terminal = false;
global  $isWorking;
$isWorking = false;


function signalHandler($signal)
{
    global $terminal;
    echo $signal;
    switch($signal)
    {
        case SIGUSR1:
        case SIGALRM:
            echo "fuck";
         $terminal = true;
         break;
        case SIGTERM:
        case SIGHUP:
        case SIGINT:
        case SIGQUIT:
            $isWorking=false;
        $terminal = true;
            echo "quit";
            //exit(0);
            break;
        default:
            return false;
    }

}

$pipe = new Pipe();
$pipe->write(str_pad("test1",1024));
$pipe->write(str_pad("test2",1024));
$pipe->write(str_pad("test3",1024));
$pid = pcntl_fork();
pcntl_signal(SIGCHLD,"signalHandler",false);
pcntl_signal(SIGTERM, "signalHandler",false);
pcntl_signal(SIGINT, "signalHandler",false);
pcntl_signal(SIGQUIT,"signalHandler",false);
pcntl_signal(SIGUSR1,"signalHandler",false);
pcntl_signal_dispatch();

$redis = new \Redis();
$redis ->connect("127.0.0.1",6379);
$redis ->auth("louis");

if($pid == 0){
    pcntl_signal_dispatch();
    sleep(10);
    $pipe->write(str_pad("test1",128));
    $pipe->write(str_pad("test2",128));
    $pipe->write(str_pad("test3",128));
/*    $result = $redis->lPush("louis","1");
    $result = $redis->lPush("louis","2");
    $result = $redis->lPush("louis","3");
    $result = $redis->lPush("louis","4");
    sleep(10);
    $result = $redis->lPush("louis","5");
    $result = $redis->lPush("louis","6");
    $result = $redis->lPush("louis","7");
    $result = $redis->lPush("louis","8");
    sleep(5);
    $result = $redis->lPush("louis","9");
    $result = $redis->lPush("louis","10");*/
    pcntl_signal_dispatch();
    echo PHP_EOL."child".PHP_EOL;
}else{
    pcntl_signal_dispatch();
    //$pipe->setBlock(true);
    while(1) {
        if($terminal == true){
            break;
        }
        global $isWorking;
        $isWorking = true;
        pcntl_signal_dispatch();
        $pipe->setBlock(true);
        $result = $pipe->read(128);
        var_dump($result);
        //echo $result . PHP_EOL;
        //$result = $redis->brPop(["louis"],5);
        //var_dump($result);

        pcntl_signal_dispatch();
        echo "extra work".PHP_EOL;
    }
    echo PHP_EOL."parent".PHP_EOL;
}


