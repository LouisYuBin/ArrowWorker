<?php
/**
 * By yubin at 2019/2/18 11:11 AM.
 */

namespace App\Controller\Admin;

use ArrowWorker\Loader;
use ArrowWorker\Web\Request;
use ArrowWorker\Web\Response;
use ArrowWorker\Lib\Client\WebSocket;
use ArrowWorker\Log;

class Index
{
    public function index()
    {

        Response::Write(mt_rand(0,10000));
    }

    public function get()
    {
        $this->_webSocketClient();

        Log::Info('hese functions let you read andhese functions let you read and manipulate ID3 tags. ID3 tags are used in MP3 files to store title of the song, as well as information about the artist, album, genre, year and track numberhese functions let you read and manipulate ID3 tags. ID3 tags are used in MP3 files to store title of the song, as well as information about the artist, album, genre, year and track numberhese functions let you read and manipulate ID3 tags. ID3 tags information about the artist, album, genre, year and track numberhese functions let you read and manipulate ID3 tags. ID3 tags are used in MP3 files to store title of the song, as well as information about the artist, album, genre, year and track numberhese functions let you read and manipulate ID3 tags. ID3 tags are used in MP3 files to store title of the song, as well as information about the artist, album, genre, year and track numberhese functions let you read and manipulate ID3 tags. ID3 tags are used in MP3 files to store title of the song, as well as information about the artist, album, genre, year and track numberhese functions let you read and manipulate ID3 tags. ID3 tags are used in MP3 files to store title of t album, genre, year and track number','ccc');
        Response::Write('rest get  '.Request::Server('REQUEST_URI').json_encode(Request::Params()));
    }

    private function _webSocketClient()
    {
        $cli = WebSocket::Connect('127.0.0.1',9503);
        $cli->Push(mt_rand(1,1000).'_from http','/?a=a&b=b');
    }

    public function put()
    {
        Response::Write('rest put');
    }

    public function post()
    {
        Response::Write('rest post');
    }
}