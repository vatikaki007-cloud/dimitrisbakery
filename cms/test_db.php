<?php
/**
 * test_db.php — Temporary database connection debugger
 * 
 * IMPORTANT: DELETE THIS FILE from your server after debugging!
 * It exposes server information and must not stay live.
 */

// ── Credentials to test (copied from config.php) ──────────────────────────
$host    = 'localhost';
$db_name = 'dimitdkc_bakery';
$db_user = 'dimitdkc_vatikaki';
$db_pass = '3rLeA*n,H,tW^,&g';
$charset = 'utf8mb4';
// ──────────────────────────────────────────────────────────────────────────

echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
<title>DB Debug</title>
<style>
  body { font-family: monospace; background: #111; color: #eee; padding: 30px; }
  .ok   { color: #4caf80; font-weight: bold; }
  .fail { color: #e05555; font-weight: bold; }
  .box  { background: #1a1a22; border: 1px solid #333; padding: 16px; border-radius: 8px; margin: 12px 0; white-space: pre-wrap; word-break: break-all; }
  h2    { color: #c8964c; }
</style></head><body>';

echo '<h2>🔧 Dimitri\'s Bakery — DB Connection Test</h2>';

// ── Step 1: PHP version ────────────────────────────────────────────────────
echo '<h3>PHP Version</h3>';
echo '<div class="box">' . phpversion() . '</div>';

// ── Step 2: PDO available? ─────────────────────────────────────────────────
echo '<h3>PDO Extension</h3>';
if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
    echo '<div class="box"><span class="ok">✓ PDO and pdo_mysql are loaded</span></div>';
} else {
    echo '<div class="box"><span class="fail">✗ PDO or pdo_mysql is NOT loaded.</span><br>Loaded extensions: ' . implode(', ', get_loaded_extensions()) . '</div>';
}

// ── Step 3: Try raw mysqli first (simpler) ─────────────────────────────────
echo '<h3>MySQLi Connection Test (raw)</h3>';
if (function_exists('mysqli_connect')) {
    $mysqli = @mysqli_connect($host, $db_user, $db_pass, $db_name);
    if ($mysqli) {
        echo '<div class="box"><span class="ok">✓ mysqli connected successfully!</span></div>';
        mysqli_close($mysqli);
    } else {
        echo '<div class="box"><span class="fail">✗ mysqli failed:</span> ' . htmlspecialchars(mysqli_connect_error()) . ' (Error #' . mysqli_connect_errno() . ')</div>';
    }
} else {
    echo '<div class="box"><span class="fail">✗ mysqli extension not available</span></div>';
}

// ── Step 4: PDO connection ─────────────────────────────────────────────────
echo '<h3>PDO Connection Test</h3>';
try {
    $dsn = "mysql:host={$host};dbname={$db_name};charset={$charset}";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo '<div class="box"><span class="ok">✓ PDO connected successfully!</span></div>';

    // ── Step 5: Check if tables exist ─────────────────────────────────────
    echo '<h3>Tables in database</h3>';
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if ($tables) {
        echo '<div class="box"><span class="ok">✓ Tables found:</span>' . "\n" . implode("\n", $tables) . '</div>';
    } else {
        echo '<div class="box"><span class="fail">⚠ No tables found.</span>
The database is empty — you still need to run <strong>db_setup.sql</strong> in phpMyAdmin.</div>';
    }

} catch (PDOException $e) {
    echo '<div class="box"><span class="fail">✗ PDO failed:</span>' . "\n" . htmlspecialchars($e->getMessage()) . '</div>';

    // Extra hints based on error code
    $code = $e->getCode();
    echo '<h3>Hint</h3><div class="box">';
    if ($code == 1045) {
        echo '⚠ Error 1045 = Access denied. The username or password is wrong, OR the user has not been added to the database in cPanel (MySQL Databases → Add User to Database → All Privileges).';
    } elseif ($code == 1049) {
        echo '⚠ Error 1049 = Unknown database. The database name "' . $db_name . '" does not exist on this server.';
    } elseif ($code == 2002) {
        echo '⚠ Error 2002 = Cannot connect to MySQL server. Try changing DB_HOST from "localhost" to "127.0.0.1".';
    } else {
        echo 'Error code: ' . $code;
    }
    echo '</div>';
}

// ── Step 6: Credentials summary (partial, for verification) ───────────────
echo '<h3>Credentials Used</h3>';
echo '<div class="box">';
echo 'Host:     ' . htmlspecialchars($host) . "\n";
echo 'Database: ' . htmlspecialchars($db_name) . "\n";
echo 'User:     ' . htmlspecialchars($db_user) . "\n";
echo 'Password: ' . str_repeat('*', strlen($db_pass) - 2) . substr($db_pass, -2) . ' (' . strlen($db_pass) . ' chars)' . "\n";
echo '</div>';

echo '<p style="color:#e05555;margin-top:30px;"><strong>⚠ DELETE this file from your server once debugging is complete!</strong></p>';
echo '</body></html>';
