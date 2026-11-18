<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_perm('backup');

/** Streams a portable SQL dump (structure and data) of every table, using PDO only. */
function dump_database(): void
{
    $pdo = db();
    $name = config('db')['name'];
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="guco-backup-' . date('Ymd-His') . '.sql"');
    echo "-- GUCO database backup, " . date('c') . "\n-- Restore: mysql -u USER -p DATABASE < this-file.sql\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
    foreach ($pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM) as [$table]) {
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
        echo "DROP TABLE IF EXISTS `$table`;\n$create;\n";
        $st = $pdo->query("SELECT * FROM `$table`");
        $batch = [];
        while ($row = $st->fetch(PDO::FETCH_NUM)) {
            $batch[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)) . ')';
            if (count($batch) >= 200) {
                echo "INSERT INTO `$table` VALUES\n" . implode(",\n", $batch) . ";\n";
                $batch = [];
            }
        }
        if ($batch) {
            echo "INSERT INTO `$table` VALUES\n" . implode(",\n", $batch) . ";\n";
        }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    audit('database_backup', 'downloaded SQL dump');
    dump_database();
    exit;
}
admin_header('Backup', 'Download a copy of all site data');
?>
<div class="card accent" style="max-width:720px">
  <h2 style="margin-top:0">Database backup</h2>
  <p>Downloads one <code>.sql</code> file with every table: members, payments, donations, events, content and the audit log. Store it somewhere safe and private; it contains personal data and password hashes.</p>
  <form method="post"><?= csrf_field() ?><button>Download backup now</button></form>
  <h3>Recommended routine</h3>
  <ul>
    <li>Download a backup weekly, and before any update or migration.</li>
    <li>Also back up the <code>uploads/</code> and <code>storage/</code> folders (pictures, documents, proofs of payment).</li>
    <li>Restore by importing the file into an empty database: <code>mysql -u USER -p DATABASE &lt; backup.sql</code>. Test a restore on a copy at least once.</li>
  </ul>
</div>
<?php admin_footer(); ?>
