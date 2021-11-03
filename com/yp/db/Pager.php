<?php
namespace com\yp\db;

class Pager implements \JsonSerializable
{
    private $pageIndex = 0;
    private $pageSize = 100;
    private $length = -1;
    
    public function __construct(int $pageIndex = 0, int $pageSize = -1, int $length=-1)
    {
        $this->pageIndex = $pageIndex;
        $this->pageSize = $pageSize;
        $this->length = $length;
    }    
    
    public function getPageIndex()
    {
        return $this->pageIndex;
    }
    
    public function setPageIndex(int $pageIndex)
    {
        $this->pageIndex = $pageIndex;
    }
    
    public function getPageSize()
    {
        return $this->pageSize;
    }
    
    public function setPageSize(int $pageSize)
    {
        $this->pageSize = $pageSize;
    }
    
    public function getLength()
    {
        return $this->length;
    }
    
    public function setLength(int $length)
    {
        $this->length = $length;
    }
    
    
    public function jsonSerialize()
    {
        return [
            'pageIndex' => $this->pageIndex,
            'pageSize' => $this->pageSize,
            'length' => $this->length
        ];
    }
    
    public static function fromJson($std_class)
    {        
        if ($std_class && property_exists($std_class, "pageIndex")) {            
            return new Pager($std_class->pageIndex, $std_class->pageSize, $std_class->length);
        }
    }

}

