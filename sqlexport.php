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
        die("Invalid parameters. e1 and e2 must be positive integers with e1 <= e2.");
    }

    // Query to get the data from the batch table
    $sql = "SELECT * FROM batch WHERE id BETWEEN :e1 AND :e2";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':e1', $e1, PDO::PARAM_INT);
    $stmt->bindParam(':e2', $e2, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate the SQL export file
    $filename = "batchrow_{$e1}_{$e2}.sql";
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
        // Add more validation checks as needed
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
        <input type="number" id="batchsize" name="batchsize" value="100" required><br><br>
        <button type="button" onclick="handleAction('export')">Export</button>
        <button type="button" onclick="handleAction('validate')">Validate</button>
        <button type="button" onclick="handleAction('automate')">Automate</button>
    </form>

    <h2>Results</h2>
    <textarea id="results" readonly></textarea>
</body>
</html>