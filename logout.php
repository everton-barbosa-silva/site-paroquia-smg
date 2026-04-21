<?php
declare(strict_types=1);

require_once __DIR__ . '/auth/auth.php';

secretaria_logout();

header('Location: /login.php');
exit;
