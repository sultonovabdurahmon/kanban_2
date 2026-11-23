<?php
require __DIR__ . '/includes/config.php';
header('Location: ' . (user() ? 'dashboard.php' : 'login.php'));
