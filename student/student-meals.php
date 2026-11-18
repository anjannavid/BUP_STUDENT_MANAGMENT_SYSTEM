<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/meals.php';

$sid = $me['student_id'];
$t = today();
$ym = substr($t, 0, 7);
$days = [];
for ($i = 0; $i < 8; $i++) {
    $days[] = add_days($t, $i);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    $changed = 0;
    foreach ($days as $d) {
        if (!meal_editable($d)) {
            continue; // closed days never change
        }
        foreach (MEAL_KEYS as $m => $_) {
            if ($action === 'all_on') {
                $on = true;
            } elseif ($action === 'all_off') {
                $on = false;
            } else {
                $on = isset($_POST['meal'][$d][$m]);
            }
            set_meal($sid, $d, $m, $on);
        }
        $changed++;
    }
    flash($action === 'all_on' ? 'All open days turned on.' : ($action === 'all_off' ? 'All open days turned off.' : 'Meals saved.'));
    redirect('student-meals.php');
}

$prices = meal_prices();
$skips = skips_for($sid, $ym . '-01', $days[7]);
$summary = month_summary($ym, $sid)[$sid];
$booked = 0;
for ($i = 1; $i < 8; $i++) {
    $booked += day_cost($skips, $days[$i]);
}
$onToday = 0;
foreach (MEAL_KEYS as $m => $_) {
    if (!isset($skips[$t . '|' . $m])) {
        $onToday++;
    }
}
$bill = db_one("SELECT * FROM fees WHERE student_id = ? AND fee_type = 'Meal bill' AND month_label = ?", 'ss', [$sid, month_label($ym)]);

$history = [];
for ($d = $t; $d >= $ym . '-01'; $d = add_days($d, -1)) {
    $history[] = $d;
}

layout_start('My meals', 'student', 'student-meals.php');
?>
<div class="head"><h2>My meals</h2><span class="muted"><?= e(month_label($ym)) ?></span></div>

<div class="stats">
  <div class="stat"><span class="label">Meals today</span><b><?= $onToday ?> / 3</b></div>
  <div class="stat"><span class="label">Bill so far this month</span><b><?= money($summary['cost']) ?></b></div>
  <div class="stat"><span class="label">Booked, next 7 days</span><b><?= money($booked) ?></b></div>
</div>

<?php if ($bill): ?>
  <div class="notice-banner">The provost office issued your <?= e($bill['month_label']) ?> meal bill: <b><?= money($bill['amount']) ?></b>, <?= $bill['status'] === 'Paid' ? 'paid' : 'unpaid' ?>. See the Fees page.</div>
<?php endif; ?>

<p class="muted">All meals are on by default. Untick a meal you will skip, then save. A day closes for changes at 9 PM the night before.
  Prices: breakfast <?= money($prices['breakfast']) ?>, lunch <?= money($prices['lunch']) ?>, dinner <?= money($prices['dinner']) ?>.</p>

<form method="post" id="meal-form">
  <?= csrf_field() ?>
  <div class="row-actions" style="margin-bottom:12px">
    <button class="btn small" type="submit" name="action" value="save">Save meals</button>
    <button class="btn small ghost" type="submit" name="action" value="all_off" data-confirm="Skip every meal on all open days?">Skip all open days</button>
    <button class="btn small ghost" type="submit" name="action" value="all_on">Turn all open days on</button>
  </div>

  <div class="tablebox"><table>
    <thead><tr><th>Day</th><th>Breakfast</th><th>Lunch</th><th>Dinner</th><th class="num">Cost</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($days as $k => $d):
        $ed = meal_editable($d);
        $mn = menu_for($d); ?>
      <tr<?= $ed ? '' : ' class="lockrow"' ?>>
        <td><b><?= e(date('l', strtotime($d))) ?></b><?= $k === 0 ? ' <span class="pill info">today</span>' : '' ?><br><span class="muted mono"><?= fdate($d) ?></span></td>
        <?php foreach (MEAL_KEYS as $m => $label):
            $on = !isset($skips[$d . '|' . $m]);
            $id = 'ml-' . $d . '-' . $m; ?>
          <td>
            <label class="mealcell<?= $on ? '' : ' off' ?>" for="<?= e($id) ?>">
              <input type="checkbox" id="<?= e($id) ?>" name="meal[<?= e($d) ?>][<?= e($m) ?>]" value="1"<?= $on ? ' checked' : '' ?><?= $ed ? '' : ' disabled' ?>>
              <span><?= money($prices[$m]) ?><small><?= e($mn[$m] ?? '') ?></small></span>
            </label>
          </td>
        <?php endforeach; ?>
        <td class="num"><?= money(day_cost($skips, $d)) ?></td>
        <td><?= $ed ? '<span class="pill good">Open</span>' : '<span class="pill warn">Locked</span>' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</form>

<h3>This month so far</h3>
<div class="tablebox"><table>
  <thead><tr><th>Date</th><th>Breakfast</th><th>Lunch</th><th>Dinner</th><th class="num">Cost</th></tr></thead>
  <tbody>
  <?php foreach ($history as $d): ?>
    <tr>
      <td><?= fdate($d) ?></td>
      <?php foreach (MEAL_KEYS as $m => $_): ?>
        <td><?= isset($skips[$d . '|' . $m]) ? '<span class="muted">Skipped</span>' : '<span class="pill good">Taken</span>' ?></td>
      <?php endforeach; ?>
      <td class="num"><?= money(day_cost($skips, $d)) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php layout_end();
