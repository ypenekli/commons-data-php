<?php
namespace com\yp\db;

class Pager implements \JsonSerializable
{
    private $offset = 0;
    private $limit = 1500;
    private $cout = -1;
    
    public function __construct(int $offset = 0, int $limit = -1, int $cout=-1)
    {
        $this->offset = $offset;
        $this->limit = $limit;
        $this->count = $cout;
    }    
    
    public function getOffset()
    {
        return $this->offset;
    }
    
    public function setOffset(int $offset)
    {
        $this->offset = $offset;
    }
    
    public function getLimit()
    {
        return $this->limit;
    }
    
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }
    
    public function getCount()
    {
        return $this->cout;
    }
    
    public function setCount(int $cout)
    {
        $this->cout = $cout;
    }
    
    
    public function jsonSerialize()
    {
        return [
            'offset' => $this->offset,
            'limit' => $this->limit,
            'count' => $this->count
        ];
    }
    
    public static function fromJson($std_class)
    {
        if ($std_class && property_exists($std_class, "offset")) {
            $that = new Pager();
            $that->offset = $std_class->offset;
            $that->limit = $std_class->limit;
            $that->count = $std_class->count;
            return $that;
        }
    }

}

