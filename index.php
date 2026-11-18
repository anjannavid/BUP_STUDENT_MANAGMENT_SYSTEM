<?php
require_once __DIR__ . '/includes/layout.php';

layout_start('Home', 'public', '', false, false);
?>

<!-- BUP CURVED SPLIT HERO SECTION -->
<section class="bup-split-hero">

    <!-- LEFT HALL IMAGE -->
    <div class="bup-hero-left">
        <img
            src="assets/images/hall.jpg"
            alt="BUP Hall building beside the lake"
            class="hall-background-image"
            loading="eager"
        >

        <div class="bup-hero-image-overlay"></div>

        <div class="bup-welcome-content">
            <h1>Welcome</h1>
            <h2>to BUP Hall Management System</h2>
        </div>
    </div>

    <!-- S-SHAPED RED AND BLUE CURVE -->
    <svg class="bup-curve-divider"
         viewBox="0 0 600 900"
         preserveAspectRatio="none"
         xmlns="http://www.w3.org/2000/svg"
         aria-hidden="true">

        <defs>
            <linearGradient id="bupCurveGradient"
                x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#ff001e"/>
                <stop offset="48%" stop-color="#ff001e"/>
                <stop offset="72%" stop-color="#009fe8"/>
                <stop offset="100%" stop-color="#009fe8"/>
            </linearGradient>
        </defs>

        <path
            d="M 40 0
               C 230 70, 360 240, 170 450
               C 30 620, 20 790, 210 900
               L 600 900
               L 600 0 Z"
            fill="#ffffff"
        />

        <path
            d="M 40 0
               C 230 70, 360 240, 170 450
               C 30 620, 20 790, 210 900"
            fill="none"
            stroke="url(#bupCurveGradient)"
            stroke-width="18"
        />
    </svg>

    <!-- RIGHT WHITE PANEL -->
    <div class="bup-hero-right">

        <div class="bup-university-brand">
            <img
                src="assets/images/bup-logo.jpg"
                alt="Bangladesh University of Professionals Logo"
            >

            <h2>
                BANGLADESH UNIVERSITY OF
                PROFESSIONALS
            </h2>

            <div class="university-line"></div>
        </div>

        <div class="bup-portal-content bup-sso-login">
            <p class="bup-sso-lead">
                Choose your portal to sign in to BUP Hall Management.
            </p>

            <nav aria-label="Hall portals">
                <a class="bup-portal-btn bup-student-btn" href="student/student-login.php">Student Portal <span aria-hidden="true">→</span></a>
                <a class="bup-portal-btn bup-admin-btn" href="admin/admin-login.php">Provost Office <span aria-hidden="true">→</span></a>
            </nav>
        </div>

        <div class="bup-hero-footer">
            Copyright © <?= date('Y') ?>
            <a href="index.php">BUP</a>
            | Developed by Navid
        </div>
    </div>
</section>

<?php layout_end(); ?>
