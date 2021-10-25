<?php

namespace ArrowWorker\Component\Channel;

use ArrowWorker\Log\Log;

/**
 * Class Queue
 * @package ArrowWorker\Component\Channel
 */
class Queue
{

    /**
     *
     */
    private const MODE = 0666;

    /**
     * @var resource
     */
    private $queue;

    /**
     * @var array
     */
    private array $config;

    /**
     * @var string
     */
    private string $chanFileDir = APP_PATH . DIRECTORY_SEPARATOR . APP_RUNTIME_DIR . DIRECTORY_SEPARATOR . 'Chan/';


    /**
     * Queue constructor.
     * @param array $config
     * @param string $name
     */
    public function __construct(array $config, string $name)
    {
        $this->config = $config;
        $chanFile     = $this->chanFileDir . $name . '.chan';
        if (!file_exists($chanFile)) {
            if (!touch($chanFile)) {
                Log::DumpExit("touch chan file failed ({$chanFile}).");
            }
        }
        $key         = ftok($chanFile, 'A');
        $this->queue = msg_get_queue($key, self::MODE);
        if (!$this->queue) {
            Log::DumpExit("msg_get_queue({$key}," . self::MODE . ") failed");
        }
        msg_set_queue($this->queue, ['msg_qbytes' => $this->config['bufSize']]);
    }

    /**
     * @param array $config
     * @param string $name
     * @return Queue
     */
    public static function init(array $config, string $name): Queue
    {
        return new self($config, $name);
    }

    /**
     * Write  写入消息
     * @param     $message
     * @param int $msgType 消息类型
     * @return bool
     * @author Louis
     */
    public function write($message, int $msgType = 1): bool
    {
        for ($i = 0; $i < 3; $i++) {
            if (@msg_send($this->queue, $msgType, $message, true, true, $errorCode)) {
                return true;
            }
        }

        Log::Dump(" msg_send failed. error code : {$errorCode}, data : {$message}", Log::TYPE_WARNING, __METHOD__);
        return false;
    }

    /**
     * Status  获取队列状态
     * @return array
     */
    public function status(): array
    {
        return msg_stat_queue($this->queue);
    }

    /**
     * Read 写消息
     * @param int $waitSecond seconds to wait while there is no message in channel
     * @param int $msgType message type to be read
     * @return bool|string
     * @author Louis
     */
    public function Read(int $waitSecond = 500, int $msgType = 1)
    {
        $result = msg_receive(
            $this->queue,
            $msgType,
            $messageType,
            $this->config['msgSize'],
            $message,
            true,
            MSG_IPC_NOWAIT,
            $errorCode
        );
        if (!$result && MSG_ENOMSG === $errorCode) {
            usleep($waitSecond);
        }
        return $result ? $message : $result;
    }

    /**
     * @return int
     */
    public function Close()
    {
        return (int)msg_remove_queue($this->queue);
    }

    /**
     *__destruct
     */
    public function __destruct()
    {
        //$this->Close();
    }

}

