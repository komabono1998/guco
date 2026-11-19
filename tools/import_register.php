<?php
// Imports a register of existing GUCO members into the legacy_members table.
// Usage: php tools/import_register.php storage/imports/guco-register-2025.csv ["Source label"]
// The CSV needs the columns guco_no, title, full_name, sex, designation, note. Keep the file out of git.
if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}
if ($argc < 2 || !is_file($argv[1])) {
    exit("Usage: php tools/import_register.php path/to/register.csv [\"Source label\"]\n");
}
require __DIR__ . '/../includes/bootstrap.php';
[$added, $skipped, $disputed, $errors, $other] = legacy_import_csv($argv[1], $argv[2] ?? 'Voters Register 2025');
echo "Added $added, skipped $skipped already present, $other already present under a different number (noted), marked $disputed record(s) as needing the Secretariat.\n";
foreach ($errors as $e) {
    echo "  $e\n";
}
