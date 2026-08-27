<?php

declare(strict_types=1);

require_once __DIR__ . '/../Services/Auth/auth.php';

logoutPengguna();

header('Location: ' . URL_DASAR . 'login.php?pesan=logout-berhasil');
exit;