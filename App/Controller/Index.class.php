<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller;
use ArrowWorker\Controller as controller;


class Index extends controller
{
    function index()
    {
        $daemon =  self::getObj('daemon');
        $class = __CLASS__;
        $daemon -> addTask(['function' => [$class,'worker1'],'argv' => [100],'concurrency' => 3 ,'lifecycle' => 30,'proName' => 'Life_1_3_300']);
        $daemon -> addTask(['function' => [$class,'worker2'],'argv' => [100],'concurrency' => 3 , 'lifecycle' => 240 ,'proName' => 'Life_2_3_240']);
        $daemon -> start();

    }

    public static function worker1$arg)
    {
        $class = self::load('Method','c');
        for($i=0; $i<100; $i++)
        {
            $rdmNum = mt_rand(0,9999999);
            self::mongoInsert();
        }
    }

    public static function worker2($arg)
    {
	
    }


    public static function mongoInsert()
    {
        $bulk = new \MongoDB\Driver\BulkWrite(['ordered' => true]);
        for($i=1; $i<=20; $i++)
        {
            $sid    = mt_rand(1,3000);
            $status = mt_rand(0,2);
            $datet  = gmdate('Y-m-d H:i:s');
            $bulk -> insert(["sid" => $sid, "status" => $status , "data" =>'nothing']);
        }

        $manager = new \MongoDB\Driver\Manager('mongodb://username:password@127.0.0.1:27017,127.0.0.2:27017/DbName?replicaSet=rs_main&amp;readPreference=secondaryPreferred');
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);

        try 
        {
            $result = $manager->executeBulkWrite('vnnox.vnnox_table', $bulk);
        } 
        catch (\MongoDB\Driver\Exception\BulkWriteException $e) 
        {
            $result = $e->getWriteResult();

            // Check if the write concern could not be fulfilled
            if ($writeConcernError = $result->getWriteConcernError()) 
            {
                printf("%s (%d): %s\n",
                    $writeConcernError->getMessage(),
                    $writeConcernError->getCode(),
                    var_export($writeConcernError->getInfo(), true)
                );
            }

            // Check if any write operations did not complete at all
            foreach ($result->getWriteErrors() as $writeError)
            {
                printf("Operation#%d: %s (%d)\n",
                    $writeError->getIndex(),
                    $writeError->getMessage(),
                    $writeError->getCode()
                );
            }
        } 
        catch (\MongoDB\Driver\Exception\Exception $e)
        {
            printf("Other error: %s\n", $e->getMessage());
            exit;
        }

        printf("Inserted %d document(s)\n", $result->getInsertedCount());
        printf("Updated  %d document(s)\n", $result->getModifiedCount());
    }

}
