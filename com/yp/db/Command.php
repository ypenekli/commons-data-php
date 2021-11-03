<?php
namespace com\yp\db;

use com\yp\core\FnParam;
use com\yp\entity\DataEntity;
use PDO;

class Command
{

    const INSERT_INTO = " INSERT INTO ";

    const WHERE = " WHERE ";

    const SELECT = " SELECT ";

    const FROM = " FROM ";

    const SELECT_FROM = " SELECT * FROM ";

    const UPDATE = " UPDATE ";

    const DELETE = " DELETE ";

    const VALUES = " VALUES ";

    const SET = " SET ";

    const FORMAT_LIMIT = '%s LIMIT %d, %d';

    public $params = array();

    public $query;

    private $stmt;

    private function __construct(String $query, array $params = null, Pager $pager = null)
    {
        $this->params = $params;
        $this->setQuery($query, $pager);
    }

    private function setQuery(String $query, Pager $pager = null)
    {
        if ($query != null && $query != "") {
            $this->query = str_replace('~KA~', '.', $query);
            if ($pager != null && $pager->getPageSize() > - 1) {
                $offset = $pager->getPageIndex() * $pager->getPageSize();
                $this->query = sprintf(self::FORMAT_LIMIT, $this->query, $offset, $pager->getPageSize());
            }
        }
    }

    public function isSuccess()
    {
        return $this->query != null && $this->query != "";
    }

    private function bindParams()
    {
        $count = 0;
        if ($this->params != null) {
            $count = count($this->params);
        }
        if ($count > 0) {
            for ($x = 0; $x < $count; $x ++) {
                $this->stmt->bindParam($x + 1, $this->params[$x]->value);
            }
        }
    }

    public function fetchAll(String $className = DataEntity::class)
    {
        $this->stmt->execute();
        if ($this->stmt->setFetchMode(PDO::FETCH_CLASS, $className) !== false) {
            return $this->stmt->fetchAll();
        }
    }

    public function fetch(String $className = DataEntity::class)
    {
        $this->stmt->execute();
        if ($this->stmt->setFetchMode(PDO::FETCH_CLASS, $className) !== false) {
            return $this->stmt->fetch();
        }
    }

    public function execute()
    {
        return $this->stmt->execute();
    }

    public function refreshValues(DataEntity $pEntity)
    {
        $countPK = count($pEntity->getPrimaryKeys());
        $i = 0;
        if (! $pEntity->isDeleted()) {
            for ($i = 0; $i < $this->fieldCount; $i ++) {
                $this->params[$i]->setValue($pEntity->get($this->params[$i]->getName()));
            }
        }
        if (! $pEntity->isNew()) {
            for ($j = 0; $j < $countPK; $j ++) {
                $this->params[$i + $j]->setValue($pEntity->getPrimaryKeyValue($this->params[$i + $j]->getName()));
            }
        }
    }

    public function close()
    {
        $this->stmt->closeCursor();
    }

    public static function buildQueryCommand(PDO $connection, String $query, array $params, Pager $pager = null)
    {
        $cmd = new Command($query, $params, $pager);
        if ($cmd->isSuccess()) {
            $cmd->stmt = $connection->prepare($cmd->query);
            $cmd->bindParams();
        }
        return $cmd;
    }

    public static function buildCommand(PDO $connection, DataEntity $entity)
    {
        $cmd = null;
        if ($entity->isNew()) {
            $cmd = self::generateInsertCommand($entity);
        } elseif ($entity->isUpdated()) {
            $cmd = self::generateUpdateCommand($entity);
        } elseif ($entity->isDeleted()) {
            $cmd = self::generateDeleteCommand($entity);
        } else {
            $cmd = self::generateSellectCommand($entity);
        }
        if ($cmd->isSuccess()) {
            $cmd->stmt = $connection->prepare($cmd->query);
            $cmd->bindParams();
        }
        return $cmd;
    }

    private static function generateSellectCommand(DataEntity $pEntity)
    {
        $query = "";
        $paramList = self::checkFields($pEntity);
        $count = count($pEntity->getPrimaryKeys());
        if ($count > 0) {
            $select = self::SELECT_FROM . $pEntity->getSchemaName() . "." . $pEntity->getTableName();
            [
                $query,
                $paramList
            ] = self::appendFilter($pEntity, $select, $paramList);
        }
        return new Command($query, $paramList);
    }

    private static function generateInsertCommand(DataEntity $pEntity)
    {
        $paramList = self::checkFields($pEntity);
        if (count($paramList) > 0) {
            $insert = self::INSERT_INTO . $pEntity->getSchemaName() . "." . $pEntity->getTableName() . " (";
            $values_ = self::VALUES . " (";
            foreach ($paramList as $x1) {
                $insert .= $x1->name . ", ";
                $values_ .= "?, ";
            }
            $insert = rtrim($insert, ", ");
            $insert .= ") ";
            $values_ = rtrim($values_, ", ");
            $values_ .= ") ";

            return new Command($insert . $values_, $paramList);
        }
    }

    private static function generateUpdateCommand(DataEntity $pEntity)
    {
        $paramList = self::checkFields($pEntity);
        if (count($paramList) > 0) {
            $update = self::UPDATE . $pEntity->getSchemaName() . "." . $pEntity->getTableName() . self::SET;
            foreach ($paramList as $x1) {
                $update .= $x1->name . " = ?, ";
            }
            $update = rtrim($update, ", ");
            [
                $query,
                $paramList
            ] = self::appendFilter($pEntity, $update, $paramList);
            return new Command($query, $paramList);
        }
    }

    private static function generateDeleteCommand(DataEntity $pEntity)
    {
        $paramList = self::checkFields($pEntity);
        $delete = self::DELETE . self::FROM . $pEntity->getSchemaName() . "." . $pEntity->getTableName();
        [
            $query,
            $paramList
        ] = self::appendFilter($pEntity, $delete, $paramList);
        return new Command($query, $paramList);
    }

    private static function appendFilter(DataEntity $pEntity, String $pCommand, array $params)
    {
        $pCommand .= self::WHERE;
        foreach ($pEntity->getPrimaryKeys() as $x1 => $x2) {
            $pCommand .= $x1 . " = ? AND ";
            $params[] = new FnParam($x1, $x2->getValue());
        }

        return array(
            rtrim($pCommand, "AND "),
            $params
        );
    }

    private static function checkFields(DataEntity $pEntity)
    {
        $paramList = array();
        if ($pEntity->isNew() || $pEntity->isUpdated()) {
            foreach ($pEntity->getFields() as $x1 => $x2) {
                if (! is_null($x2) && ! $x2->isReadonly() && $x2->isChanged()) {
                    $paramList[] = new FnParam($x1, $x2->getValue());
                }
            }
        }
        return $paramList;
    }

    public static function buildFindPkCommand(PDO $connection, DataEntity $entity)
    {
        $params = array();
        $keys = array();
        $where = Command::WHERE;
        $select = Command::SELECT;

        foreach ($entity->getPrimaryKeys() as $x1 => $x2) {
            if (is_null($x2) || $x2->getValue() == - 1) {
                $select .= " MAX(" . $x1 . ") + 1 AS " . $x1 . ", ";
                $keys[] = $x1;
            } else {
                $where .= $x1 . ' = ? AND ';
                $params[] = $x2->getValue();
            }
        }
        if (strlen($select) > strlen(Command::SELECT)) {
            $select = rtrim($select, ", ");
            $where = rtrim($where, "AND ");
            $query = $select . Command::FROM . $entity->getSchemaName() . "." . $entity->getTableName();
            if (strlen($where) > strlen(Command::WHERE)) {
                $query .= $where;
            }
            return array(
                Command::buildQueryCommand($connection, $query, $params),
                $keys
            );
        }
    }

    static function MakePrettyException(\PDOException $e)
    {
        $trace = $e->getTrace();

        $result = 'Exception: "';
        $result .= $e->getMessage();
        $result .= '" @ ';
        if ($trace[0]['class'] != '') {
            $result .= $trace[0]['class'];
            $result .= '->';
        }
        $result .= $trace[0]['function'];
        $result .= '();<br />';

        return $result;
    }
}

