<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace KeywordAnalytics;

/**
 * Description of DatabaseCore
 *
 * @author Andrius R. <knutux@gmail.com>
 */
abstract class DatabaseCore
    {
    const TABLE_VERSION = "Version";
    const TABLE_PERMISSION_GROUP = "Permission Groups";
    const TABLE_USER = "Users";
    const TABLE_USER_PERMISSION = "User Permissions";

    const STATE_INACTIVE = 0;
    const STATE_ACTIVE = 1;
    const STATE_DELETED = -1;
    const STATE_ADMIN = 100;

    protected $db = false;
    protected $isCreated = NULL;
    protected $debug = false;

    protected function  __construct (bool $debug = false)
        {
        $this->debug = $debug;
        }

    protected abstract function getDBName () : string;
    protected abstract function getLatestVersionId () : int;

    protected function ensureInitialized (bool $checkIsValid = true)
        {
        if (false === $this->db)
            {
            $this->db = new \SQLite3  ($this->getDBName ());
            }

        if ($checkIsValid)
            {
            if (NULL === $this->isCreated)
                {
                $this->isCreated = $this->_tableExists(self::TABLE_VERSION);
                if (!$this->isCreated)
                    throw new SetupException("Database not set up");
                $version = $this->getVersion();
                if (!is_numeric($version))
                    {
                    $this->isCreated = false;
                    throw new SetupException("Database version not found");
                    }
                }
            }
        }

    protected function getTableDefinitions () : array
        {
        $tables = [];
        $tables[self::TABLE_VERSION] = (object)array ('columns' => array ("`Version` STRING"), 'unique' => array ("`Version`"), 'index' => null, 'initialize' => false);
        $tables[self::TABLE_USER] = (object)array ('columns' => array ("`Id` INTEGER PRIMARY KEY", "`Account Name` STRING", "`Full Name` STRING", "`Email` STRING", "`Password` STRING", "`State` INTEGER DEFAULT 0"),
                                                  'unique' => array ("`Account Name`", "`Email`"), 'index' => null, 'initialize' => false);
        $tables[self::TABLE_PERMISSION_GROUP] = (object)array ('columns' => array ("`Id` INTEGER PRIMARY KEY", "`Group Name` STRING, `Is Default` INTEGER DEFAULT 0"), 'unique' => array ("`Group Name`"), 'index' => null, 'initialize' => true);
        $tables[self::TABLE_USER_PERMISSION] = (object)array ('columns' => array ("`User Id` INTEGER, `Permission Group Id` INTEGER"), 'unique' => array ("`User Id`, `Permission Group Id`"), 'index' => null, 'initialize' => false);
        return $tables;
        }

    public function escapeString (string $str = null, bool $addQuotes = false) : string
        {
        $string = $this->db->escapeString ($str);
        return $addQuotes ? (null === $str ? 'NULL' : "'$string'") : $string;
        }

    public function encodePassword (string $user, string $password) : string
        {
        // use password_verify to verify
        return password_hash ($password, PASSWORD_DEFAULT);
        }

    public function checkPassword (string $user, string $password, int &$id = null) : bool
        {
        $tableName = self::TABLE_USER;
        $user = $this->db->escapeString ($user);
        $rows = $this->executeSelect ($tableName, "SELECT `Password`, `Id` FROM `$tableName` WHERE (`Account Name`='$user' OR `Email` LIKE '$user') AND `State` > 0", $error);
        if (empty ($rows))
            return false;
        $hash = $rows[0]['Password'];
        $id = $rows[0]['Id'];
        return password_verify ($password, $hash);
        }

    protected function initializeTable (string $tableName, string &$error = null) : bool
        {
        switch ($tableName)
            {
            case self::TABLE_PERMISSION_GROUP:
                if (false === $this->executeInsert($tableName, "(`Group Name`, `Is Default`) VALUES ('Public', 1)", $error))
                    return false;

                break;

            default:
                break;
            }

        return true;
        }

    public function initializeDB (string $user, string $password, &$error) : bool
        {
        $error = "";

        foreach ($this->getTableDefinitions() as $tableName => $def)
            {
            if (!$this->createTable ($tableName, $def->columns, $def->unique, $def->index ?? [], $error))
                {
                return false;
                }
                
            if ($def->initialize)
                {
                if (!$this->initializeTable ($tableName, $error))
                    return false;
                }
            }

        $pwd = $this->encodePassword ($user, $password);
        $state = self::STATE_ADMIN;
        $userId = $this->executeInsert(self::TABLE_USER, "(`Account Name`, `Full Name`, `Email`, `Password`, `State`) VALUES ('$user', 'Administrator', '$user', '$pwd', $state)", $error);
        if (false === $userId)
            return false;

        if (false === $this->executeInsert(self::TABLE_USER_PERMISSION, "(`User Id`, `Permission Group Id`) VALUES ($userId, 1)", $error))
            return false;

        $versionId = $this->getLatestVersionId();
        if (false === $this->executeInsert(self::TABLE_VERSION, "(`Version`) VALUES ($versionId)", $error))
            return false;
        return true;
        }

    private function createIndexStatement (string $tableName, string $type, array $indexes)
        {
        if (!empty ($indexes))
            {
            $nextIdx = 1;
            return implode ("\n", array_map (function ($index) use ($type, $tableName, &$nextIdx)
                {
                $name = "{$tableName}_{$type}_$nextIdx";
                $nextIdx++;
                $indexType = "UNIQUE" == strtoupper($type) ? "UNIQUE INDEX" : $type;
                return "CREATE $indexType `$name` ON `$tableName` ($index);";
                }, $indexes));
            }
        else
            return "";
        }

    private function createTable (string $tableName, array $cols, array $uniqueIndexes, array $indexes, &$error) : bool
        {
        $error = NULL;
        $exists = $this->_tableExists($tableName);
        if ($exists)
            return true;
        $uniqueIndexSql = $this->createIndexStatement ($tableName, "UNIQUE", $uniqueIndexes);
        $indexSql = $this->createIndexStatement ($tableName, "INDEX", $indexes);
        $cols[] = "`Created On` DATETIME DEFAULT CURRENT_TIMESTAMP";
        $colsStr = implode (",\n    ", $cols);

        $sql = <<<EOT
BEGIN;
CREATE TABLE `$tableName`
    (
    $colsStr
    );
$uniqueIndexSql
$indexSql
COMMIT;
EOT;
        $success = @$this->db->exec($sql);
        if (!$success)
            {
            $error = $this->db->lastErrorMsg ();
            if ($this->debug)
                {
                //var_dump ();exit();
                $error .= " ( $sql )";
                }
            }
        return $success;
        }

    protected function checkExecutionError (bool $result, string $sql, string &$error = null) : bool
        {
        if (!$result)
            {
            $error = $this->db->lastErrorMsg ();
            if ($this->debug)
                {
                $error .= " ( $sql )";
                }
            return false;
            }

        return true;
        }

    protected function executeTruncateTable (string $tableName, string &$error = null) : bool
        {
        $success = @$this->db->exec("DELETE FROM `$tableName`");
        if (!$this->checkExecutionError ($success, "TRUNCATE $tableName", $error))
            return false;

        return true;
        }

    public function executeInsert (string $tableName, string $colsWithValues, &$error)
        {
        // TODO: implement permission check
        $sql = <<<EOT
INSERT INTO `$tableName`
$colsWithValues
EOT;
        $success = @$this->db->exec($sql);
        if (!$this->checkExecutionError ($success, $sql, $error))
            return false;

        return $this->db->lastInsertRowID ();
        }

    public function executeUpdate ($tableName, $setAndWhere, &$error = null, $accessAlreadyChecked = false)
        {
        $permissionFilter = $accessAlreadyChecked ? "1=1" : $this->createPermissionFilter ('Permission Group');
        $sql = "UPDATE `$tableName` $setAndWhere AND $permissionFilter";
        $success = @$this->db->exec($sql);
        if (!$this->checkExecutionError ($success, $sql, $error))
            return false;
        return true;
        }
        
    public function executeSelectSingle (string $tableName, string $sql, string &$error = null)
        {
        $ret = @$this->db->querySingle($sql);
        if (!$this->checkExecutionError (false !== $ret, $sql, $error))
            return false;

        return $ret;
        }

    public function executeSelect (string $tableName, string $sql, string &$error = null)
        {
        $ret = @$this->db->query($sql);
        if (!$this->checkExecutionError (false !== $ret, $sql, $error))
            return false;

        $rows = [];
        while ($row = $ret->fetchArray (SQLITE3_ASSOC))
            $rows[] = $row;
        return $rows;
        }

    public function getVersion ()
        {
        $this->ensureInitialized();
        $version = $this->db->querySingle("SELECT MAX(`version`) FROM `".self::TABLE_VERSION."`");
        return $version;
        }

    protected function createPermissionFilter (string $column) : string
        {
        return "1=1";
        }

    protected function _tableExists (string $tableName) : bool
        {
        $exists = $this->db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='$tableName';");
        return !empty($exists);
        }

    public function tableExists (string $tableName) : bool
        {
        $this->ensureInitialized();
        return $this->_tableExists($tableName);
        }

    public function isCreated () : bool
        {
        $this->ensureInitialized(false);
        if (!$this->_tableExists(self::TABLE_VERSION))
            return false;
        return $this->getLatestVersionId() == $this->getVersion();
        }
    }
