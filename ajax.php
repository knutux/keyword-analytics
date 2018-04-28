<?php

require __DIR__ . '/vendor/autoload.php';

$error = NULL;

try {
    $db = \KeywordAnalytics\Database::GetInstance();
    $model = new \KeywordAnalytics\Model ();
    $postArgs = filter_input_array(INPUT_POST);
    $isLogedIn = $model->isLoggedIn ($db, $postArgs, $error);
    if (!$isLogedIn)
        \KeywordAnalytics\Views\Ajax::writeError('Unauthorized', 403);
    else
        {
        \KeywordAnalytics\Views\Ajax::handleMessage($db, $model, $postArgs);
        }
} catch (KeywordAnalytics\SetupException $ex) {
    \KeywordAnalytics\Views\Ajax::writeError('Database not setup', 500);
    exit();
    }
