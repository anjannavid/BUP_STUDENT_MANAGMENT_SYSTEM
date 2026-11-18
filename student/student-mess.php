<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $rating = (int)post('rating');
    if ($rating < 1 || $rating > 5) {
        flash('Pick a rating from 1 to 5.', 'err');
    } else {
        db_exec('INSERT INTO mess_feedback (student_id, rating, comment) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)',
            'sis', [$me['student_id'], $rating, mb_substr(post('comment'), 0, 500)]);
        flash('Thanks for the feedback.');
    }
    redirect('student-mess.php');
}

$menu = db_all('SELECT * FROM mess_menu ORDER BY day_index');
$todayIdx = ((int)date('w') + 1) % 7;
$fb = db_one('SELECT * FROM mess_feedback WHERE student_id = ?', 's', [$me['student_id']]);

layout_start('Menu', 'student', 'student-mess.php');
?>
<h2>Weekly menu and feedback</h2>
<div class="tablebox"><table>
  <thead><tr><th>Day</th><th>Breakfast</th><th>Lunch</th><th>Dinner</th></tr></thead>
  <tbody>
  <?php foreach ($menu as $m): ?>
    <tr>
      <td><b><?= e($m['day_name']) ?></b><?= (int)$m['day_index'] === $todayIdx ? ' <span class="pill info">today</span>' : '' ?></td>
      <td><?= e($m['breakfast']) ?></td><td><?= e($m['lunch']) ?></td><td><?= e($m['dinner']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<form class="panel form" method="post">
  <?= csrf_field() ?>
  <div class="full"><h3>Rate this week's mess</h3></div>
  <div class="stars full" role="radiogroup" aria-label="Rating">
    <?php for ($n = 1; $n <= 5; $n++): ?>
      <label for="r<?= $n ?>"><input type="radio" id="r<?= $n ?>" name="rating" value="<?= $n ?>"<?= (($fb['rating'] ?? 4) == $n) ? ' checked' : '' ?>><?= $n ?></label>
    <?php endfor; ?>
  </div>
  <label class="full" for="fb-c">Comment<textarea id="fb-c" name="comment" maxlength="500" placeholder="What was good, what should change"><?= e($fb['comment'] ?? '') ?></textarea></label>
  <div><button class="btn" type="submit">Send feedback</button></div>
</form>
<?php layout_end();
