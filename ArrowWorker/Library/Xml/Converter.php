<?php
/**
 * By yubin at 2018/12/10 7:46 PM.
 */

namespace ArrowWorker\Library\Xml;

use ArrowWorker\Library\Verifier;


/**
 * Class Converter
 * @package Xml
 */
class Converter
{
    /**
     * @var \SimpleXMLElement
     */
    private $_xmlObject;

    /**
     * Converter constructor.
     * @param string $xml
     */
    public function __construct(string $xml)
    {
        if( !Verifier::IsXml($xml) )
        {
            return ;
        }
        $this->_xmlObject = simplexml_load_string($xml);
    }

    /**
     * @return bool|array
     */
    public function ToArray()
    {
        if( empty($this->_xmlObject) )
        {
            return false;
        }
        return json_decode( json_encode( $this->_xmlObject ),true);
    }

    /**
     * @return bool|\SimpleXMLElement
     */
    public function ToObject()
    {
        if( empty($this->_xmlObject) )
        {
            return false;
        }
        return $this->_xmlObject;
    }

}