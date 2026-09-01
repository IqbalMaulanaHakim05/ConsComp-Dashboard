<?php

// Root project adalah URL yang dibuka Laragon. Arahkan ke entry point publik
// agar tidak ada lagi versi lama halaman dataset karyawan yang terakses.
$query = $_SERVER['QUERY_STRING'] ?? '';
$target = 'public/' . ($query !== '' ? '?' . $query : '');

header('Location: ' . $target, true, 302);
exit;
