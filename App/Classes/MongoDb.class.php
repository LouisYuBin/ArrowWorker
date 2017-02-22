<?php
namespace App\Classes;
use \MongoDB\Driver\Query as query;
use \MongoDB\Driver\Manager as manager;
use	\MongoDB\Driver\BulkWrite as write;

class MongoDb
{
	private static $mongoInstance = null; 
	public  static $mongoManager  = null;
	public  static $mongoConfig   = null;
	public  static $writeConcern  = null;
	
	public function __construct( $config )
	{
		self::$mongoConfig = $config;
		
		$secondary   = '';
		$extraString = '';
		if( isset( self::$mongoConfig['secondary'] ) )
		{
			foreach( self::$mongoConfig['secondary'] as $node )
			{
				$secondary .= ','.implode( ':', array_values( $node ) );
			}
		}
		if( $secondary != '' )
		{
			$extraString = '?replicaSet=rs_main&amp;readPreference=secondaryPreferred';
		}
		self::$mongoManager = new manager("mongodb://{$config['username']}:{$config['password']}@{$config['host']}:{$config['port']}{$secondary}/{$config['dbName']}{$extraString}");
	}
	
	public function reconnect()
	{
        $this -> __construct( self::$mongoConfig );
	}
	
	public function query($table, $filter, $options)
	{
        $filter = $this -> objId($filter);
		$query     = new query($filter, $options);
		$cursor    = self::$mongoManager -> executeQuery(self::$mongoConfig['dbName'].'.'.$table, $query);
		$documents = [];
		foreach ($cursor as $document)
		{
			$objectId       = strval($document->_id);
			$document       = json_decode(json_encode($document),true);
			$document['id'] = $objectId;
			unset($document['_id']);
			$documents[]    = $document;
		}
		return $documents;
	}
	
	public function update($table, $filter, $update)
	{
        $filter = $this -> objId($filter);
		$updateRlt = ['result' => 200, 'msg' => 'ok'];
		
		$bulkWrite = new write(['ordered' => true]);
		$bulkWrite -> update($filter, ['$set' => $update]);
		try
		{
			$result = self::$mongoManager -> executeBulkWrite(self::$mongoConfig['dbName'].'.'.$table, $bulkWrite);
		}
		catch(\MongoDB\Driver\Exception\BulkWriteException $e)
		{
			$result = $e->getWriteResult();
			// Check if the write concern could not be fulfilled
			if ($writeConcernError = $result->getWriteConcernError())
			{
				$updateRlt = ['result' => $writeConcernError->getCode(), 'msg' => $writeConcernError->getMessage()];
			}
			// Check if any write operations did not complete at all
			foreach ($result->getWriteErrors() as $writeError)
			{
				$updateRlt = ['result' => $writeError->getCode(), 'msg' => $writeError->getMessage()];
			}
		}
		catch (\MongoDB\Driver\Exception\Exception $e)
		{
			$updateRlt = ['result' => -1, 'msg' => $e->getMessage()];
		}
		return $updateRlt;		
	}
	
	public function insert($table, $data)
	{
		$insertRlt = ['result' => 200, 'msg' => 'ok'];

		$bulkWrite = new write(['ordered' => true]);
		if(is_array($data))
		{
			if(count($data) != count($data, COUNT_RECURSIVE))
			{
				foreach($data as $eachData)
				{
					$bulkWrite -> insert($eachData);
				}
			}
			else
			{
				$bulkWrite -> insert($data);
			}
		}
		else
		{
			$insertRlt = ['result' => -1, 'msg' => 'data should be array'];
			return $insertRlt;
		}

		try
		{
			$result = self::$mongoManager -> executeBulkWrite(self::$mongoConfig['dbName'].'.'.$table, $bulkWrite);
		}
		catch(\MongoDB\Driver\Exception\BulkWriteException $e)
		{
            $insertRlt = [ 'result' => -2, 'msg' => [ 'WriteConcernError' => null, 'WriteErrors' => [] ] ];
			$result = $e->getWriteResult();
			// Check if the write concern could not be fulfilled
			if ($writeConcernError = $result->getWriteConcernError())
			{
                $insertRlt['msg']['WriteConcernError'] = $writeConcernError;
			}
			// Check if any write operations did not complete at all
			foreach ($result->getWriteErrors() as $writeError)
			{
                $insertRlt['msg']['WriteErrors'][] = $writeError;
			}
		}
		catch (\MongoDB\Driver\Exception\Exception $e)
		{
			$insertRlt = ['result' => -1, 'msg' => $e->getMessage()];
		}
		return $insertRlt;		
	}
	
	public function delete($table, $filter)
	{
        $filter = $this -> objId($filter);
		$deleteRlt = ['result' => 200, 'msg' => 'ok'];
		$bulkWrite = new write(['ordered' => true]);
		$bulkWrite -> delete($filter, ['limit' => 0]);
		try
		{
			$result = self::$mongoManager -> executeBulkWrite(self::$mongoConfig['dbName'].'.'.$table, $bulkWrite);
		}
		catch(\MongoDB\Driver\Exception\BulkWriteException $e)
		{
			$result = $e->getWriteResult();
			// Check if the write concern could not be fulfilled
			if ($writeConcernError = $result->getWriteConcernError())
			{
				$deleteRlt = ['result' => $writeConcernError->getCode(), 'msg' => $writeConcernError->getMessage()];
			}
			
		}
		catch (\MongoDB\Driver\Exception\Exception $e)
		{
			$deleteRlt = ['result' => -1, 'msg' => $e->getMessage()];
		}
		return $deleteRlt;		
	}

    public function objId($filter)
    {
        $newFilter = $filter;
        if(isset($newFilter['_id']))
        {
           $newFilter['_id'] = new \MongoDB\BSON\ObjectID($newFilter['_id']);
        }
        return $newFilter;
    }
	
}
