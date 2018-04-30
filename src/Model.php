<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace KeywordAnalytics;

use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\v201710\cm\CampaignService;
use Google\AdsApi\AdWords\v201710\cm\OrderBy;
use Google\AdsApi\AdWords\v201710\cm\Paging;
use Google\AdsApi\AdWords\v201710\cm\Selector;
use Google\AdsApi\AdWords\v201710\cm\SortOrder;
use Google\AdsApi\AdWords\v201710\cm\Location;
use Google\AdsApi\AdWords\v201710\cm\Language;
use Google\AdsApi\AdWords\v201710\cm\NetworkSetting;
use Google\AdsApi\AdWords\v201710\o\LanguageSearchParameter;
use Google\AdsApi\AdWords\v201710\o\LocationSearchParameter;
use Google\AdsApi\AdWords\v201710\o\TargetingIdeaSelector;
use Google\AdsApi\AdWords\v201710\o\RequestType;
use Google\AdsApi\AdWords\v201710\o\IdeaType;
use Google\AdsApi\AdWords\v201710\o\AttributeType;
use Google\AdsApi\AdWords\v201710\o\RelatedToQuerySearchParameter;
use Google\AdsApi\AdWords\v201710\o\NetworkSearchParameter;
use Google\AdsApi\AdWords\v201710\o\TargetingIdeaService;
use Google\AdsApi\Common\OAuth2TokenBuilder;

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

    public function getGoogleApiSession (string &$error = null)
        {
        $file = __DIR__.'/../adsapi_php.ini';
        if (!file_exists($file))
            {
            $error = "Google API configiration file ($file) does not exist";
            return false;
            }

        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile($file)->build();
        $session = (new AdWordsSessionBuilder())->fromFile($file)->withOAuth2Credential($oAuth2Credential)->build();

        return $session;
        }

    public function getKeywordStats (Database $db, string $keyword)
        {
        $model = $this->createModelObject ($db);
        $model->success = false;
        
        $pageSize = 100;

        $session = $this->getGoogleApiSession ($error);
        if (false === $session)
            {
            $model->errors[] = $error;
            return $model;
            }

        $selector = new TargetingIdeaSelector();
        $selector->setRequestType(RequestType::IDEAS);
        $selector->setIdeaType(IdeaType::KEYWORD);

        $selector->setRequestedAttributeTypes(
            [
                AttributeType::KEYWORD_TEXT, // Represents the keyword text for the given keyword idea.
                AttributeType::SEARCH_VOLUME, // Represents either the (approximate) number of searches for the given keyword idea on google.com or google.com and partners, depending on the user's targeting.
                AttributeType::AVERAGE_CPC, // Represents the average cost per click historically paid for the keyword.
                AttributeType::COMPETITION, // Represents the relative amount of competition associated with the given keyword idea, relative to other keywords. This value will be between 0 and 1 (inclusive).
                AttributeType::TARGETED_MONTHLY_SEARCHES // Represents the (approximated) number of searches on this keyword idea (as available for the past twelve months), targeted to the specified geographies.
            ]
        );
        
        $paging = new Paging();
        $paging->setStartIndex(0);
        $paging->setNumberResults($pageSize);
        $selector->setPaging($paging);

        $searchParameters = [];
        
        // Create related to query search parameter.
        $relatedToQuerySearchParameter = new RelatedToQuerySearchParameter();
        $relatedToQuerySearchParameter->setQueries(
            [
                $keyword
            ]
        );
        $searchParameters[] = $relatedToQuerySearchParameter;

        $networkSetting = new NetworkSetting();
        $networkSetting->setTargetGoogleSearch(true);
        $networkSetting->setTargetSearchNetwork(false);
        $networkSetting->setTargetContentNetwork(false);
        $networkSetting->setTargetPartnerSearchNetwork(false);

        $networkSearchParameter = new NetworkSearchParameter();
        $networkSearchParameter->setNetworkSetting($networkSetting);
        $searchParameters[] = $networkSearchParameter;

        $locationUS = new Location();
        // UKI - 2826
        $locationUS->setId(2840); // US (https://fusiontables.google.com/DataSource?docid=1Jlxrqc1dU3a9rsNW2l5xxlmQEKUu0dIPusImi41B#rows:id=1)
        $searchParameters[] = new LocationSearchParameter(null, [$locationUS]);
        
        $english = new Language();
        $english->setId(1000);
        $searchParameters[] = new LanguageSearchParameter(null, [$english]);
        $selector->setSearchParameters ($searchParameters);

        $adWordsServices = new AdWordsServices();

        $targetingIdeaService = $adWordsServices->get($session, TargetingIdeaService::class);
        $page = $targetingIdeaService->get($selector);
var_dump ($page);
                $totalNumEntries = 0;
        do {
            // Make the get request.
            $page = $campaignService->get($selector);
            // Display results.
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $campaign) {
                    printf(
                        "Campaign with ID %d and name '%s' was found.\n",
                        $campaign->getId(),
                        $campaign->getName()
                    );
                }
            }
            // Advance the paging index.
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + 100
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

        printf ("Number of results found: %d\n", $totalNumEntries);
        return $model;
        }
    }
