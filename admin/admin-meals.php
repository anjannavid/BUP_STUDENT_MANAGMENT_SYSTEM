<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/meals.php';

$t = today();
$months = [];
for ($i = 0; $i < 3; $i++) {
    $months[] = date('Y-m', strtotime(date('Y-m-01', strtotime($t)) . " -$i month"));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    if ($action === 'prices') {
        $vals = [];
        foreach (MEAL_KEYS as $m => $_) {
            $vals[$m] = (int)post($m);
            if ($vals[$m] < 0 || post($m) === '') {
                $vals = null;
                break;
            }
        }
        if ($vals === null) {
            flash('Enter a price of zero or more for every meal.', 'err');
        } else {
            foreach ($vals as $m => $p) {
                db_exec('INSERT INTO meal_prices (meal, price) VALUES (?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price)', 'si', [$m, $p]);
            }
            flash('Meal prices updated.');
        }
    } elseif ($action === 'bills') {
        $ym = post('ym');
        if (!in_array($ym, $months, true)) {
            flash('Choose a month from the list.', 'err');
        } else {
            $label = month_label($ym);
            $n = 0;
            foreach (month_summary($ym) as $sid => $s) {
                if ($s['cost'] <= 0) {
                    continue;
                }
                $ex = db_one("SELECT id, status FROM fees WHERE student_id = ? AND fee_type = 'Meal bill' AND month_label = ?", 'ss', [$sid, $label]);
                if ($ex) {
                    if ($ex['status'] === 'Due') {
                        db_exec('UPDATE fees SET amount = ? WHERE id = ?', 'ii', [$s['cost'], $ex['id']]);
                        $n++;
                    }
                } else {
                    db_exec("INSERT INTO fees (student_id, fee_type, month_label, amount) VALUES (?, 'Meal bill', ?, ?)", 'ssi', [$sid, $label, $s['cost']]);
                    $n++;
                }
            }
            flash("Meal bills issued for $n residents.");
        }
    }
    redirect('admin-meals.php');
}

$prices = meal_prices();
$days = [];
for ($i = 1; $i <= 7; $i++) {
    $days[] = add_days($t, $i);
}
$ym = substr($t, 0, 7);
$summary = month_summary($ym);
$names = array_column(db_all('SELECT student_id, name FROM students ORDER BY name'), 'name', 'student_id');

layout_start('Meal count', 'admin', 'admin-meals.php');
?>
<h2>Meal count and billing</h2>
<p class="muted">Counts follow what residents booked. Meals are on by default, so a resident who never opens the portal is counted in.</p>

<div class="stats">
  <?php foreach (MEAL_KEYS as $m => $label): ?>
    <div class="stat"><span class="label">Tomorrow, <?= e(strtolower($label)) ?></span><b><?= meal_count($days[0], $m) ?><span class="muted" style="font-size:1rem"> plates</span></b></div>
  <?php endforeach; ?>
</div>

<div class="tablebox"><table>
  <thead><tr><th>Day</th><th class="num">Breakfast</th><th class="num">Lunch</th><th class="num">Dinner</th><th class="num">Total plates</th></tr></thead>
  <tbody>
  <?php foreach ($days as $d):
      $c = [];
      foreach (MEAL_KEYS as $m => $_) { $c[$m] = meal_count($d, $m); } ?>
    <tr>
      <td><b><?= e(date('l', strtotime($d))) ?></b> <span class="muted mono"><?= fdate($d) ?></span></td>
      <?php foreach ($c as $n): ?><td class="num"><?= $n ?></td><?php endforeach; ?>
      <td class="num"><?= array_sum($c) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<form class="panel form" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="prices">
  <div class="full"><h3>Price per meal (৳)</h3></div>
  <?php foreach (MEAL_KEYS as $m => $label): ?>
    <label for="pr-<?= $m ?>"><?= e($label) ?><input id="pr-<?= $m ?>" name="<?= $m ?>" type="number" min="0" value="<?= (int)$prices[$m] ?>" required></label>
  <?php endforeach; ?>
  <div><button class="btn" type="submit">Save prices</button></div>
</form>

<form class="panel form" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="bills">
  <div class="full"><h3>Issue meal bills</h3>
    <p class="muted" style="margin-top:4px">Adds a Meal bill invoice for each resident, based on the meals they took. Run it again to refresh bills that are still unpaid.</p></div>
  <label for="mb-m">Month
    <select id="mb-m" name="ym"><?php foreach ($months as $m): ?><option value="<?= e($m) ?>"><?= e(month_label($m)) ?></option><?php endforeach; ?></select></label>
  <div><button class="btn" type="submit">Issue meal bills</button></div>
</form>

<h3>Meals taken so far, <?= e(month_label($ym)) ?></h3>
<div class="tablebox"><table>
  <thead><tr><th>Resident</th><th class="num">Meals taken</th><th class="num">Cost</th></tr></thead>
  <tbody>
  <?php foreach ($names as $sid => $name): ?>
    <tr><td><?= e($name) ?></td><td class="num"><?= (int)$summary[$sid]['meals'] ?></td><td class="num"><?= money($summary[$sid]['cost']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php layout_end();
