<?php
namespace com\yp\entity;

class Element implements \JsonSerializable
{

    private $value;

    private $changed;

    private $typeName;

    private $readOnly;

    public function __construct($value = NULL, bool $readonly = false, String $typeName = "String")
    {
        $this->value = $value;
        $this->readOnly = $readonly;
        $this->typeName = $typeName;
        $this->changed = false;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value, bool $changed = true)
    {
        $this->changed = $changed;
        $this->value = $value;
        $this->typeName = gettype($value);
    }

    public function isChanged()
    {
        return $this->changed;
    }

    public function isReadOnly()
    {
        return $this->readOnly;
    }

    public function getTypeName()
    {
        return $this->typeName;
    }

    public function accept()
    {
        $this->changed = false;
    }

    public function jsonSerialize()
    {
        return [
            // 'name' => $this->name,
            // 'typeName' => $this->typeName,
            'value' => $this->value,
            'readOnly' => $this->readOnly,
            'changed' => $this->changed
        ];
    }

    public static function fromJson($std_class)
    {
        if (! empty($std_class) && property_exists($std_class, 'value')) {
            $e = new Element($std_class->value);
            $e->changed = $std_class->changed != null && $std_class->changed;
            $e->typeName = $std_class->typeName;
            $e->readOnly = $std_class->readOnly != null && $std_class->readOnly;
            return $e;
        }
    }
}

?>