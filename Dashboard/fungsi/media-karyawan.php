<?php

declare(strict_types=1);

/**
 * Memvalidasi & menyimpan satu file unggahan (foto JPG atau CV PDF).
 * Mengembalikan nama file tersimpan, atau null bila tidak ada file / gagal
 * (pesan kegagalan ditulis ke $pesan).
 */
function unggahMediaKaryawan(array $file, string $jenis, string &$pesan): ?string
{
    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $pesan = "File gagal diunggah.";
        return null;
    }

    $batas = $jenis === "foto" ? 2 * 1024 * 1024 : 5 * 1024 * 1024;
    if ((int) ($file["size"] ?? 0) > $batas) {
        $pesan = $jenis === "cv" ? "Ukuran CV maksimal 5 MB." : "Ukuran foto maksimal 2 MB.";
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, (string) $file["tmp_name"]) : "";
    if ($finfo) {
        finfo_close($finfo);
    }

    $aturan = $jenis === "foto" ? ["image/jpeg" => "jpg"] : ["application/pdf" => "pdf"];

    if (!isset($aturan[$mime])) {
        $pesan = $jenis === "cv" ? "CV harus berupa file PDF." : "Foto harus berupa JPG atau JPEG.";
        return null;
    }

    $nama = bin2hex(random_bytes(16)) . "." . $aturan[$mime];
    $folder = dirname(__DIR__) . "/uploads/" . ($jenis === "foto" ? "foto" : $jenis);

    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }

    if (!move_uploaded_file((string) $file["tmp_name"], $folder . "/" . $nama)) {
        $pesan = "File gagal disimpan.";
        return null;
    }

    return $nama;
}
