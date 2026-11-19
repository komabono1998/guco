<?php
// Shows what importing a register CSV would do, without changing the database.
// Usage: php tools/dry_run_register.php storage/imports/file.csv
if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}
if ($argc < 2 || !is_file($argv[1])) {
    exit("Usage: php tools/dry_run_register.php path/to/register.csv\n");
}
require __DIR__ . '/../includes/bootstrap.php';
$existing = q('SELECT guco_no, full_name FROM legacy_members')->fetchAll();
$fh = fopen($argv[1], 'r');
$head = array_map('strtolower', array_map('trim', fgetcsv($fh)));
$head[0] = preg_replace('/^\xEF\xBB\xBF/', '', $head[0]);
$new = $same = $other = 0;
while (($r = fgetcsv($fh)) !== false) {
    $row = array_combine($head, array_pad($r, count($head), ''));
    $no = legacy_norm_no($row['guco_no']);
    $verdict = 'NEW';
    foreach ($existing as $e) {
        if (legacy_same_person($row['full_name'], $e['full_name'], $e['guco_no'] === $no)) {
            $verdict = $e['guco_no'] === $no ? 'already there (same number)' : "already there as {$e['guco_no']} {$e['full_name']}";
            if ($e['guco_no'] === $no) {
                break;
            }
        }
    }
    $verdict === 'NEW' ? $new++ : ($verdict === 'already there (same number)' ? $same++ : $other++);
    if ($verdict !== 'NEW') {
        echo str_pad($no, 11) . str_pad($row['full_name'], 26) . $verdict . "\n";
    }
}
echo "New: $new, already present: $same, present under another number: $other\n";
