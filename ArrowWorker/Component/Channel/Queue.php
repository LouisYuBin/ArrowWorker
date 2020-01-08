<?php

namespace ArrowWorker\Component\Channel;

use ArrowWorker\Log;

class Queue
{
	
    const MODE = 0666;
    
    const MODULE_NAME = 'Queue';

    private $_queue;

    private $_config = [];

    private $_chanFileDir = APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.DIRECTORY_SEPARATOR.'Chan/';


    private function __construct(array $config, string $name)
    {
        $this->_config = $config;
        $chanFile = $this->_chanFileDir.$name.'.chan';
		if (!file_exists($chanFile))
		{
		    if( !touch($chanFile) )
            {
                Log::DumpExit("touch chan file failed ({$chanFile}).");
            }
		}
		$key   = ftok($chanFile, 'A');
		$this->_queue = msg_get_queue($key, self::MODE);
		if( !$this->_queue )
        {
            Log::DumpExit("msg_get_queue({$key},0666) failed");
        }
        msg_set_queue($this->_queue, ['msg_qbytes'=>$this->_config['bufSize']]);
    }

    public static function Init(array $config, string $name) : Queue
    {
        return new self($config, $name);
    }

    /**
     * Write  写入消息
     * @author Louis
     * @param     $message
     * @param int $msgType 消息类型
     * @return bool
     */
    public function Write( $message, int $msgType=1 ) : bool
    {
        for( $i=0; $i<3; $i++)
        {
            if( @msg_send( $this->_queue, $msgType, (string)$message,false, true, $errorCode) )
            {
                return true;
            }
        }

        Log::Dump(__CLASS__.'::'.__METHOD__." msg_send failed. error code : {$errorCode}, data : {$message}", Log::TYPE_WARNING, self::MODULE_NAME);
        return false;
	}

    /**
     * Status  获取队列状态
     * @return array
     */
    public function Status()
    {
        return msg_stat_queue( $this->_queue );
    }

    /**
     * Read 写消息
     * @author Louis
     * @param int $waitSecond seconds to wait while there is no message in channel
     * @param int $msgType message type to be read
     * @return bool|string
     */
    public function Read(int $waitSecond=500, int $msgType=1)
    {
		$result = msg_receive(
		    $this->_queue,
            $msgType,
            $messageType,
            $this->_config['msgSize'],
            $message,
            false,
            MSG_IPC_NOWAIT,
            $errorCode
        );
    	if( !$result && MSG_ENOMSG==$errorCode )
        {
            usleep($waitSecond);
        }
		return $result ? $message : $result;
    }

    public function Close()
    {
        return (int)msg_remove_queue($this->_queue);
    }

    /**
     *__destruct
     */
    public function __destruct()
    {
		//$this->Close();
    }

}

