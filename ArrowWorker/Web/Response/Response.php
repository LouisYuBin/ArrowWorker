<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Web\Response;

use ArrowWorker\Library\Context;
use ArrowWorker\Log\Log;
use ArrowWorker\Web\ResponseInterface;

/**
 * Class Response
 * @package ArrowWorker
 */
class Response implements ResponseInterface
{

    /**
     * @var bool
     */
    private bool $isAllowCORS = false;

    /**
     * Response constructor.
     * @param bool $isAllowCORS
     */
    public function __construct(bool $isAllowCORS)
    {
        $this->isAllowCORS = $isAllowCORS;
    }

    /**
     * @return bool
     */
    public function getCORS(): bool
    {
        return $this->isAllowCORS;
    }

    /**
     * @param int $code
     * @param array $data
     * @param string $msg
     */
    public function writeJson(int $code, array $data = [], string $msg = ''):void
    {
        $this->setHeader("content-type", "application/json;charset=utf-8");
        $this->Write(json_encode([
            'code' => $code,
            'data' => $data,
            'msg'  => $msg,
        ]));
    }

    /**
     * @param string $msg
     */
    public function Write(string $msg):void
    {
        if ($this->isAllowCORS) {
            $this->setCorsHeader();
        }
        $this->setHeader('Server', 'Arrow, Louis!');
        Context::Get(__CLASS__)->end($msg);
        Log::Debug("Response : {$msg}", [], __METHOD__);
    }

    /**
     * @param string $key
     * @param string $val
     * @return void
     */
    public function setHeader(string $key, string $val):void
    {
        Context::Get(__CLASS__)->header($key, $val);
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status):void
    {
        Context::Get(__CLASS__)->status($status);
    }

    /**
     * @param array $data
     * @return void
     */
    public function setHeaders(array $data):void
    {
        $response = Context::Get(__CLASS__);
        foreach ($data as $key => $val) {
            $response->header($key, $val);
        }
    }


    /**
     * @param string $name
     * @param string $val
     * @param int $expire
     * @param string $path
     * @param string|null $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return void
     */
    public function setCookie(string $name, string $val, int $expire = 0, string $path = '/', string $domain = null, bool $secure = false, bool $httpOnly = true):void
    {
        $expire = ($expire == 0) ? 0 : time() + $expire;
        Context::Get(__CLASS__)->cookie($name, $val, $expire, $path, $domain, $secure, $httpOnly);
    }

    /**
     *
     */
    public function setCorsHeader():void
    {
        $this->setHeaders([
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Headers' => 'Origin,X-Requested-With,x_requested_with,Content-Type,Accept',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS',
        ]);
    }

}