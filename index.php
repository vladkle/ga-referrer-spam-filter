<?php
//XXX: don't forget to run composer install

session_start();
include 'vendor/autoload.php';
$clientId     = '<CLIENT_ID>';
$clientSecret = '<CLIENT_SECRET>';
$redirectUri  = '<REDIRECT_URI>';
/** @var string|string[] $accountIds id or ids of accounts to add the exclude filter to */
$accountIds   = ['<ACCOUNT_ID>'];

$excludedItems = [
    'simple-share-buttons.com',
    'googlsucks.com',
    's.click.aliexpress.com',
    'simple-share-buttons.com',
    'buttons-for-website.com',
    'best-seo-solution.com',
    'buttons-for-your-website.com',
    'get-free-traffic-now.com',
    'best-seo-offer.com',
    'youporn-forum.ga',
    'pornhub-forum.ga',
    'pornhub-forum.uni.me',
    'theguardlan.com',
    'social-buttons.com',
    'hulfingtonpost.com',
    'free-share-buttons.com',
    'buy-cheap-online.info',
    'guardlink.org',
    'event-tracking.com',
    'buttons-for-your-website.com',
    '01webdirectory.com',
];


function createGoogleClient($clientId, $clientSecret, $redirectUri)
{
    $client = new Google_Client();
    $client->setApplicationName('Referrer Spam Filter');

    $client->setClientId($clientId);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirectUri);
    $client->addScope('https://www.googleapis.com/auth/analytics.edit');

    return $client;
}

function setFilter($name, $analytics, $accountIds, $excluded)
{
    $filter = new Google_Service_Analytics_Filter();
    $filter->setName($name);
    $filter->setType('EXCLUDE');
    $details = new Google_Service_Analytics_FilterExpression();
    $details->setField('CAMPAIGN_SOURCE');
    $details->setMatchType('MATCHES');
    $details->setExpressionValue($excluded);
    $details->setCaseSensitive(false);

    $filter->setExcludeDetails($details);

    if (!is_array($accountIds))
        $accountIds = [$accountIds];

    foreach ($accountIds as $accountId)
        $analytics->management_filters->insert($accountId, $filter);
}

function createExcludedRegexes($excludedItems)
{
    $excludedRegexes = [];
    $currentRegex    = '';
    $maxRegexLength  = 250;
    foreach ($excludedItems as $item)
    {
        $item = preg_quote(trim($item));
        if (strlen($currentRegex) + strlen($item) > $maxRegexLength)
        {
            $excludedRegexes[] = '(' . trim($currentRegex, '|') . ')';
            $currentRegex      = '';
        }
        $currentRegex .= $item . '|';
    }
    $excludedRegexes[] = '(' . trim($currentRegex, '|') . ')';

    return $excludedRegexes;
}

try
{
    $client = createGoogleClient($clientId, $clientSecret, $redirectUri);
    if (isset($_GET['code']))
    {
        $analytics = new Google_Service_Analytics($client);
        $client->authenticate($_GET['code']);
        $i = 1;

        $excludedRegexes = createExcludedRegexes($excludedItems);
        foreach ($excludedRegexes as $excludedRegex)
            setFilter('Exclude Referrer Spam #' . $i++, $analytics, $accountIds, $excludedRegex);

        echo 'Done';
    }
    else
    {
        $authUrl = $client->createAuthUrl();
        echo "<a class='login' href='" . $authUrl . "'>Login via google account</a>";
    }
}
catch (Google_Service_Exception $e)
{
    print 'There was an Analytics API service error <br>' . $e->getCode() . ': ' . $e->getMessage();
}
catch (Exception $e)
{
    print 'There was a general API error <br>' . $e->getCode() . ': ' . $e->getMessage();
}
