<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/meals.php';

$sid = $me['student_id'];
$t = today();
$due = (int)db_val("SELECT COALESCE(SUM(amount),0) FROM fees WHERE student_id = ? AND status = 'Due'", 's', [$sid]);
$reminded = (int)db_val("SELECT COUNT(*) FROM fees WHERE student_id = ? AND status = 'Due' AND reminded = 1", 's', [$sid]);
$openReq = (int)db_val("SELECT COUNT(*) FROM maintenance_requests WHERE student_id = ? AND status <> 'Resolved'", 's', [$sid]);
$skips = skips_for($sid, $t, $t);
$mealsToday = 0;
foreach (MEAL_KEYS as $m => $_) {
    if (!isset($skips[$t . '|' . $m])) {
        $mealsToday++;
    }
}
$notice = db_one('SELECT * FROM notices ORDER BY created_at DESC, id DESC LIMIT 1');
$menu = menu_for($t);

layout_start('Overview', 'student', 'student-dashboard.php');
?>
<div class="head"><h2>Hello, <?= e(explode(' ', $me['name'])[0]) ?></h2><span class="muted mono"><?= e($sid) ?></span></div>

<?php if ($reminded): ?>
  <div class="notice-banner"><b>Payment reminder.</b> The provost office has sent a reminder for your unpaid dues. See the Fees page.</div>
<?php endif; ?>

<div class="stats">
  <div class="stat"><span class="label">Room</span><b><?= $me['room_no'] ? e($me['room_no']) : 'Not allocated' ?></b></div>
  <div class="stat"><span class="label">Dues</span><b><?= money($due) ?></b></div>
  <div class="stat"><span class="label">Open requests</span><b><?= $openReq ?></b></div>
  <div class="stat"><span class="label">Meals today</span><b><?= $mealsToday ?> / 3</b></div>
</div>

<div class="cols" style="margin-top:0">
  <section class="panel">
    <h3>Latest notice</h3>
    <?= $notice ? notice_card($notice) : '<p class="muted">None.</p>' ?>
  </section>
  <section class="panel">
    <h3>Today<?= $menu ? ': ' . e($menu['day_name']) : '' ?></h3>
    <?php if ($menu): foreach (MEAL_KEYS as $m => $label): ?>
      <div class="meal"><span class="label"><?= e($label) ?></span><span><?= e($menu[$m]) ?></span></div>
    <?php endforeach; endif; ?>
  </section>
</div>
<?php layout_end();
