<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace KeywordAnalytics;

/**
 * Description of Model
 *
 * @author Andrius R. <knutux@gmail.com>
 */
class Model
    {
    private $model = false;
    private $errors = [];
    
    const SESSION_VERSION = 2;
    const PREFIX_OLD_ID = "old_";

    public function isLoggedIn (Database $db, $postData, &$error = null)
        {
        if (!$db->isCreated())
            return true; // this will lead to exception which will redirect to setup page

        if ($this->checkUserAuth ())
            return true;

        // not logged in, check if there is a post data
        if (empty ($postData[\KeywordAnalytics\Views\Common::FIELD_USERNAME]) || empty ($postData[\KeywordAnalytics\Views\Common::FIELD_PASSWORD]))
            return false;

        if ($db->checkPassword ($postData[\KeywordAnalytics\Views\Common::FIELD_USERNAME], $postData[\KeywordAnalytics\Views\Common::FIELD_PASSWORD], $id))
            {
            session_start();
            $_SESSION["auth_version"] = self::SESSION_VERSION;
            $_SESSION["auth_id"] = $id;
            session_commit();
            header('Location: index.php');
            exit();
            }

        return false;
        }

    public function getUserId (\KeywordAnalytics\Database $db) : ?int
        {
        return $_SESSION["auth_id"] ?? null;
        }

    public function checkUserAuth ()
        {
        session_start();
        if( isset($_SESSION["auth_version"]) && $_SESSION["auth_version"] === self::SESSION_VERSION )
            {
            return TRUE;
            }
        else
            {
            session_destroy();
            return FALSE;
            }
        }

    public function getJson ()
        {
        return json_encode ($this->model);
        }

    protected function createModelObject (Database $db)
        {
        return (object)
                [
                    'version' => $db->getVersion(),
                    'errors' => [],
                    'baseUrl' => 'ajax.php'
                ];
        }

    public static function createFieldDefinition (string $label, string $type = "text", string $placeholder = null) : \stdClass
        {
        return (object)['dbName' => $label, 'label' => $label, 'type' => $type, 'placeholder' => $placeholder, 'readonly' => false, 'isId' => false];
        }

    public static function createCalculatedFieldDefinition (string $id, callable $fn) : \stdClass
        {
        $field = self::createFieldDefinition ($id);
        $field->readonly = true;
        $field->dbName = $fn;
        return $field;
        }

    public static function markFieldReadonly (\stdClass $field) : \stdClass
        {
        $field->readonly = true;
        return $field;
        }

    public static function markIdField (\stdClass $field) : \stdClass
        {
        $field->readonly = true;
        $field->isId = true;
        return $field;
        }

    public function prepare (Database $db)
        {
        $this->model = $this->createModelObject ($db);
        $this->model->mode = 'analytics';
        $this->model->kwd = '';
        $this->model->errors = $this->errors;
        }

    public function getKeywordStats (Database $db, string $keyword)
        {
        
        }
    }
