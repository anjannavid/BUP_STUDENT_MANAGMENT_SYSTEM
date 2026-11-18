<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/meals.php';

function bar_row(string $label, int $val, int $max, string $right, string $cls = ''): string
{
    $w = $max > 0 ? round($val / $max * 100) : 0;
    return '<div class="bar-row"><span>' . e($label) . '</span><div class="track"><i class="' . $cls . '" style="width:' . $w
        . '%"></i></div><span class="mono" style="text-align:right">' . e($right) . '</span></div>';
}

$cap = (int)db_val('SELECT COALESCE(SUM(capacity),0) FROM rooms');
$res = (int)db_val('SELECT COUNT(*) FROM students WHERE room_no IS NOT NULL');
$due = (int)db_val("SELECT COALESCE(SUM(amount),0) FROM fees WHERE status = 'Due'");
$openM = (int)db_val("SELECT COUNT(*) FROM maintenance_requests WHERE status <> 'Resolved'");
$pendG = (int)db_val("SELECT COUNT(*) FROM guests WHERE status = 'Pending'");
$rating = (float)db_val('SELECT COALESCE(AVG(rating),0) FROM mess_feedback');
$nFb = (int)db_val('SELECT COUNT(*) FROM mess_feedback');

$months = db_all("SELECT month_label, SUM(IF(status='Paid',amount,0)) AS paid, SUM(IF(status='Due',amount,0)) AS due
                  FROM fees GROUP BY month_label ORDER BY MIN(id)");
$maxFee = 0;
foreach ($months as $m) {
    $maxFee = max($maxFee, (int)$m['paid'] + (int)$m['due']);
}

$blocks = db_all('SELECT r.block, r.side, SUM(r.capacity) AS cap,
                  (SELECT COUNT(*) FROM students s JOIN rooms r2 ON r2.room_no = s.room_no WHERE r2.block = r.block) AS occ
                  FROM rooms r GROUP BY r.block, r.side ORDER BY r.block');
$mstat = db_all("SELECT status, COUNT(*) AS n FROM maintenance_requests GROUP BY status");
$mmap = array_column($mstat, 'n', 'status');
$mtotal = max(1, array_sum($mmap));

layout_start('Overview', 'admin', 'admin-dashboard.php');
?>
<div class="head"><h2>Overview</h2><span class="muted mono"><?= fdate(today()) ?></span></div>

<div class="stats">
  <div class="stat"><span class="label">Residents</span><b><?= $res ?><span class="muted" style="font-size:1rem"> / <?= $cap ?></span></b></div>
  <div class="stat"><span class="label">Dues outstanding</span><b><?= money($due) ?></b></div>
  <div class="stat"><span class="label">Open maintenance</span><b><?= $openM ?></b></div>
  <div class="stat"><span class="label">Guest requests waiting</span><b><?= $pendG ?></b></div>
  <div class="stat"><span class="label">Mess rating</span><b><?= number_format($rating, 1) ?><span class="muted" style="font-size:1rem"> / 5</span></b></div>
</div>

<div class="cols" style="margin-top:0">
  <section class="panel">
    <h3>Fees by month</h3>
    <div class="legend"><span>Collected</span><span class="d">Due</span></div>
    <div class="bars">
      <?php foreach ($months as $m):
          $p = (int)$m['paid']; $d = (int)$m['due'];
          $w1 = $maxFee ? $p / $maxFee * 100 : 0; $w2 = $maxFee ? $d / $maxFee * 100 : 0; ?>
        <div class="bar-row"><span><?= e($m['month_label']) ?></span>
          <div class="track"><i style="width:<?= $w1 ?>%"></i><i class="due" style="width:<?= $w2 ?>%"></i></div>
          <span class="mono" style="text-align:right"><?= money($p + $d) ?></span></div>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="panel">
    <h3>Occupancy by block</h3>
    <div class="bars">
      <?php foreach ($blocks as $b) { echo bar_row('Block ' . $b['block'] . ' (' . $b['side'] . ')', (int)$b['occ'], (int)$b['cap'], $b['occ'] . '/' . $b['cap']); } ?>
    </div>
  </section>
  <section class="panel">
    <h3>Maintenance status</h3>
    <div class="bars">
      <?php foreach (['Pending', 'In progress', 'Resolved'] as $st) {
          $n = (int)($mmap[$st] ?? 0);
          echo bar_row($st, $n, $mtotal, (string)$n, $st === 'Pending' ? 'due' : '');
      } ?>
    </div>
  </section>
  <section class="panel">
    <h3>Meals booked tomorrow</h3>
    <div class="bars">
      <?php $tm = add_days(today(), 1); $total = (int)db_val('SELECT COUNT(*) FROM students');
      foreach (MEAL_KEYS as $m => $label) { $c = meal_count($tm, $m); echo bar_row($label, $c, max(1, $total), $c . ' plates'); } ?>
    </div>
    <p class="muted" style="margin-top:10px;font-size:.85rem"><?= $nFb ?> residents rated the mess.</p>
  </section>
</div>
<?php layout_end();
