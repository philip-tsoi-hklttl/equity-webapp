<?php

require_once('config.php'); // Assuming config.php contains the database connection details

// Function to connect to the MySQL database
function connectToDatabase() {
    global $pdo;
    try {
        $pdo = new PDO('mysql:host=' . MYSQL_HOST . ';dbname=' . MYSQL_DATABASE . ';charset=' . MYSQL_CHARSET . ';port=' . MYSQL_PORT, MYSQL_USERNAME, MYSQL_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Could not connect to the database " . MYSQL_DATABASE . " :" . $e->getMessage());
    }
}

// Connect to the database
connectToDatabase();

// Get the action parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'view';

// Handle the export action
if ($action === 'export') {
    // Get query string parameters
    $e1 = isset($_REQUEST['e1']) ? intval($_REQUEST['e1']) : 0;
    $e2 = isset($_REQUEST['e2']) ? intval($_REQUEST['e2']) : 0;

    // Validate parameters
    if ($e1 <= 0 || $e2 <= 0 || $e1 > $e2) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters. e1 and e2 must be positive integers with e1 <= e2.']);
        exit;
    }

    // Query to get the data from the batch table
    $sql = "SELECT * FROM batch WHERE id BETWEEN :e1 AND :e2";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':e1', $e1, PDO::PARAM_INT);
    $stmt->bindParam(':e2', $e2, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate the SQL export file content
    $fileContent = "";
    foreach ($results as $row) {
        $columns = implode(", ", array_keys($row));
        $values = implode(", ", array_map(function($value) use ($pdo) {
            return $pdo->quote($value);
        }, $row));
        $fileContent .= "INSERT INTO batch ($columns) VALUES ($values);\n";
    }

    // Return the file content
    header('Content-Type: text/plain');
    echo $fileContent;
    exit;
}

// Handle the backup action
if ($action === 'backup') {
    $e1 = isset($_REQUEST['e1']) ? intval($_REQUEST['e1']) : 0;
    $e2 = isset($_REQUEST['e2']) ? intval($_REQUEST['e2']) : 0;

    if ($e1 <= 0 || $e2 <= 0 || $e1 > $e2) {
        die("Invalid parameters. e1 and e2 must be positive integers with e1 <= e2.");
    }

    $sql = "SELECT * FROM batch WHERE id BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$e1, $e2]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "batchrow_{$e1}_{$e2}.sql";
    $fileContent = "";

    foreach ($results as $row) {
        $columns = implode(", ", array_keys($row));
        $values = implode(", ", array_map(function($value) use ($pdo) {
            return $pdo->quote($value);
        }, $row));
        $fileContent .= "INSERT INTO batch ($columns) VALUES ($values);\n";
    }

    $backupDir = '/export/';
    $filePath = $backupDir . $filename;
    
    if (file_put_contents($filePath, $fileContent) === false) {
        die("Failed to save backup file to $filePath");
    }

    echo "Backup saved successfully to $filePath";
    exit;
}
// New automatebackup action
if ($action === 'automatebackup') {
    $preserve = isset($_REQUEST['preserve']) ? intval($_REQUEST['preserve']) : 500;
    $batchsize = isset($_REQUEST['batchsize']) ? intval($_REQUEST['batchsize']) : 10;
    
    try {
        // Get total row count
        $stmt = $pdo->query('SELECT COUNT(*) as total FROM batch');
        $totalRows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($totalRows <= $preserve) {
            echo json_encode(['status' => 'success', 'message' => "No cleanup needed. Total rows: $totalRows"]);
            exit;
        }

        // Get the ID of the preserve-th newest row
        $stmt = $pdo->prepare(
            'SELECT id FROM batch ORDER BY id DESC LIMIT 1 OFFSET ?'
        );
        $stmt->execute([$preserve - 1]);
        $cutoffRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cutoffRow) {
            echo json_encode(['status' => 'success', 'message' => 'No rows to clean up']);
            exit;
        }

        $cutoffId = $cutoffRow['id'];
        $results = [];

        // Process in chunks
        $stmt = $pdo->prepare(
            'SELECT id FROM batch WHERE id < ? ORDER BY id ASC'
        );
        $stmt->execute([$cutoffId]);
        $rowsToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);

        for ($i = 0; $i < count($rowsToDelete); $i += $batchsize) {
            $batch = array_slice($rowsToDelete, $i, $batchsize);
            $startId = $batch[0]['id'];
            $endId = $batch[count($batch) - 1]['id'];

            // Generate file content directly
            $sql = "SELECT * FROM batch WHERE id BETWEEN ? AND ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$startId, $endId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $fileContent = "";
            foreach ($results as $row) {
                $columns = implode(", ", array_keys($row));
                $values = implode(", ", array_map(function($value) use ($pdo) {
                    return $pdo->quote($value);
                }, $row));
                $fileContent .= "INSERT INTO batch ($columns) VALUES ($values);\n";
            }

            // Backup the batch
            $filename = "batchrow_{$startId}_{$endId}.sql";
            $backupDir = '/export/';
            $filePath = $backupDir . $filename;
            
            if (file_put_contents($filePath, $fileContent) === false) {
                throw new Exception("Failed to save backup file to $filePath");
            }

            // Delete the batch
            $deleteStmt = $pdo->prepare(
                'DELETE FROM batch WHERE id BETWEEN ? AND ?'
            );
            $deleteStmt->execute([$startId, $endId]);

            $results[] = "Backed up and deleted rows $startId to $endId";
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Cleanup completed',
            'results' => $results,
            'total_deleted' => count($rowsToDelete)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle the validate action
if ($action === 'validate') {
    // Get query string parameters
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

    // Validate parameters
    if ($id <= 0) {
        die("Invalid parameter. id must be a positive integer.");
    }

    // Query to get the data from the batch table
    $sql = "SELECT * FROM batch WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Validate the row
    $errors = [];
    if (!$row) {
        $errors[] = "Row with id $id not found.";
    } else {
        // Check for corrupted rows
        if (empty($row['batch']) || empty($row['sfobj']) || empty($row['data']) || empty($row['debug']) || empty($row['create_time'])) {
            $errors[] = "Row with id $id is corrupted. Missing required fields.";
        }
    }

    // Output the validation result
    if (empty($errors)) {
        echo "Row with id $id is valid.";
    } else {
        echo "Row with id $id is invalid. Errors:\n";
        foreach ($errors as $error) {
            echo "- $error\n";
        }
    }
    exit;
}

// Handle the automate action
if ($action === 'automate') {
    // Get query string parameters
    $e1 = isset($_REQUEST['e1']) ? intval($_REQUEST['e1']) : 0;
    $e2 = isset($_REQUEST['e2']) ? intval($_REQUEST['e2']) : 0;
    $batchsize = isset($_REQUEST['batchsize']) ? intval($_REQUEST['batchsize']) : 100;

    // Validate parameters
    if ($e1 <= 0 || $e2 <= 0 || $e1 > $e2 || $batchsize <= 0) {
        die("Invalid parameters. e1 and e2 must be positive integers with e1 <= e2, and batchsize must be a positive integer.");
    }

    $resultsTextarea = '';

    for ($start = $e1; $start <= $e2; $start += $batchsize) {
        $end = min($start + $batchsize - 1, $e2);

        // Query to get the data from the batch table
        $sql = "SELECT * FROM batch WHERE id BETWEEN :start AND :end";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':start', $start, PDO::PARAM_INT);
        $stmt->bindParam(':end', $end, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate the SQL export file
        $filename = "batchrow_{$start}_{$end}.sql";
        $fileContent = "";

        foreach ($results as $row) {
            $columns = implode(", ", array_keys($row));
            $values = implode(", ", array_map(function($value) use ($pdo) {
                return $pdo->quote($value);
            }, $row));
            $fileContent .= "INSERT INTO batch ($columns) VALUES ($values);\n";
        }

        // Set headers for file download
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($fileContent));

        // Output the file content
        echo $fileContent;

        // Append the result to the textarea
        $resultsTextarea .= "Exported batchrow_{$start}_{$end}.sql successfully.\n";
    }

    // Output the results
    echo "<textarea rows='10' cols='50' readonly>";
    echo htmlspecialchars($resultsTextarea);
    echo "</textarea>";
    exit;
}

// Display the UI form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Export</title>
    <style>
        textarea {
            width: 100%;
            height: 50vh;
        }
    </style>
    <script>
        async function handleAction(action) {
            var e1 = document.getElementById('e1').value;
            var e2 = document.getElementById('e2').value;
            var batchsize = document.getElementById('batchsize').value;
            var resultsTextarea = document.getElementById('results');

            if (action === 'export') {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'sqlexport.php?action=export', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.responseType = 'blob';

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        var blob = new Blob([xhr.response], { type: 'application/sql' });
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = 'batchrow_' + e1 + '_' + e2 + '.sql';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        resultsTextarea.value = 'Error exporting data';
                    }
                };

                xhr.send('e1=' + e1 + '&e2=' + e2);
            } else if (action === 'validate') {
                for (let id = e1; id <= e2; id++) {
                    await new Promise((resolve) => {
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'sqlexport.php?action=validate', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                resultsTextarea.value = xhr.responseText + '\n' + resultsTextarea.value;
                            } else {
                                resultsTextarea.value = `Error validating row with id ${id}\n` + resultsTextarea.value;
                            }
                            resolve();
                        };

                        xhr.send('id=' + id);
                    });
                }
            } else if (action === 'automate') {
                for (let start = parseInt(e1); start <= parseInt(e2); start += parseInt(batchsize)) {
                    let end = Math.min(start + parseInt(batchsize) - 1, parseInt(e2));
                    await new Promise((resolve) => {
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'sqlexport.php?action=export', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.responseType = 'blob';

                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                var blob = new Blob([xhr.response], { type: 'application/sql' });
                                var link = document.createElement('a');
                                link.href = window.URL.createObjectURL(blob);
                                link.download = 'batchrow_' + start + '_' + end + '.sql';
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                                resultsTextarea.value = `Exported batchrow_${start}_${end}.sql successfully.\n` + resultsTextarea.value;
                            } else {
                                resultsTextarea.value = `Error exporting batchrow_${start}_${end}.sql\n` + resultsTextarea.value;
                            }
                            resolve();
                        };

                        xhr.send('e1=' + start + '&e2=' + end);
                    });
                }
            } else if (action === 'backup') {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'sqlexport.php?action=backup', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        resultsTextarea.value = xhr.responseText + '\n' + resultsTextarea.value;
                    } else {
                        resultsTextarea.value = 'Error creating backup';
                    }
                };

                xhr.send('e1=' + e1 + '&e2=' + e2);
            } else if (action === 'automatebackup') {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'sqlexport.php?action=automatebackup', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                var params = 'batchsize=' + batchsize;

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        resultsTextarea.value = xhr.responseText + '\n' + resultsTextarea.value;
                    } else {
                        resultsTextarea.value = 'Error during automated backup';
                    }
                };

                xhr.send(params);
            }
        }
    </script>
</head>
<body>
    <h1>SQL Export and Validate</h1>
    <form id="actionForm">
        <label for="e1">From Row (e1):</label>
        <input type="number" id="e1" name="e1" required><br><br>
        <label for="e2">To Row (e2):</label>
        <input type="number" id="e2" name="e2" required><br><br>
        <label for="batchsize">Batch Size:</label>
        <input type="number" id="batchsize" name="batchsize" value="10" required><br><br>
        <button type="button" onclick="handleAction('export')">Export</button>
        <button type="button" onclick="handleAction('validate')">Validate</button>
        <button type="button" onclick="handleAction('automate')">Automate</button>
        <button type="button" onclick="handleAction('backup')">Backup</button>
        <button type="button" onclick="handleAction('automatebackup')">Automate Backup</button>
    </form>

    <h2>Results</h2>
    <textarea id="results" readonly></textarea>

    <p id="description">
        This tool provides five main functions:<br><br>
        
        1. <strong>Export:</strong> Downloads selected rows (e1 to e2) from the batch table as SQL INSERT statements.<br><br>
        
        2. <strong>Validate:</strong> Checks data integrity of specified rows, verifying existence and required fields (batch, sfobj, data, debug, create_time).<br><br>
        
        3. <strong>Automate:</strong> Performs batch exports with automatic chunking. Splits large ranges into smaller batches (default 100 rows) for efficient processing.<br><br>
        
        4. <strong>Backup:</strong> Similar to export but saves SQL files to the '/export/' directory on the server instead of downloading.<br><br>
        
        5. <strong>Automate Backup:</strong> Performs automated cleanup and backup of older records. Keeps a specified number of newest records (default 500) and processes older records in chunks (default 10), backing them up before deletion.
    </p>
</body>
</html>
