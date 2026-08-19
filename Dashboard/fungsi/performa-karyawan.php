<?php

declare(strict_types=1);

function normalisasiSkorPerforma(mixed $nilai): ?int
{
    $nilai = trim((string) $nilai);
    if ($nilai === "") return null;

    if (!preg_match('/^\d+$/', $nilai)) {
        throw new InvalidArgumentException("Skor performa harus berupa bilangan bulat antara 1 sampai 100, atau dikosongkan jika belum dinilai.");
    }

    $skor = (int) $nilai;
    if ($skor === 0) return null;
    if ($skor < 1 || $skor > 100) {
        throw new InvalidArgumentException("Skor performa harus berupa bilangan bulat antara 1 sampai 100, atau dikosongkan jika belum dinilai.");
    }

    return $skor;
}

function tampilkanSkorPerforma(mixed $nilai, string $nilaiKosong = "-"): string
{
    $nilai = trim((string) $nilai);
    return is_numeric($nilai) && (float) $nilai > 0 ? $nilai : $nilaiKosong;
}
