<?php
require_once __DIR__ . '/config.php';
$pdo = get_db();

$customers_file = __DIR__ . '/csv_imports/Customers';
echo "Checking file: $customers_file<br>";

if (!file_exists($customers_file)) {
    die("File not found!");
}

$handle = fopen($customers_file, "r");
$headers = fgetcsv($handle, 10000, ",");
echo "Header count: " . count($headers) . "<br>";
echo "Headers: <pre>" . print_r($headers, true) . "</pre>";

$count = 0;
$skipped = 0;
$errors = [];

while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
    if (count($data) == count($headers)) {
        try {
            $row = array_combine($headers, $data);
            $ref = trim($row['Account Number'] ?? '');
            if (empty($ref)) {
                $skipped++;
                continue;
            }
            $count++;
        } catch (Exception $e) {
            $errors[] = "Row count matches but array_combine failed: " . $e->getMessage();
        }
    } else {
        $skipped++;
        if ($skipped < 5) {
            $errors[] = "Row " . ($count + $skipped + 1) . " count mismatch: " . count($data) . " vs " . count($headers);
        }
    }
}
fclose($handle);

echo "Imported: $count<br>";
echo "Skipped: $skipped<br>";
echo "Errors: <pre>" . print_r($errors, true) . "</pre>";
