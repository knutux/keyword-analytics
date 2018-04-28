<?php

require __DIR__ . '/vendor/autoload.php';

$error = NULL;

try {
    $db = \KeywordAnalytics\Database::GetInstance();
    $model = new \KeywordAnalytics\Model ();
    $isLogedIn = $model->isLoggedIn ($db, $_POST, $error);
    if ($isLogedIn)
        $model->prepare ($db);
} catch (KeywordAnalytics\SetupException $ex) {
    header('Location: setup.php');
    exit();
    }

KeywordAnalytics\Views\Common::writeHTMLHeader("Keyword Analytics", "Keyword Analytics.");

if (!$isLogedIn)
    KeywordAnalytics\Views\Common::writeLoginForm("Please enter your credentials", "Login", $error);
else
    \KeywordAnalytics\Views\Analytics::write($model);

KeywordAnalytics\Views\Common::writeHTMLFooter();
