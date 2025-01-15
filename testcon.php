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


// STEP 2: Get all items in Job__c (Using CURL)
$query_url = $instanceUrl.'/services/data/v59.0/composite/sobjects/Job__c/';
//$query_url = $instanceUrl."/services/data/v59.0/queryAll/?q=SELECT+FIELDS(STANDARD)+FROM+Job_Item__c+LIMIT+200";


$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $query_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        $httpheader_authorization,
        "Content-type: application/json",
    ),
));
$response = curl_exec($curl);
curl_close($curl);

$json = json_decode($response);
//$job_length = count($json->{'records'});
$job_length = $json->{'totalSize'};


/*
echo "Total number of jobs = ".$job_length;
echo "<br/>";
echo "<br/>";




echo "<table>";
echo "<tr>";
echo "<th></th>";
echo "<th>ID</th>";
echo "<th>NAME</th>";
echo "<th>Created Date</th>";
echo "<th>Last Modified Date</th>";
echo "<th>IsDeleted</th>";

echo "</tr>";

for($i=0; $i<$job_length; $i++){
    $job_id = $json->{'records'}[$i]->{"Id"};
    //echo $job_id;
    //echo "<br/>";

    echo "<tr>";
    echo "<td>".$i."</td>";
    echo "<td>".$job_id."</td>";
    echo "<td>".$json->{'records'}[$i]->{"Name"}."</td>";
    echo "<td>".$json->{'records'}[$i]->{"CreatedDate"}."</td>";
    echo "<td>".$json->{'records'}[$i]->{"LastModifiedDate"}."</td>";
    echo "<td>".$json->{'records'}[$i]->{"IsDeleted"}."</td>";    
    echo "</tr>";

    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $instanceUrl.'/services/data/v59.0/sobjects/Job__c/'.$job_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            $authorization,
            "Content-type: application/json",
        ),
    ));
    
    $response_small = curl_exec($curl);
    $json_small = json_decode($response_small);
    $name = $json_small->{'Name'};
    echo $name;
    echo "<br/>";
    $Product__c = $json_small->{'Product__c'};
    echo $Product__c;
    echo "<br/>";
    echo "----------------------------------------------------------";

    echo "<br/>";
    echo "<br/>";
    echo "<br/>";
    
    

}
//echo $json->{'recentItems'}[0]->{"Name"};
//Object ID = $json->{'recentItems'}[0]->{"Id"};
//Object Name = $json->{'recentItems'}[0]->{"Name"};
echo "</table>";
*/

//$json = json_encode($response);
//echo $json->recentItems;


// STEP 2: Get all items in Job__c (Using SF library)
$salesforceFunctions = new SalesforceFunctions($instanceUrl, $accessToken, "v59.0");

/* query function */
$query_JI = 'SELECT Id, Bulk_Date__c, Job__c, Name FROM Job_Item__c';
$result_JI = $salesforceFunctions->query($query_JI);


$query_JB = "SELECT CompanyName__c, Job_Classify__c, CS_Checked__c, Client_ID__c, Name FROM Job__c";
$result_JB = $salesforceFunctions->query($query_JB);

//print '############ query function with 2 accounts ############' . "\r\n";
//print '<pre>';
//print_r($result_JI);
//print '</pre>';
//print '<pre>';
//print_r($result_JB);
//print '</pre>';


//print_r($result_JI["records"][0]["Name"]);



//$a = $salesforceFunctions->customEndpoint($query_url, null, 200, [], 'GET');
//$a = $salesforceFunctions->retrieve();

//echo json_decode($a);
/**/

//$options = require 'config.php';

//use bjsmasth\Salesforce\Authentication\AuthenticationInterface;
//use bjsmasth\Salesforce\Authentication\PasswordAuthentication;

//$access_token = $salesforce->getAccessToken();
//$instance_url = $salesforce->getInstanceUrl();

//$salesforce = new PasswordAuthentication($options);
//$development = false; // set by default to sandbox
// Sandbox: https://test.salesforce.com/, Production: https://login.salesforce.com/ 
//$endPoint = $development ? 'https://test.salesforce.com/' : 'https://login.salesforce.com/';
//$endPoint = "https://login.salesforce.com/";
//$salesforce->setEndpoint($endPoint);
//$salesforce->authenticate();


//$accessToken = $salesforce->getAccessToken();
//$instanceUrl = $salesforce->getInstanceUrl();

//$salesforceFunctions = new SalesforceFunctions($instanceUrl, $accessToken, "v52.0");
