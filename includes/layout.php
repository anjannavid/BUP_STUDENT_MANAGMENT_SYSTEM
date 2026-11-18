<?php
// Page shell shared by every page: top bar, side rail for the portals, footer.
require_once __DIR__ . '/helpers.php';

function layout_start(string $title, string $area = 'public', string $active = '', bool $rail = true, bool $chrome = true): void
{
    $base = $area === 'public' ? '' : '../';
    $GLOBALS['_layout_rail'] = ($area !== 'public' && $rail);
    $GLOBALS['_layout_chrome'] = $chrome;

    $studentLink = $base . 'student/' . (!empty($_SESSION['student_id']) ? 'student-dashboard.php' : 'student-login.php');
    $adminLink = $base . 'admin/' . (!empty($_SESSION['admin_id']) ? 'admin-dashboard.php' : 'admin-login.php');

    $cur = fn(string $a) => $area === $a ? ' aria-current="page"' : '';

    $items = [];

    if ($area === 'student') {
        $items = [
            'student-dashboard.php' => 'Overview',
            'student-profile.php' => 'Profile',
            'student-fees.php' => 'Fees',
            'student-maintenance.php' => 'Maintenance',
            'student-guests.php' => 'Guests',
            'student-meals.php' => 'My meals',
            'student-mess.php' => 'Menu',
            'student-complaints.php' => 'Complaints',
            'student-notices.php' => 'Notices',
        ];
    } elseif ($area === 'admin') {
        $items = [
            'admin-dashboard.php' => 'Overview',
            'admin-students.php' => 'Students',
            'admin-rooms.php' => 'Rooms',
            'admin-fees.php' => 'Fees',
            'admin-maintenance.php' => 'Maintenance',
            'admin-guests.php' => 'Guests',
            'admin-notices.php' => 'Notices',
            'admin-complaints.php' => 'Complaints',
            'admin-meals.php' => 'Meal count',
            'admin-mess.php' => 'Mess',
        ];
    }

    $me = $GLOBALS['me'] ?? null;
?>

<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= e($title) ?> | BUP Hall Management</title>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Hind+Siliguri:wght@500&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap">

<link rel="stylesheet" href="<?= $base ?>assets/css/style.css">

<script src="<?= $base ?>assets/js/script.js" defer></script>

</head>

<body<?= empty($GLOBALS['_layout_chrome']) ? ' class="bup-login-page"' : '' ?>>

<div class="wrap">

<?php if (!empty($GLOBALS['_layout_chrome'])): ?>
    <!-- MAIN NAVBAR -->

    <header class="bar">

       <a class="brand" href="<?= $base ?>index.php">

    <img
        src="<?= $base ?>assets/images/bup-logo.jpg"
        alt="BUP Logo"
        class="brand-logo"
    >

    <div class="brand-text">
        <b>BUP Hall Management</b>
        <span>বিইউপি হল ব্যবস্থাপনা</span>
    </div>

</a>

        <!-- NAVIGATION LINKS -->

        <nav class="nav" aria-label="Main">

            <a href="<?= $base ?>index.php"<?= $area === 'public' ? ' aria-current="page"' : '' ?>>
                Home
            </a>

            <a href="<?= e($studentLink) ?>"<?= $cur('student') ?>>
                Student portal
            </a>

            <a href="<?= e($adminLink) ?>"<?= $cur('admin') ?>>
                Provost office
            </a>

        </nav>

<?php endif; ?>
    </header>


    <!-- MAIN CONTENT -->

    <main>

        <?= flash_render() ?>


        <?php if ($GLOBALS['_layout_rail']): ?>

        <div class="dash">

            <!-- SIDEBAR -->

            <aside class="rail" aria-label="Sections">

                <div class="who">

                    <p class="label">
                        <?= $area === 'admin' ? 'Signed in as' : 'Resident' ?>
                    </p>

                    <b>
                        <?= e($area === 'admin' ? 'Provost office' : ($me['name'] ?? '')) ?>
                    </b>

                </div>


                <!-- SIDEBAR LINKS -->

                <?php foreach ($items as $file => $label): ?>

                    <a href="<?= e($file) ?>"<?= $file === $active ? ' aria-current="page"' : '' ?>>
                        <?= e($label) ?>
                    </a>

                <?php endforeach; ?>


                <!-- LOGOUT BUTTON -->

                <form method="post" action="<?= $area ?>-logout.php">

                    <?= csrf_field() ?>

                    <button class="btn ghost small" type="submit">
                        Sign out
                    </button>

                </form>

            </aside>


            <!-- DASHBOARD CONTENT -->

            <section class="content">

        <?php endif; ?>

<?php
}


function layout_end(): void
{
    if (!empty($GLOBALS['_layout_rail'])) {
        echo "    </section>\n  </div>\n";
    }
?>

    </main>
<?php if (!empty($GLOBALS['_layout_chrome'])): ?>

    <!-- FOOTER -->

    <footer>

        <!-- <span>
            Demo for Bangladesh University of Professionals halls,
            Mirpur Cantonment, Dhaka. Not an official university system.
            All names and figures are sample data.
        </span>

        <span>
            PHP and MySQL. No AI backend.
        </span> -->


    </footer>
<?php endif; ?>

</div>

</body>

</html>

<?php
}


/**
 * Reusable notice card used on the home page
 * and the student portal.
 */

function notice_card(array $n): string
{
    return '<div class="item"><div class="meta"><span class="pill info">'
        . e($n['category'])
        . '</span><span class="muted mono">'
        . fdate($n['created_at'])
        . '</span></div><h3>'
        . e($n['title'])
        . '</h3><p class="muted">'
        . e($n['body'])
        . '</p></div>';
}
