<?php
require_once 'includes/db.php';
$db = getDB();
$checks = [];

function hasColumn(PDO $db, string $table, string $col): bool {
  $s = $db->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
  $s->execute([$col]);
  return (bool)$s->fetch();
}
function hasTable(PDO $db, string $table): bool {
  $s = $db->prepare("SHOW TABLES LIKE ?");
  $s->execute([$table]);
  return (bool)$s->fetch();
}

$checks[] = ['users.notif_in_app', hasColumn($db, 'users', 'notif_in_app')];
$checks[] = ['users.notif_email', hasColumn($db, 'users', 'notif_email')];
$checks[] = ['users.notif_telegram', hasColumn($db, 'users', 'notif_telegram')];
$checks[] = ['offers.counter_price', hasColumn($db, 'offers', 'counter_price')];
$checks[] = ['offers.counter_note', hasColumn($db, 'offers', 'counter_note')];
$checks[] = ['offers.counter_status', hasColumn($db, 'offers', 'counter_status')];
$checks[] = ['table.offer_negotiations', hasTable($db, 'offer_negotiations')];
$checks[] = ['table.listing_completion_proofs', hasTable($db, 'listing_completion_proofs')];
$checks[] = ['proofs.completion_code', hasColumn($db, 'listing_completion_proofs', 'completion_code')];
$checks[] = ['table.listing_completion_photos', hasTable($db, 'listing_completion_photos')];
$checks[] = ['table.worker_service_packages', hasTable($db, 'worker_service_packages')];
$checks[] = ['table.tb_chat_typing', hasTable($db, 'tb_chat_typing')];

foreach ($checks as [$name, $ok]) {
  echo ($ok ? 'PASS' : 'FAIL') . " - {$name}\n";
}
