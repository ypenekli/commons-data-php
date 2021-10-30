<?php
namespace com\yp\entity;

use com\yp\tools\JsonHandler;

include_once CORE_PACKAGE_ROOT . '/entity/Element.php';

class DataEntity implements \JsonSerializable
{

    const INSERTED = 0;

    const DELETED = 1;

    const UPDATED = 2;

    const UNCHANGED = 3;

    const EMPTY = 4;
    
    const SCHEMA_NAME = 'BASE_SCHEMA';
    
    const TABLE_NAME = 'BASE_TABLE';
    
    const CLASS_NAME = 'DataEntity';

    protected $fields;

    protected $primary_keys;

    protected $state;

    protected $className;
    

    public function __construct()
    {
        $this->primary_keys = array();
        $this->state = DataEntity::UNCHANGED;
        $this->className = self::CLASS_NAME;
    }

    public function getSchemaName():string
    {
        $c = get_called_class();
        return $c::SCHEMA_NAME;
    }

    public function getTableName():string
    {
        $c = get_called_class();
        return $c::TABLE_NAME;
    }

    public function getClassName():string
    {
        return $this->className;
    }

    public function get($pFieldName)
    {
        $pFieldName = strtolower($pFieldName);
        if (isset($this->fields[$pFieldName])) {
            return $this->fields[$pFieldName]->getValue();
        }
    }

    public function getElement($pFieldName)
    {
        $pFieldName = strtolower($pFieldName);
        if (isset($this->fields[$pFieldName])) {
            return $this->fields[$pFieldName];
        }
    }

    public function __set($pFieldName, $pValue)
    {
        $this->set($pFieldName, $pValue, false);
        return $this;
    }

    public function set($pFieldName, $pValue, bool $pChanged = true)
    {
        $pFieldName = strtolower($pFieldName);
        if (! isset($this->fields[$pFieldName])) {
            $this->fields[$pFieldName] = new Element();
        }
        $this->fields[$pFieldName]->setValue($pValue, $pChanged);
        if ($pChanged == true && $this->state == DataEntity::UNCHANGED) {
            $this->state = DataEntity::UPDATED;
        }

        if ($this->isPrimaryKey($pFieldName) && ($this->isNew() || ! $pChanged)) {
            $this->primary_keys[$pFieldName]->setValue($pValue);
        }
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function delete()
    {
        $this->state = DataEntity::DELETED;
    }

    public function accept()
    {
        $this->state = DataEntity::UNCHANGED;
        foreach ($this->fields as $x1) {
            $x1->accept();
        }
        foreach ($this->primary_keys as  $x1 => $x2 ) {
            if (isset($this->fields[$x1])) {
                $x2 = $this->fields[$x1];
                $this->primary_keys[$x1] = $x2;
            }
        }
    }

    public function isUnchanged()
    {
        return $this->state == DataEntity::UNCHANGED;
    }

    public function isNew()
    {
        return $this->state == DataEntity::INSERTED;
    }

    public function isUpdated()
    {
        return $this->state == DataEntity::UPDATED;
    }

    public function isDeleted()
    {
        return $this->state == DataEntity::DELETED;
    }

    public function isPrimaryKey($pFieldName)
    {
        $pFieldName = strtolower($pFieldName);
        return isset($this->primary_keys[$pFieldName]);
    }

    public function getPrimaryKeys()
    {
        return $this->primary_keys;
    }

    public function getPrimaryKeyValue($pFieldName)
    {
        $pFieldName = strtolower($pFieldName);
        if (isset($this->primary_keys[$pFieldName])) {
            return $this->primary_keys[$pFieldName]->getValue();
        }
    }
    
    public function setClient(DataEntity $de){
        $this->setClientInfo(
            $de->get("client_name"),
            $de->get("client_ip"),
            $de->get("client_datetime"));
    }
    
    public function setLastClient(DataEntity $de){        
        $this->setLastClientInfo(
            $de->get("last_client_name"),
            $de->get("last_client_ip"),
            $de->get("last_client_datetime"));
    }

    public function setLastClientInfo(string $userName, string $remaddres, $datetime = null){
        if($datetime == null){
            $datetime = (new \DateTime())->format('YmdHisv');
        }
        if($this->isNew()){            
            $this->setClientInfo($userName, $remaddres, $datetime);
        }        
        $this->set("last_client_name", $userName);
        $this->set("last_client_ip", $remaddres);
        $this->set("last_client_datetime", $datetime);
    }
    
    public function setClientInfo(string $userName, string $remaddres, $datetime = null){
        if($datetime == null){
            $datetime = (new \DateTime())->format('YmdHisv');
        }        
        $this->set("client_name", $userName);
        $this->set("client_ip", $remaddres);
        $this->set("client_datetime", $datetime);
        
    }
    
    
    public function jsonSerialize()
    {
        return [
            'className' => get_called_class(),
            'state' => $this->state,
            'fields' => $this->fields,
            'primary_keys' => $this->getPrimaryKeys()
        ];
    }

    public static function fromJson($std_class)
    {
        if (! property_exists($std_class, "className")) {
            $target_class = self::CLASS_NAME;
        } else {
            $target_class = $std_class->className;
        }
        $target_class = JsonHandler::get_class($target_class);
        $that = new $target_class(); 
        $that->className = $target_class;
        $that->state = $std_class->state;

        foreach ($std_class->primaryKeys as $x1 => $x2) {
            $that->primary_keys[strtolower($x1)] = Element::fromJson($x2);
        }

        foreach ($std_class->fields as $x1 => $x2) {
            $e = Element::fromJson($x2);
            if (! empty($e)) {
                $that->fields[strtolower($x1)] = $e;
            }
        }

        return $that;
    }
}