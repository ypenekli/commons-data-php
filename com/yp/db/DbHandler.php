<?php
namespace com\yp\db;

include_once CORE_PACKAGE_ROOT . '/entity/DataEntity.php';
include_once CORE_PACKAGE_ROOT . '/db/Pager.php';
include_once CORE_PACKAGE_ROOT . '/db/Result.php';
include_once CORE_PACKAGE_ROOT . '/db/Command.php';

use PDO;
use PDOException;
use com\yp\tools\Configuration;
use com\yp\entity\DataEntity;
use com\yp\tools\JsonHandler;

class DbHandler
{

    private $connection;

    public function __construct(PDO $connection = null)
    {
        $this->connection = $connection;
        if ($this->connection === null) {
            $filename = SITE_ROOT . '../../commons-data-php/Config.properties';
            $this->buildConnection(Configuration::getSubConfig($filename, 'database'));
        }
    }

    private function buildConnection($configuration)
    {
        if ($configuration['type'] == 'mysql') {
            $this->dsn = '(type):host=(host);dbname=(database);charset=UTF8';
            $this->dsn = str_replace('(type)', $configuration['type'], $this->dsn);
            $this->dsn = str_replace('(host)', $configuration['host'], $this->dsn);
            $this->dsn = str_replace('(database)', $configuration['database'], $this->dsn);

            $this->options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            );
            try {
                $this->connection = new PDO($this->dsn, $configuration['username'], $configuration['password']);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                error_log("buildConnection!" . $e->getMessage(), 0);
            }
        }
    }

    public function find(string $pFnName, DataEntity $pEntity)
    {
        $result = null;
        $pEntity->accept();
        $className = JsonHandler::get_class($pEntity->getClassName());
        $cmd = Command::buildCommand($this->connection, $pEntity);
        if ($cmd->isSuccess()) {
            try {
                $result = $cmd->fetch($className);
                $cmd->close();
            } catch (PDOException $e) {
                error_log("find!" . query . " :" . $cmd->query);
                error_log("find!" . message . " :" . $e->getMessage(), 0);
            }
        }
        return $result;
    }

    private function findCount(string $pQueryName, array $pParams)
    {
        $className = DataEntity::class;
        $result = null;
        $cmd = Command::buildCountQueryCommand($this->connection, $this->queries[$pQueryName], $pParams);
        if ($cmd->isSuccess()) {
            try {
                $result = $cmd->fetch($className);
                $cmd->close();
            } catch (PDOException $e) {
                error_log("findCount!" . $pQueryName . " :" . $cmd->query);
                error_log("findCount!" . $pQueryName . " :" . $e->getMessage(), 0);
            }
        }
        error_log("findCount 1!" . $pQueryName . " :" . $cmd->query);
        if ($result != null) {
            error_log("findCount 2!" . $result->get("count") );
            return $result->get("count");
        }
        return 0;
    }

    public function findOne(string $pQueryName, array $pParams)
    {
        $result = null;
        $params = array();
        $pager = new Pager();
        $className = DataEntity::class;
        if (isset($pParams) && is_array($pParams)) {
            $count = count($pParams);
            if ($count > 1) {
                $className = $pParams[0]->value;
                $pager = $pParams[1]->value;
            }
            if ($count > 2) {
                $params = array_slice($pParams, 2);
            }
        }

        $cmd = Command::buildQueryCommand($this->connection, $this->queries[$pQueryName], $params, $pager);
        if ($cmd->isSuccess()) {
            try {
                $result = $cmd->fetch($className);
                $cmd->close();
            } catch (PDOException $e) {
                error_log("findOne!" . $pQueryName . " :" . $cmd->query);
                error_log("findOne!" . $pQueryName . " :" . $e->getMessage(), 0);
            }
        }
        return $result;
    }

    public function findBy(string $pQueryName, array $pParams)
    {
        $list = null;
        $pager = new Pager();
        $className = DataEntity::class;
        $params = array();
        if (isset($pParams) && is_array($pParams)) {
            $count = count($pParams);
            if ($count > 1) {
                $className = JsonHandler::get_class($pParams[0]->value);
                $pager = $pParams[1]->value;
            }
            if ($count > 2) {
                $params = array_slice($pParams, 2);
            }
        }
        $cmd = Command::buildQueryCommand($this->connection, $this->queries[$pQueryName], $params, $pager);        
        if ($cmd->isSuccess()) {
            try {
                $list = $cmd->fetchAll($className);
                $cmd->close();
            } catch (PDOException $e) {
                error_log("findBy!" . $pQueryName . " :" . $cmd->query);
                error_log("findBy!" . $pQueryName . " :" . $e->getMessage(), 0);
            }
        }
        return $list;
    }
    
    public function findPageBy(string $pQueryName, array $pParams)
    {
        $result = new Result();
        $list = null;
        $pager = new Pager();
        $className = DataEntity::class;
        $params = array();
        if (isset($pParams) && is_array($pParams)) {
            $count = count($pParams);
            if ($count > 1) {
                $className = JsonHandler::get_class($pParams[0]->value);
                $pager = $pParams[1]->value;
            }
            if ($count > 2) {
                $params = array_slice($pParams, 2);
            }
        }
        
        if ($pager != null && $pager->getLength() < 0) {
            $count = $this->findCount($pQueryName, $params);
            $result->setDataLength($count);
            $pager->setLength($count);
        }
        
        $cmd = Command::buildQueryCommand($this->connection, $this->queries[$pQueryName], $params, $pager);
        error_log("query :" . $cmd->query . 0);
        if ($cmd->isSuccess()) {
            try {
                $list = $cmd->fetchAll($className);
                $cmd->close();
                $result->setSuccess(true);
                $result->setData($list);
                //$result->setDataLength(count($list));
            } catch (PDOException $e) {
                $result->setSuccess(false);
                $result->setMessage("Okuma hatasi");
                error_log("findBy!" . $pQueryName . " :" . $cmd->query);
                error_log("findBy!" . $pQueryName . " :" . $e->getMessage(), 0);
            }
        }
        return $result;
    }

    public function save(string $pFnName, DataEntity $pEntity)
    {
        $result = new Result();
        self::checkAndGeneratePk($pEntity);
        $cmd = Command::buildCommand($this->connection, $pEntity);
        if ($cmd->isSuccess()) {
            try {
                $result->setSuccess($cmd->execute());
                $cmd->close();
            } catch (PDOException $e) {
                error_log("save! query :" . $cmd->query);
                error_log("save! message :" . $e->getMessage(), 0);
            }
        }
        if ($result->isSuccess()) {
            $result->setMessage("Kaydetme tamamlandi.");
            $pEntity->accept();
        } else {
            $result->setMessage("Kaydetme başarısız oldu.");
        }
        $result->setData($pEntity);
        return $result;
    }

    public function saveAll(string $pFnName, array $pEntities)
    {
        $result = new Result();

        if (is_array($pEntities)) {
            $x = $pEntities[0];
            $cmd = Command::buildCommand($this->connection, $x);
            if ($cmd->isSuccess()) {
                try {
                    foreach ($pEntities as $x) {
                        $cmd->refreshValues($x);
                        $result->setSuccess($cmd->execute($this->params));
                    }
                    $cmd->close();
                } catch (PDOException $e) {
                    error_log("saveAll!" . query . " :" . $cmd->query);
                    error_log("saveAll!" . message . " :" . $e->getMessage(), 0);
                }
            }
        }

        if ($result->isSuccess()) {
            $result->setMessage("Kaydetme tamamlandi.");
        }
        $result->setData($pEntities);
        return $result;
    }

    public function execute(string $pQuery, array $pParams)
    {
        $result = new Result();
        $cmd = Command::buildQueryCommand($this->connection, $pQuery, $pParams);
        if ($cmd->isSuccess()) {
            try {
                $result->setSuccess($cmd->execute());
                $cmd->close();
            } catch (PDOException $e) {
                error_log("execute!" . query . " :" . $cmd->query);
                error_log("execute!" . message . " :" . $e->getMessage(), 0);
            }
        }
        if ($result->isSuccess()) {
            $result->setMessage("islem tamamlandi.");
        }
        return $result;
    }

    public function executeAll(string $pFnName, array $pParams)
    {
        $result = new Result();

        if (is_array($pParams)) {
            $success = true;
            foreach ($pParams as $x1) {
                if ($success) {
                    $result = $this->execute($x1->name, $x1->value);
                    $success = $result->isSuccess();
                }
            }
        }
        return $result;
    }

    public function saveAtomic(string $pFnName, array $params)
    {
        $result = new Result();
        $temp = new Result(true);

        if (is_array($params)) {
            $this->connection->beginTransaction();
            foreach ($params as $x1) {
                if ($x1->name == "list") {
                    foreach ($x1->value as $x2) {
                        if (! is_array($x2)) {
                            if (! $x2->isUnchanged() && $temp->isSuccess()) {
                                $temp = $this->save("save", $x2);
                            }
                        } else {
                            if (count($x2) > 0 && $temp->isSuccess()) {
                                $temp = $this->saveAll("saveAll", $x2);
                            }
                        }
                    }
                } else if ($x1->name == "data") {
                    if ($x1->value != null) {
                        if (! is_array($x1->value)) {
                            if (! $x1->value->isUnchanged() && $temp->isSuccess()) {
                                $temp = $this->save("save", $x1->value);
                            }
                        } else {
                            if (count($x1->value) > 0 && $temp->isSuccess()) {
                                $temp = $this->saveAll("saveAll", $x1->value);
                            }
                        }
                    }
                } else {
                    if (! is_array($x1)) {
                        $temp = $this->execute($x1->name, $x1->value);
                    } else {
                        $temp = $this->executeAll("executeAll", $x1);
                    }
                }
            }
            if ($temp->isSuccess()) {
                $this->connection->commit();
                $result->setMessage("saveAtomic! :" . "basari");
            } else {
                $this->connection->rollback();
                $result->setMessage("saveAtomic! :" . "hata olustu");
            }
            $result->setSuccess($temp->isSuccess());
        }

        return $result;
    }

    private function checkAndGeneratePk($pEntity)
    {
        if ($pEntity->isNew()) {
            [
                $cmd,
                $keys
            ] = Command::buildFindPkCommand($this->connection, $pEntity);
            if (! is_null($cmd) && $cmd->isSuccess()) {
                try {
                    $result = $cmd->fetch(DataEntity::class);
                    $cmd->close();

                    foreach ($keys as $key) {
                        if (! is_null($result) && ! is_null($result->get($key))) {
                            $pEntity->set($key, $result->get($key));
                        } else {
                            $pEntity->set($key, 0);
                        }
                    }
                } catch (\PDOException $e) {
                    error_log("findPk!" . $cmd->query);
                    error_log("findPk!" . $e->getMessage(), 0);
                }
            }
        }
    }
}