<?php
namespace com\yp\core;

use com\yp\tools\JsonHandler;

class FnParam implements \JsonSerializable
{

    public $name;

    public $value;

    public function __construct(String $pName = null, $pValue = null)
    {
        $this->name = $pName;
        $this->value = $pValue;
    }
    
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'value' => $this->value
        ];
    }

    public static function fromJson($std_class)
    {
        if ($std_class && property_exists($std_class, "name")) {
            $that = new FnParam();
            $that->name = $std_class->name;
            if (property_exists($std_class, "value")) {
                if (JsonHandler::isValidJson($std_class->value)){
                    $that->value = JsonHandler::promote($std_class->value);
                } else{
                    $that->value = $std_class->value;
                }
            }
            return $that;
        }
    }
}