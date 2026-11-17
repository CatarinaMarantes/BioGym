<?php
require_once __DIR__ . '/utils/session.php';

if (!isset($_SESSION['id'])) {
    redirecionar('/login.php');
}

redirecionar('/dashboard.php');
