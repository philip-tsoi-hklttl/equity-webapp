<?php
date_default_timezone_set('Asia/Hong_Kong');  // Set timezone to GMT+8

require_once ('vendor/autoload.php');
require_once ('config.php');

use EHAERER\Salesforce\Authentication\PasswordAuthentication;
use EHAERER\Salesforce\SalesforceFunctions;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// STEP 0: preparation

if ($_SERVER['CONTENT_TYPE'] != 'application/json') {
    // header($_SERVER['SERVER_PROTOCOL']. ' 500 Internal Server Error');
    // exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : 'view';

$result = array();
$extra = array();
$debugObj = array();
$debug = isset($_GET['debug']) ? $_GET['debug'] : (DEBUG == true);
$pretty = isset($_GET['pretty']) ? $_GET['pretty'] : false;

$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$time = time();
$timestart = microtime(true);

$result['status'] = -1;
$result['error'] = 'Fatal error.';

switch ($action) {
    default:
    case 'view':
        // provide batch number then list
        $batch = isset($_GET['batch']) ? $_GET['batch'] : '19700101-000000';
        $sqlView = "SELECT * FROM batch WHERE batch='" . $batch . "' AND 1 LIMIT 1";
        $debugObj['sql'] = $sqlView;
        $resultView = $pdo->query($sqlView)->fetchAll();

        $result['data']['id'] = $resultView[0]['id'];
        $result['data']['batch'] = $resultView[0]['batch'];
        $result['data']['sfobj'] = json_decode($resultView[0]['sfobj']);
        $result['data']['data'] = json_decode($resultView[0]['data']);
        $result['data']['debug'] = json_decode($resultView[0]['debug']);
        $result['data']['extra'] = json_decode($resultView[0]['extra']);
        $result['data']['status'] = $resultView[0]['status'];
        $result['data']['create_time'] = $resultView[0]['create_time'];

        if (count($resultView) >= 1) {
            $result['status'] = 0;
            $result['error'] = 'View data from database success';
        } else {
            $result['error'] = 'Database Error';
        }

        break;

    case 'exportexcel':
        $spreadsheet = new Spreadsheet();

        $batch = isset($_GET['batch']) ? $_GET['batch'] : '19700101-000000';
        $sqlView = "SELECT * FROM batch WHERE batch='" . $batch . "' AND 1 LIMIT 1";
        $debugObj['sql'] = $sqlView;
        $resultView = $pdo->query($sqlView)->fetchAll();

        $sfobj = json_decode($resultView[0]['sfobj'], true);
        $data = json_decode($resultView[0]['data'], true);

        $tablelist = array_keys($sfobj);
        // $debugObj["tablelist"] = $tablelist;
        // $debugObj["sfobj"] = $sfobj;
        $export = (1 == 1);

        for ($i = 0; $i < count($tablelist); $i++) {
            if ($i == 0) {
                ${'sheet_' . $i} = $spreadsheet->getActiveSheet();
            } else {
                ${'sheet_' . $i} = $spreadsheet->createSheet();
            }

            $sfobj_target = $sfobj[$tablelist[$i]];
            $table_heading = $sfobj_target['title'];
            ${'sheet_' . $i}->setTitle($sfobj_target['title']);
            ${'fieldlist_' . $i} = '';

            for ($j = 0; $j < count($sfobj_target['field']); $j++) {
                ${'fieldlist_' . $i} .= $sfobj_target['field'][$j] . ' ';
                // ${"sheet_".$i}->setCellValueByColumnAndRow($j+1, 1, $sfobj_target["field"][$j]);
                ${'sheet_' . $i}->setCellValue([$j + 1, 1], $sfobj_target['field'][$j]);
            }

            ${'datarecord_' . $i} = $data[$tablelist[$i]]['records'];
            // $debugObj["datarecord_".$i] =  ${"datarecord_".$i};

            for ($k = 0; $k < count(${'datarecord_' . $i}); $k++) {
                for ($j = 0; $j < count($sfobj_target['field']); $j++) {
                    ${'testttt_' . $i . '_' . $k . '_' . $j} = ${'datarecord_' . $i}[$k][$sfobj_target['field'][$j]];
                    // $debugObj["testttt_".$i."_".$k."_".$j] =  ${"testttt_".$i."_".$k."_".$j};
                    // ${"sheet_".$i}->setCellValueByColumnAndRow($j+1, $k+2, ${"datarecord_".$i}[$k][$sfobj_target["field"][$j]] );
                    ${'sheet_' . $i}->setCellValue([$j + 1, $k + 2], ${'datarecord_' . $i}[$k][$sfobj_target['field'][$j]]);
                }
            }
        }

        if ($export) {
            $filename = 'batch-' . $batch . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');  // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');  // always modified
            header('Cache-Control: cache, must-revalidate');  // HTTP/1.1
            header('Pragma: public');  // HTTP/1.

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');

            die();
        }

        break;

    case 'history':
        $from = isset($_GET['from']) ? intval($_GET['from']) : 0;
        $to = isset($_GET['to']) ? intval($_GET['to']) : 50;
        $sqlHistory = "SELECT id, batch, create_time, extra FROM batch ORDER BY id DESC LIMIT $from, $to";
        $debugObj['sql'] = $sqlHistory;
        $resultHistory = $pdo->query($sqlHistory)->fetchAll();

        for ($i = 0; $i < count($resultHistory); $i++) {
            $result['data'][$i]['id'] = $resultHistory[$i]['id'];
            $result['data'][$i]['batch'] = $resultHistory[$i]['batch'];
            $result['data'][$i]['create_time'] = $resultHistory[$i]['create_time'];
            $result['data'][$i]['extra'] = $resultHistory[$i]['extra'];
        }

        if (count($resultHistory) >= 1) {
            $result['status'] = 0;
            $result['error'] = 'Get historical data from database success';
        } else {
            $result['error'] = 'Database Error';
        }
        break;

    case 'historyCount':
        $sqlCount = 'SELECT COUNT(id) as total FROM batch';
        $debugObj['sql'] = $sqlCount;
        $resultCount = $pdo->query($sqlCount)->fetch();
        $result['total'] = $resultCount['total'];
        $result['status'] = 0;
        $result['error'] = 'Get total count from database success';
        break;

    case 'auth':
        // STEP 1: SF side Authorization, get bearer token
        $salesforce_opt = [
            'grant_type' => 'password',
            'client_id' => SF_CLIENT_ID,
            'client_secret' => SF_CLIENT_SECRET,
            'username' => SF_USERNAME,
            'password' => SF_PASSWORD . SF_SECURITY,
        ];

        $salesforce = new PasswordAuthentication($salesforce_opt);
        $salesforce_endPoint = 'https://login.salesforce.com/';
        $salesforce->setEndpoint($salesforce_endPoint);

        try {
            $salesforce->authenticate();
            $accessToken = $salesforce->getAccessToken();
            $instanceUrl = $salesforce->getInstanceUrl();
            // $httpheader_authorization = 'Authorization: Bearer ' . $accessToken;
            $debugObj['instanceUrl'] = $instanceUrl;
            $debugObj['accessToken'] = $accessToken;
            $result['batch'] = date('Ymd-His', $time);

            if ($accessToken != '') {
                $result['auth'] .= 'Salesforce authenticated, access token = ' . $accessToken . "\n";
            } else {
                $result['auth'] .= "Salesforce authentication failed.\n";
            }
        } catch (Exception $e) {
            $result['auth'] .= 'Salesforce authentication exception: ' . $e->getMessage() . "\n";
        }
        $result['status'] = 0;
        $result['error'] = 'SF auth success';
        // STEP 2: Get all items in specified SF objects (Using SF library)

        $salesforceFunctions = new SalesforceFunctions($instanceUrl, $accessToken, 'v' . SF_API_VERSION);

        $result['SF_options'] = $salesforce_opt;
        break;

    case 'create':
    case 'retrieve':
        // STEP 1: SF side Authorization, get bearer token
        $salesforce_opt = [
            'grant_type' => 'password',
            'client_id' => SF_CLIENT_ID,
            'client_secret' => SF_CLIENT_SECRET,
            'username' => SF_USERNAME,
            'password' => SF_PASSWORD . SF_SECURITY,
        ];

        $salesforce = new PasswordAuthentication($salesforce_opt);
        $salesforce_endPoint = 'https://login.salesforce.com/';
        $salesforce->setEndpoint($salesforce_endPoint);

        $salesforce->authenticate();

        $accessToken = $salesforce->getAccessToken();
        $instanceUrl = $salesforce->getInstanceUrl();
        $httpheader_authorization = 'Authorization: Bearer ' . $accessToken;

        $debugObj['instanceUrl'] = $instanceUrl;
        $debugObj['accessToken'] = $accessToken;
        $result['batch'] = date('Ymd-His', $time);

        // STEP 2: Get all items in specified SF objects (Using SF library)

        $salesforceFunctions = new SalesforceFunctions($instanceUrl, $accessToken, 'v' . SF_API_VERSION);

        $result['sfobj'] = $sfobj;

        $data_pass = true;
        foreach ($sfobj as $k => $v) {
            // $result["d_".$v["title"]] = $salesforceFunctions->describe($v);

            ${'query_' . $k} = '';
            if (in_array('CreatedDate', $v['field'])) {
                ${'query_' . $k} = 'SELECT ';
            } else {
                ${'query_' . $k} = 'SELECT CreatedDate, ';
            }
            for ($i = 0; $i < count($v['field']); $i++) {
                // $result["sfobj"][$v["title"]]["datatype"][$i] = "PKLA";
                // $sfobj["datatype"][$i] = $salesforceFunctions->describe($v);

                ${'query_' . $k} .= $v['field'][$i];
                if ($i < count($v['field']) - 1) {
                    ${'query_' . $k} .= ', ';
                }
            }
            ${'query_' . $k} .= ' FROM ' . $v['title'];

            // First get total count of records
            $countQuery = 'SELECT COUNT() FROM ' . $v['title'];
            $countResult = $salesforceFunctions->query($countQuery);
            $totalRecords = $countResult['totalSize'];

            // Initialize variables for pagination
            ${'data_' . $k} = ['records' => []];
            $batchSize = 2000;
            $totalBatches = ceil($totalRecords / $batchSize);

            // Add pagination info to debug
            $debugObj['pagination_' . $k] = [
                'total_records' => $totalRecords,
                'batch_size' => $batchSize,
                'total_batches' => $totalBatches
            ];

            $createdAt = '1970-01-01T00:00:00.000+0000';

            for ($i = 1; $i <= $totalBatches; $i++) {
                ${'workingquery_' . $i} = ${'query_' . $k};
                ${'workingquery_' . $i} .= ' WHERE CreatedDate > ' . $createdAt;
                ${'workingquery_' . $i} .= ' ORDER BY CreatedDate ASC';
                ${'workingquery_' . $i} .= ' LIMIT ' . $batchSize;

                $batchResult = $salesforceFunctions->query(${'workingquery_' . $i});
                if ($batchResult && isset($batchResult['records'])) {
                    // ${"data_".$k}['records'] = $batchResult['records'];
                    ${'data_' . $k}['records'] = array_merge(${'data_' . $k}['records'], $batchResult['records']);
                    $createdAt = end($batchResult['records'])['CreatedDate'];
                    $debugObj['pagination_' . $k]['iteration_' . $i] = [
                        'records_retrieved' => count($batchResult['records']),
                        'query' => ${'workingquery_' . $i}
                    ];
                } else {
                    $result['error'] = 'Error retrieving initial batch.';
                    $result['status'] = -1;
                    break;
                }
            }

            // Set total size and retrieved count in the final result
            ${'data_' . $k}['totalSize'] = $totalRecords;
            ${'data_' . $k}['retrievedSize'] = count(${'data_' . $k}['records']);

            $data_pass = $data_pass && (count(${'data_' . $k}['records']) > 0);

            if (count(${'data_' . $k}['records']) > 0) {
                $result['data'][$k] = ${'data_' . $k};
            }
        }

        if ($data_pass) {
            $result['status'] = 0;
            $result['error'] = 'Retrieve SF object success';
        } else {
            $result['error'] = 'One or more SF object cannot be retrieved';
        }

        // STEP 3: Insert into database

        if ($action == 'create') {
            $timenow = microtime(true);

            $reason = isset($_GET['reason']) ? $_GET['reason'] : 'manual';
            // accepted values
            // 1) manual (default)
            // 2) crontab
            // 3) cdc
            $extra['reason'] = $reason;
            $result['reason'] = $reason;

            $sql_Insert = 'INSERT INTO batch (batch, sfobj, data, debug, extra, status) VALUES (?, ?, ?, ?, ?, ?)';
            try {
                $stmt = $pdo->prepare($sql_Insert);
                $result_Insert = $stmt->execute([
                    $result['batch'],
                    json_encode($result['sfobj']),
                    json_encode($result['data']),
                    json_encode($debugObj),
                    json_encode($extra),
                    $result['status']
                ]);
                $result['INSERT_ID'] = $pdo->lastInsertId();
                $debugObj['sql_status'] = 'SQL executed successfully';
                
                
                $buildTableStatements = buildTable($result['batch']);
                $result['buildTable']['statements'] = $buildTableStatements;
                foreach ($buildTableStatements as $sql) {
                    $pdo->prepare($sql)->execute();
                }
                $result['buildTable']['status'] = 'Build Table executed successfully';

            } catch (Exception $e) {
                $debugObj['sql_error'] = $e->getMessage();
                $result['error'] = 'SQL execution error: ' . $e->getMessage();
                $result['status'] = -1;
                break;
            }

            $debugObj['sql_Insert'] = $sql_Insert;
            $debugObj['time_start'] = $timestart;
            $debugObj['time_now'] = $timenow;
            $debugObj['time_spent'] = $timenow - $timestart;
        }

        break;

    case 'buildtable':
        if (isset($_GET['batch'])) {
            $v = $_GET['batch'];
        } else if (isset($_GET['id'])) {
            $v = $_GET['id'];
        } else {
            $v = '';
        }

        $buildTableStatements = buildTable($v);
        $result['buildTableStatements'] = $buildTableStatements;
        // Execute SQL statements

        foreach ($buildTableStatements as $sql) {
            $pdo->prepare($sql)->execute();
        }

        $result['status'] = 0;
        $result['error'] = 'Build Table success';

        break;
}

// STEP FINAL: echo result
if ($debug) {
    $result['debug'] = $debugObj;
}

if ($pretty) {
    $json = '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
} else {
    $json = json_encode($result, JSON_PRETTY_PRINT);
}
echo $json;

function JSONTOSTRING($json_object)
{
    $a = json_encode($json_object, JSON_UNESCAPED_SLASHES);
    $a = stripslashes($a);
    $a = str_replace('\\', '', $a);
    return $a;
}

function mapSalesforceToMySQLDataType($salesforceType)
{
    $typeMapping = [
        'id' => 'VARCHAR(255)',
        'string' => 'VARCHAR(255)',
        'boolean' => 'BOOLEAN',
        'int' => 'INT',
        'double' => 'DOUBLE',
        'date' => 'DATE',
        'datetime' => 'DATETIME',
        'textarea' => 'TEXT',
        'picklist' => 'VARCHAR(255)',
        'phone' => 'VARCHAR(40)',
        'url' => 'VARCHAR(255)',
        'email' => 'VARCHAR(100)',
        'currency' => 'DECIMAL(18,2)',
        // Add more mappings as needed
    ];

    return isset($typeMapping[$salesforceType]) ? $typeMapping[$salesforceType] : 'VARCHAR(255)';
}

function getFieldTypes($metadata, $fields)
{
    $fieldTypes = [];
    foreach ($metadata['fields'] as $field) {
        if (in_array($field['name'], $fields)) {
            $fieldTypes[$field['name']] = mapSalesforceToMySQLDataType($field['type']);
        }
    }
    return $fieldTypes;
}

function buildTable($v)
{
    global $pdo, $debugObj;
    if ($v != '') {
        if (str_contains($v, '-')) {
            $sqlView = "SELECT * FROM batch WHERE batch='" . $v . "' AND 1 LIMIT 1";
        } else {
            $sqlView = "SELECT * FROM batch WHERE id='" . $v . "' AND 1 LIMIT 1";
        }
    } else {
        $sqlView = 'SELECT * FROM batch WHERE 1 ORDER BY id DESC LIMIT 1';
    }

    $debugObj['buildTable']['sqlView'] = $sqlView;
    try {
        $resultView = $pdo->query($sqlView)->fetchAll();
    } catch (Exception $e) {
        $debugObj['buildTable']['sqlView_error'] = $e->getMessage();
        return [];
    }

    $sfobj = json_decode($resultView[0]['sfobj'], true);
    $data = json_decode($resultView[0]['data'], true);

    $tablelist = array_keys($sfobj);

    $sqlStatements = [];
    $titles = array_map(function ($obj) {
        return $obj['title'];
    }, $sfobj);

    foreach ($titles as $title) {
        $sqlStatements[] = 'DROP TABLE `' . $_ENV['MYSQL_DATABASE'] . '`.`' . $title . '`;';
    }

    // Generate CREATE TABLE and INSERT INTO statements
    foreach ($tablelist as $tableObj) {
        $sfobj_target = $sfobj[$tableObj];
        $fields = $sfobj_target['field'];
        $datatypes = $sfobj_target['datatype'];
        $tableName = $sfobj_target['title'];

        // Create Table statement
        $sqlCreate = "CREATE TABLE IF NOT EXISTS `$tableName` (\n";
        $field_definitions = [];

        for ($i = 0; $i < count($fields); $i++) {
            $field_definitions[] = '`' . $fields[$i] . '` ' . $datatypes[$i];
        }

        $sqlCreate .= implode(",\n", $field_definitions);
        $sqlCreate .= "\n);";

        $sqlStatements[] = $sqlCreate;
        // $debugObj["buildTable"]["create_table"][$tableName] = $sqlCreate;

        // Insert Data statements
        $dataRecords = $data[$tableObj]['records'];
        foreach ($dataRecords as $record) {
            $fieldList = implode(', ', array_map(function ($field) {
                return "`$field`";
            }, $fields));
            $valueList = implode(', ', array_map(function ($field) use ($record, $pdo) {
                return $pdo->quote($record[$field]);
            }, $fields));
            $sqlInsert = "INSERT INTO `$tableName` ($fieldList) VALUES ($valueList);";
            $sqlStatements[] = $sqlInsert;
            // $debugObj["buildTable"]["insert_data"][$tableName][] = $sqlInsert;
        }
    }

    return $sqlStatements;
}