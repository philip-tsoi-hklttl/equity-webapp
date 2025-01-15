<?php
require_once ('vendor/autoload.php');
require_once ('config.php');

use EHAERER\Salesforce\Authentication\PasswordAuthentication;
use EHAERER\Salesforce\SalesforceFunctions;


// STEP 1: Authorization, get bearer token

$options = [
    'grant_type' => 'password',
    'client_id' => SF_CLIENT_ID,
    'client_secret' => SF_CLIENT_SECRET,
    'username' => SF_USERNAME,
    'password' => SF_PASSWORD.SF_SECURITY,
];

$salesforce = new PasswordAuthentication($options);

$endPoint = "https://login.salesforce.com/";
$salesforce->setEndpoint($endPoint);
//var_dump($options);
$salesforce->authenticate();    //HTTP 500 ERROR

$accessToken = $salesforce->getAccessToken();
$instanceUrl = $salesforce->getInstanceUrl();
$httpheader_authorization = "Authorization: Bearer ".$accessToken;


// Set the list of events to subscribe to
$channels = array(
    '/event/Job__ChangeEvent',
    '/event/Job_Item__ChangeEvent'
);

// Subscribe to each channel
foreach ($channels as $channel) {
    $subscribeData = array(
        'replay' => -1, // Replay all events
        'advice' => array(
            'timeout' => 0 // Keep connection open indefinitely
        ),
        'query' => "SELECT Id, Your_Field__c FROM $channel"
    );

    $subscribeUrl = $loginInfo['instance_url'] . '/services/data/v52.0/cometd/48.0';

    // Process received events
    while (true) {
        $subscribeRequest = curl_init($subscribeUrl);
        curl_setopt_array($subscribeRequest, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($subscribeData),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
                'Accept: application/json'
            )
        ));

        // Check for new events
        $response = curl_exec($subscribeRequest);

        // Parse the response
        list($headers, $body) = explode("\r\n\r\n", $response, 2);
        $body = json_decode($body, true);

        if (!empty($body)) {
            foreach ($body as $event) {
                if (!empty($event['data'])) {
                    echo "Received event: " . json_encode($event['data']) . PHP_EOL;
                }
            }
        } else {
            echo "No event received for channel: $channel" . PHP_EOL;
        }

        // Close the request
        curl_close($subscribeRequest);

        // Sleep for a short interval before checking for new events again
        sleep(1);
    }
}