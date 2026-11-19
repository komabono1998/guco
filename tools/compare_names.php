<?php
// Lists people whose spelling in a CSV differs from the register under the same GUCO number. Changes nothing.
// Usage: php tools/compare_names.php storage/imports/file.csv
if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}
if ($argc < 2 || !is_file($argv[1])) {
    exit("Usage: php tools/compare_names.php path/to/file.csv\n");
}
require __DIR__ . '/../includes/bootstrap.php';
$have = q('SELECT guco_no, full_name FROM legacy_members')->fetchAll();
$fh = fopen($argv[1], 'r');
$head = array_map('strtolower', array_map('trim', fgetcsv($fh)));
$head[0] = preg_replace('/^\xEF\xBB\xBF/', '', $head[0]);
$n = 0;
while (($r = fgetcsv($fh)) !== false) {
    $row = array_combine($head, array_pad($r, count($head), ''));
    $no = legacy_norm_no($row['guco_no']);
    foreach ($have as $e) {
        if ($e['guco_no'] === $no && legacy_same_person($row['full_name'], $e['full_name'], true)) {
            $a = legacy_name_tokens($row['full_name']);
            $b = legacy_name_tokens($e['full_name']);
            sort($a);
            sort($b);
            if ($a !== $b) {
                echo "$no  file: {$row['full_name']}  |  database: {$e['full_name']}\n";
                $n++;
            }
        }
    }
}
echo "$n spelling difference(s)\n";
