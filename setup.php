<?php

require __DIR__ . '/vendor/autoload.php';

$db = \KeywordAnalytics\Database::GetInstance(true);
$model = new \KeywordAnalytics\Model ();

if ($db->isCreated () && is_numeric ($db->getVersion()))
    {
    header('Location: index.php');
    exit();
    }

$error = "";
if (!empty ($_POST[KeywordAnalytics\Views\Common::FIELD_USERNAME]) && !empty ($_POST[KeywordAnalytics\Views\Common::FIELD_PASSWORD]))
    {
    if ($db->initializeDB ($_POST[KeywordAnalytics\Views\Common::FIELD_USERNAME], $_POST[KeywordAnalytics\Views\Common::FIELD_PASSWORD], $error))
        {
        header('Location: index.php');
        exit();
        }
    }

KeywordAnalytics\Views\Common::writeHTMLHeader("Keyword Analytics - Setup Database", "Keyword Analytics.");
KeywordAnalytics\Views\Common::writeLoginForm("Define Admin User", "Create", $error);
KeywordAnalytics\Views\Common::writeHTMLFooter();
