<?php
namespace com\yp\core;

use com\yp\entity\DataEntity;
use com\yp\db\Pager;
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
                    $that->value = FnParam::promote($std_class->value);
                } else{
                    $that->value = $std_class->value;
                }
            }
            return $that;
        }
    }

    public static function promoteArray($std_class){
        $x1 = $std_class[0];
        if (property_exists($x1, "state")) {
            $result = Array();
            foreach ($std_class as $x1) {
                $result[] = DataEntity::fromJson($x1);
            }
        } else if (property_exists($x1, "name")) {
            $result = Array();
            foreach ($std_class as $x1) {
                $result[] = FnParam::fromJson($x1);
            }
        }
        return $result;
    }
    
    public static function promote($std_class)
    {
        $result = $std_class;
        if (is_array($std_class)) {
            $x1 = $std_class[0];
            if (! is_array($x1)) {
                $result = FnParam::promoteArray($std_class);
            } else{
                FnParam::promote($x1);
            }
        } else {
            if (property_exists($std_class, "fields")) {
                $result = DataEntity::fromJson($std_class);
            } else if (property_exists($std_class, "name")) {
                $result = FnParam::fromJson($std_class);
            } else if (property_exists($std_class, "offset")) {
                $result = Pager::fromJson($std_class);
            }
        }
        
        return $result;
    }
}