<?php
/**
 * By yubin at 2020/7/5 7:05 下午.
 */

namespace ArrowWorker\Web;


/**
 * Interface ResponseInterface
 * @package ArrowWorker\Web\Response
 */
interface ResponseInterface
{


    /**
     * @return bool
     */
    public function getCORS(): bool;

    /**
     * @param int $code
     * @param array $data
     * @param string $msg
     */
    public function writeJson(int $code, array $data = [], string $msg = ''): void;

    /**
     * @param string $msg
     */
    public function Write(string $msg): void;

    /**
     * @param string $key
     * @param string $val
     * @return void
     */
    public function setHeader(string $key, string $val): void;

    /**
     * @param int $status
     */
    public function setStatus(int $status): void;

    /**
     * @param array $data
     * @return void
     */
    public function setHeaders(array $data): void;


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
    public function setCookie(string $name, string $val, int $expire = 0, string $path = '/', string $domain = null, bool $secure = false, bool $httpOnly = true): void;


    /**
     * @return void
     */
    public function setCorsHeader(): void;
}