<?php

namespace ArrowWorker\Library\Xml;

/**
 * Class Reader
 * @package Xml
 */
class Reader
{
    /**
     * @var \XMLReader
     */
    private $reader;


    /**
     * Reader constructor.
     * @param string $url
     */
    public function __construct(string $url='')
    {
        $this->reader = new \XMLReader();
        $this->reader->open($url);
        $this->reader->read();
    }

    /**
     * toArray : make final array
     * @return array
     */
    public function toArray() : array
    {
        $nodeList  = $this->reader->expand()->childNodes;
        $nodeArray = $this->expandChildNode($nodeList);
        return  [ $this->reader->localName => $nodeArray];

    }

    /**
     * expandChildNode : expand xml node to array
     * @param \DOMNodeList $nodeList
     * @return array|string
     */
    public function expandChildNode(\DOMNodeList $nodeList)
    {
        $data = [];
        foreach ($nodeList as $node )
        {
            $content = $node->textContent;
            if ( $node->hasChildNodes() )
            {
                $content = $this->expandChildNode($node->childNodes);
            }
            if ( $node->nodeName=='#text' )
            {
                $data = $content;
                continue;
            }
            $data[$node->nodeName] = $content;
        }

        return $data;
    }

}