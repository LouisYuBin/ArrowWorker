<?php
/**
 * By yubin at 2019/4/22 10:55 AM.
 */

namespace ArrowWorker\Lib\Client;

use Swoole\Coroutine\Http\Client;

/**
 * Class Http
 */
class Http
{
    /**
     *
     */
    const ERROR_URL = -200;

    /**
     *
     */
    const ERROR_MSG = 'url incorrect';

    /**
     * @var string
     */
    private $_host = '127.0.0.1';

    /**
     * @var int
     */
    private $_port = 80;

    /**
     * @var string
     */
    private $_path = '/';

    /**
     * @var array
     */
    private $_header = [];


    /**
     * @var null|Client
     */
    private $_client = null;

    /**
     * @var string
     */
    private $_scheme = 'http';

    /**
     * @var int
     */
    private $_statusCode = 200;

    /**
     * @var int
     */
    private $_responseMsg = '';

    /**
     * Http constructor.
     *
     * @param string $url
     * @param int    $timeout
     */
    public function __construct(string $url, int $timeout = 1)
    {
        $this->_parseUrl($url);
        if ($this->_statusCode == static::ERROR_URL)
        {
            return;
        }
        $this->_client = new Client($this->_host, $this->_port, $this->_scheme == 'https' ? true : false);
        $this->_header = [
            'Host' => in_array($this->_port, [80, 443]) ? $this->_host : "{$this->_host}:{$this->_port}",
        ];
    }

    /**
     * @param string $url
     */
    private function _parseUrl(string $url)
    {
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme']) || !isset($parsedUrl['host']))
        {
            $this->_statusCode = static::ERROR_URL;
            $this->_responseMsg = static::ERROR_MSG;

            return;
        }

        $this->_port = isset($parsedUrl['port']) ? $parsedUrl['port'] : ($parsedUrl['scheme'] == 'https' ? 443 : 80);

        $this->_path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';
        $this->_scheme = $parsedUrl['scheme'];
        $this->_host = $parsedUrl['host'];

    }

    /**
     * @param array  $data
     * @param string $path
     *
     * @return array
     */
    public function Post(array $data, string $path = ''): array
    {
        if ($this->_statusCode == static::ERROR_URL)
        {
            return $this->_errorUrl();
        }
        $this->_client->setHeaders($this->_header);
        $this->_client->Post($path == '' ? $this->_path : $path, $data);

        return $this->_Response();
    }

    /**
     * @param string $path
     *
     * @return array
     */
    public function Get(string $path = ''): array
    {
        if ($this->_statusCode == static::ERROR_URL)
        {
            return $this->_errorUrl();
        }
        $this->_client->setHeaders($this->_header);
        $this->_client->get($path == '' ? $this->_path : $path);

        return $this->_Response();
    }

    /**
     * @param array $header
     *
     * @return $this
     */
    public function Header(array $header)
    {
        $this->_header = array_merge($this->_header, $header);

        return $this;
    }

    /**
     * @param array $files =['formKeyName'=>'file path']
     *
     * @return $this
     */
    public function AddFile(array $files)
    {
        foreach ($files as $formKeyName => $fileInfo)
        {
            if (!is_array($fileInfo) && !isset($fileInfo['path']))
            {
                continue;
            }

            $this->_client->addFile($fileInfo['path'], $formKeyName, isset($fileInfo['mimeType']) ?
                $fileInfo['mimeType'] : null, isset($fileInfo['filename']) ? $fileInfo['filename'] :
                null, isset($fileInfo['offset']) ? (int)$fileInfo['offset'] : 0, isset($fileInfo['length']) ?
                (int)$fileInfo['length'] : -1);
        }

        return $this;
    }

    /**
     * @return array
     */
    private function _Response()
    {
        return [
            'httpCode' => $this->_client->statusCode, 'data' => (string)$this->_client->body,
        ];
    }

    /**
     * @return array
     */
    private function _errorUrl(): array
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
    public function Method(string $method)
    {
        $this->_client->setMethod($method);

        return $this;
    }


    /**
     * @param array $cookies
     *
     * @return $this
     */
    public function Cookies(array $cookies)
    {
        $this->_client->setCookies($cookies);

        return $this;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function SetData(array $data)
    {
        $this->_client->setData($data);

        return $this;
    }

    /**
     * @param string $path
     *
     * @return array
     */
    public function Execute(string $path)
    {
        $this->_client->execute($path);

        return [
            'httpCode' => $this->_client->statusCode, 'data' => (string)$this->_client->body,
        ];
    }

    /**
     *
     */
    public function Close()
    {
        $this->_client->close();
        unset($this->_client);
    }

}

