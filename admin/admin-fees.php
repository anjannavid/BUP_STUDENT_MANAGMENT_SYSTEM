<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/get_fee_payments.php';
require_once __DIR__ . '/includes/set_semester_fee.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    if ($action === 'paid') {
        db_exec("UPDATE fees SET status = 'Paid', paid_on = CURDATE() WHERE id = ? AND status = 'Due'", 'i', [(int)post('id')]);
        flash('Marked as paid.');
    } elseif ($action === 'remind') {
        db_exec("UPDATE fees SET reminded = 1 WHERE id = ? AND status = 'Due'", 'i', [(int)post('id')]);
        flash('Reminder sent. The resident sees it on their dashboard.');
    } elseif ($action === 'invoice') {
        $month = post('month');
        $type = in_array(post('fee_type'), ['Seat rent', 'Mess', 'Other'], true) ? post('fee_type') : 'Other';
        $amount = (int)post('amount');
        if ($month === '' || $amount <= 0) {
            flash('Enter a month and an amount above zero.', 'err');
        } else {
            $n = issue_invoices($month, $type, $amount);
            flash("Invoices created for $n residents.");
        }
    }
    redirect('admin-fees.php');
}

$fees = get_fee_payments();

layout_start('Fees', 'admin', 'admin-fees.php');
?>
<h2>Fees and invoices</h2>
<form class="panel form" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="invoice">
  <div class="full"><h3>Generate invoices for all residents</h3></div>
  <label for="i-m">Month<input id="i-m" name="month" placeholder="Nov 2026" required></label>
  <label for="i-t">Type
    <select id="i-t" name="fee_type"><option>Seat rent</option><option>Mess</option><option>Other</option></select></label>
  <label for="i-a">Amount (৳)<input id="i-a" name="amount" type="number" min="1" value="1500" required></label>
  <div><button class="btn" type="submit">Generate</button></div>
</form>

<div class="tablebox"><table>
  <thead><tr><th>Resident</th><th>Month</th><th>Type</th><th class="num">Amount</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($fees as $f): ?>
    <tr>
      <td><?= e($f['name']) ?></td>
      <td><?= e($f['month_label']) ?></td>
      <td><?= e($f['fee_type']) ?></td>
      <td class="num"><?= money($f['amount']) ?></td>
      <td><?= pill($f['status']) ?><?= $f['status'] === 'Due' && $f['reminded'] ? ' <span class="muted">reminded</span>' : '' ?></td>
      <td>
        <?php if ($f['status'] === 'Due'): ?>
        <div class="row-actions">
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="paid"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
            <button class="btn small" type="submit">Mark paid</button></form>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="remind"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
            <button class="btn small ghost" type="submit">Send reminder</button></form>
        </div>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$fees): ?><tr><td colspan="6" class="empty">No invoices yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php layout_end();
