<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace KeywordAnalytics;

class Database extends DatabaseCore
    {
    const TABLE_KEYWORDS = "Keywords";

    protected function  __construct (bool $debug = false)
        {
        parent::__construct ($debug);
        }

    private static $s_instance = false;
    public static function GetInstance(bool $debug = false)
        {
        if (false === self::$s_instance)
            self::$s_instance = new Database($debug);

        return self::$s_instance;
        }

    protected function getDBName () : string
        {
        return "key-analytics.db";
        }

    protected function getLatestVersionId () : int
        {
        return 1;
        }

    protected function getTableDefinitions () : array
        {
        $tables = parent::getTableDefinitions();
        return $tables;
        }

    public function clearDatabase(string &$error = null) : bool
        {
        return true;
        }
        
    }