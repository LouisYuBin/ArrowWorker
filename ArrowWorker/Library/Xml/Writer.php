<?php

namespace ArrowWorker\Library\Xml;

/**
 * Class Writer
 * @package Xml
 */
class Writer
{
    /**
     * @var Writer
     */
    private $writer;


    /**
     * Writer constructor.
     * @param string $version
     * @param string $charset
     * @param string|null $stylePath
     */
    public function __construct(bool $hasHeader = false, string $version = '1.0', string $charset = 'UTF-8', string $stylePath = null)
    {
        $this->writer = new \XMLWriter;
        $this->writer->openMemory();
        $this->writer->setIndent(false);
        $this->writer->setIndentString(' ');
        if ($hasHeader) {
            $this->writer->startDocument($version, $charset);
        }

        if (!is_null($stylePath)) {
            $this->writer->writePI($this->writer, 'xml-stylesheet', 'type="text/xsl" href="' . $stylePath . '"');
        }
    }


    /**
     * @param string $elementName
     * @param string $elementValue
     */
    public function setElement(string $elementName, string $elementValue)
    {
        $this->writer->startElement($elementName);
        $this->writer->text($elementValue);
        $this->writer->endElement();
    }


    /**
     * @param array $elementArray
     * @param string $parentIndex
     * @return $this
     */
    public function makeFromArray(array $elementArray, string $parentIndex = '')
    {
        foreach ($elementArray as $index => $element) {
            if (is_array($element)) {
                $key = !is_int($index) ? $index : $parentIndex;
                $isNewNode = !(isset($element[0]) && !is_int($index));
                if ($isNewNode) {
                    $this->writer->startElement($key);
                }
                $this->makeFromArray($element, $index);
                if ($isNewNode) {
                    $this->writer->endElement();
                }
            } else {
                $this->setElement($index, $element);
            }
        }
        return $this;
    }

    /**
     * Return the content of a current xml document.
     * @access public
     * @param null
     * @return string Xml document
     */
    public function getXml()
    {
        $this->writer->endDocument();
        return $this->writer->outputMemory();
    }


}