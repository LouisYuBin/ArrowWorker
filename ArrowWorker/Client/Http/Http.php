<?php
/**
 * By yubin at 2019/4/22 10:55 AM.
 */

namespace ArrowWorker\Client\Http;

use Swoole\Coroutine\Http\Client;

/**
 * Class Http
 */
class Http
{
    /**
     *
     */
    public const ERROR_URL = -200;

    /**
     *
     */
    public const ERROR_MSG = 'url incorrect';

    /**
     * @var string
     */
    private $host = '127.0.0.1';

    /**
     * @var int
     */
    private $port = 80;

    /**
     * @var string
     */
    private $path = '/';

    /**
     * @var array
     */
    private $header = [];


    /**
     * @var null|Client
     */
    private $client = null;

    /**
     * @var string
     */
    private $scheme = 'http';

    /**
     * @var int
     */
    private $statusCode = 200;

    /**
     * @var int
     */
    private $responseMsg = '';

    /**
     * Http constructor.
     *
     * @param string $url
     * @param int $timeout
     */
    public function __construct(string $url, int $timeout = 1)
    {
        $this->parseUrl($url);
        if ($this->statusCode === static::ERROR_URL) {
            return;
        }
        $this->client = new Client($this->host, $this->port, $this->scheme === 'https');
        $this->header = [
            'Host' => in_array($this->port, [80, 443]) ? $this->host : "{$this->host}:{$this->port}",
        ];
    }

    /**
     * @param string $url
     */
    private function parseUrl(string $url): void
    {
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme'],$parsedUrl['host'])) {
            $this->statusCode  = self::ERROR_URL;
            $this->responseMsg = self::ERROR_MSG;

            return;
        }

        $this->port   = isset($parsedUrl['port']) && $parsedUrl['scheme'] === 'https' ? 443 : 80;
        $this->path   = $parsedUrl['path'] ?? $this->path;
        $this->scheme = $parsedUrl['scheme'];
        $this->host   = $parsedUrl['host'];

    }

    /**
     * @param array $data
     * @param string $path
     *
     * @return array
     */
    public function Post(array $data, string $path = ''): array
    {
        if ($this->statusCode === static::ERROR_URL) {
            return $this->errorUrl();
        }
        $this->client->setHeaders($this->header);
        $this->client->Post($path === '' ? $this->path : $path, $data);

        return $this->response();
    }

    /**
     * @param string $path
     *
     * @return array
     */
    public function Get(string $path = ''): array
    {
        if ($this->statusCode === static::ERROR_URL) {
            return $this->errorUrl();
        }
        $this->client->setHeaders($this->header);
        $this->client->get($path === '' ? $this->path : $path);

        return $this->response();
    }

    /**
     * @param array $header
     *
     * @return $this
     */
    public function Header(array $header): self
    {
        $this->header = array_merge($this->header, $header);

        return $this;
    }

    /**
     * @param array $files =['formKeyName'=>'file path']
     *
     * @return $this
     */
    public function AddFile(array $files): self
    {
        foreach ($files as $formKeyName => $fileInfo) {
            if (!is_array($fileInfo) && !isset($fileInfo['path'])) {
                continue;
            }

            $this->client->addFile(
                $fileInfo['path'], 
                $formKeyName, 
                $fileInfo['mimeType'] ?? null,  
                $fileInfo['filename'] ?? null, 
                isset($fileInfo['offset']) ? (int)$fileInfo['offset'] : 0, 
                isset($fileInfo['length']) ?
                (int)$fileInfo['length'] : -1
            );
        }

        return $this;
    }

    /**
     * @return array
     */
    private function response(): array
    {
        return [
            'httpCode' => $this->client->statusCode, 'data' => (string)$this->client->body,
        ];
    }

    /**
     * @return array
     */
    private function errorUrl(): array
    {
        return [
            'httpCode' => static::ERROR_URL, 'data' => 'request url is incorrect.',
        ];
    }


    /**
     * @param string $method
     *
     * @return $this
     */
    public function Method(string $method): self
    {
        $this->client->setMethod($method);

        return $this;
    }


    /**
     * @param array $cookies
     *
     * @return $this
     */
    public function Cookies(array $cookies): self
    {
        $this->client->setCookies($cookies);

        return $this;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function SetData(array $data): self
    {
        $this->client->setData($data);

        return $this;
    }

    /**
     * @param string $path
     *
     * @return array
     */
    public function Execute(string $path): array
    {
        $this->client->execute($path);

        return [
            'httpCode' => $this->client->statusCode, 'data' => (string)$this->client->body,
        ];
    }

    /**
     *
     */
    public function Close(): void
    {
        $this->client->close();
        unset($this->client);
    }

}

