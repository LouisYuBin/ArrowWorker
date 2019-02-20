<?php
/**
 * By yubin at 2019/2/18 11:11 AM.
 */

namespace App\Controller\Admin;

use ArrowWorker\Response;

class Index
{
    public function index()
    {
        Response::Write(mt_rand(0,10000));
    }

    public function get()
    {
        Response::Write('rest get');
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