<?php
/**
 * By yubin at 2019-11-20 18:12.
 */

namespace ArrowWorker\Library\Grpc;

use Google\Protobuf\Internal\Message;

class Parser
{
    const GRPC_ERROR_NO_RESPONSE = -1;

    public static function pack( string $data ) : string
    {
        return $data = pack( 'CN', 0, strlen( $data ) ) . $data;
    }

    public static function unpack( string $data ) : string
    {
        // it's the way to verify the package length
        // 1 + 4 + data
        // $len = unpack('N', substr($data, 1, 4))[1];
        // assert(strlen($data) - 5 === $len);
        return $data = substr( $data, 5 );
    }

    public static function SerializeMessage( $message )
    {
        if ( method_exists( $message, 'encode' ) )
        {
            $data = $message->encode();
        }
        else if ( method_exists( $message, 'serializeToString' ) )
        {
            $data = $message->serializeToString();
        }
        else if ( method_exists( $message, 'serialize' ) )
        {
            $data = $message->serialize();
        }
        return self::pack( (string)$data );
    }

    public static function DeserializeMessage( $deserialize, string $value )
    {
        if ( empty( $value ) )
        {
            return null;
        }
        $value = self::unpack( $value );

        if ( is_array( $deserialize ) )
        {
            [
                $className,
                $deserializeFunc,
            ] = $deserialize;
            /** @var \Google\Protobuf\Internal\Message $object */
            $object = new $className();
            if ( $deserializeFunc && method_exists( $object, $deserializeFunc ) )
            {
                $object->{$deserializeFunc}( $value );
            }
            else
            {
                // @noinspection PhpUndefinedMethodInspection
                $object->mergeFromString( $value );
            }
            return $object;
        }
        return call_user_func( $deserialize, $value );
    }

    /**
     * @param null|\swoole_http2_response $response
     * @param                             $deserialize
     * @return \Grpc\StringifyAble[]|Message[]|\swoole_http2_response[]
     */
    public static function ParseResponse( $response, $deserialize ) : array
    {
        if ( !$response )
        {
            return [
                'No response',
                self::GRPC_ERROR_NO_RESPONSE,
                $response,
            ];
        }
        if ( $response->statusCode !== 200 )
        {
            $message = $response->headers[ 'grpc-message' ] ?? 'Http status Error';
            $code    = $response->headers[ 'grpc-status' ] ?? ( $response->errCode ? : $response->statusCode );
            return [
                $message,
                (int)$code,
                $response,
            ];
        }
        $grpc_status = (int)( $response->headers[ 'grpc-status' ] ?? 0 );
        if ( $grpc_status !== 0 )
        {
            return [
                $response->headers[ 'grpc-message' ] ?? 'Unknown error',
                $grpc_status,
                $response,
            ];
        }
        $data   = $response->data;
        $reply  = self::deserializeMessage( $deserialize, $data );
        $status = (int)( $response->headers[ 'grpc-status' ] ?? 0 ? : 0 );
        return [
            $reply,
            $status,
            $response,
        ];
    }
}
