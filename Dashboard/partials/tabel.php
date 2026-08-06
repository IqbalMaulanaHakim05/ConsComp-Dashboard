<?php

/*
|--------------------------------------------------------------------------
| Bagian tabel data karyawan (pencarian + pembatasan baris).
| Variabel yang diharapkan:
|   $hasil, $jumlahData, $totalCocok, $kataKunci,
|   $batas, $batasDiizinkan, $tanpaBatas, $izinkanSemua
|--------------------------------------------------------------------------
*/

// Form dan tombol reset dikembalikan ke halaman yang sedang aktif.
$halamanIni = basename($_SERVER["PHP_SELF"]);

// Kolom aksi (edit/hapus) hanya untuk peran yang boleh mengubah data.
$bolehAksi = function_exists("punyaRole") && punyaRole("admin", "superadmin");

// Nilai pagination (dengan cadangan bila tidak dikirim halaman pemanggil).
$halaman = $halaman ?? 1;
$totalHalaman = $totalHalaman ?? 1;
$offset = $offset ?? 0;

// Rentang baris yang sedang ditampilkan.
$mulai = $jumlahData > 0 ? ($offset + 1) : 0;
$sampai = $offset + $jumlahData;

// Parameter dasar untuk mempertahankan pencarian & batas pada tautan halaman.
$paramDasar = [];
if ($kataKunci !== "") {
    $paramDasar["cari"] = $kataKunci;
}
$paramDasar["filter"] = $filterKolom ?? "semua";
$paramDasar["batas"] = $tanpaBatas ? "semua" : $batas;

?>
    <section class="data-card">

        <div class="data-card-header">
            <h2>Data Karyawan</h2>

            <form method="GET" class="search-form">
                <input
                    type="text"
                    name="cari"
                    placeholder="Cari nama, ID, posisi, atau departemen"
                    value="<?= htmlspecialchars($kataKunci); ?>"
                >

                <select name="filter" title="Pilih kolom pencarian">
                    <?php foreach (["semua"=>"Semua kolom", "id"=>"ID", "posisi"=>"Posisi", "departemen"=>"Departemen", "gaji"=>"Gaji", "tanggal_masuk"=>"Tanggal masuk", "status_kerja"=>"Status kerja", "performa"=>"Performa"] as $nilaiFilter=>$labelFilter): ?>
                        <option value="<?= $nilaiFilter; ?>" <?= ($filterKolom ?? "semua") === $nilaiFilter ? "selected" : ""; ?>><?= $labelFilter; ?></option>
                    <?php endforeach; ?>
                </select>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Cari
                </button>

                <select
                    name="batas"
                    onchange="this.form.submit()"
                    title="Jumlah baris yang ditampilkan"
                >
                    <?php foreach ($batasDiizinkan as $opsiBatas): ?>
                        <option
                            value="<?= $opsiBatas; ?>"
                            <?= (!$tanpaBatas && $batas === $opsiBatas) ? "selected" : ""; ?>
                        >
                            <?= $opsiBatas; ?> baris
                        </option>
                    <?php endforeach; ?>

                    <?php if ($izinkanSemua): ?>
                        <option
                            value="semua"
                            <?= $tanpaBatas ? "selected" : ""; ?>
                        >
                            Semua
                        </option>
                    <?php endif; ?>
                </select>

                <?php if ($kataKunci !== ""): ?>
                    <a
                        href="<?= htmlspecialchars($halamanIni); ?>"
                        class="btn btn-secondary"
                    >
                        Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="result-info">
            <?php if ($kataKunci !== ""): ?>
                Ditemukan
                <strong><?= $totalCocok; ?></strong>
                data untuk pencarian
                <strong><?= htmlspecialchars($kataKunci); ?></strong>,
                menampilkan baris
                <strong><?= $mulai; ?>&ndash;<?= $sampai; ?></strong>.
            <?php else: ?>
                Menampilkan baris
                <strong><?= $mulai; ?>&ndash;<?= $sampai; ?></strong>
                dari
                <strong><?= $totalCocok; ?></strong>
                data karyawan.
            <?php endif; ?>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>No.</th>
                    <th>ID Karyawan</th>
                    <th>Nama</th>
                    <th>Posisi</th>
                    <th>Departemen</th>
                    <th>Gaji</th>
                    <th>Tanggal Masuk</th>
                    <th>Status Kerja</th>
                    <th>Performa</th>
                    <?php if ($bolehAksi): ?>
                        <th>Aksi</th>
                    <?php endif; ?>
                </tr>
                </thead>

                <tbody>

                <?php if ($jumlahData > 0): ?>

                    <?php $nomor = $offset + 1; ?>

                    <?php while (
                        $row = mysqli_fetch_assoc($hasil)
                    ): ?>
                        <tr>
                            <td>
                                <?= $nomor++; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["emp_id"] ?? "-"
                                ); ?>
                            </td>

                            <td>
                                <a
                                    class="employee-profile-link"
                                    href="profil-karyawan.php?id=<?= (int) $row["id"]; ?>"
                                >
                                    <?= htmlspecialchars(
                                        $row["employee_name"] ?? "-"
                                    ); ?>
                                </a>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["position"] ?? "-"
                                ); ?>
                            </td>

                            <td>
                                <span class="badge">
                                    <?= htmlspecialchars(
                                        $row["department"] ?? "-"
                                    ); ?>
                                </span>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) (
                                        $row["salary"] ?? 0
                                    ),
                                    2,
                                    ",",
                                    "."
                                ); ?>
                            </td>

                            <td>
                                <?php
                                $tanggalMasuk =
                                    $row["date_of_hire"] ?? "";

                                if (
                                    $tanggalMasuk !== ""
                                    && $tanggalMasuk !== null
                                    && strtotime(
                                        $tanggalMasuk
                                    ) !== false
                                ) {
                                    echo date(
                                        "d-m-Y",
                                        strtotime(
                                            $tanggalMasuk
                                        )
                                    );
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["employment_status"] ?? "-"
                                ); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["performance_score"] ?? "-"
                                ); ?>
                            </td>

                            <?php if ($bolehAksi): ?>
                            <td>
                                <div class="action-buttons">
                                    <a
                                        href="fungsi/edit.php?id=<?= (int) $row["id"]; ?>"
                                        class="btn btn-warning"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        href="fungsi/hapus.php?id=<?= (int) $row["id"]; ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm(
                                            'Yakin ingin menghapus data karyawan ini?'
                                        );"
                                    >
                                        Hapus
                                    </a>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td
                            colspan="<?= $bolehAksi ? 10 : 9; ?>"
                            class="empty-data"
                        >
                            <?php if ($kataKunci !== ""): ?>
                                Data yang dicari tidak ditemukan.
                            <?php else: ?>
                                Data karyawan belum tersedia.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>

        <?php if (!$tanpaBatas && $totalHalaman > 1): ?>
            <div class="pagination">
                <div class="pagination-info">
                    Halaman <strong><?= $halaman; ?></strong>
                    dari <strong><?= $totalHalaman; ?></strong>
                </div>

                <div class="pagination-nav">
                    <?php if ($halaman > 1): ?>
                        <a href="?<?= htmlspecialchars(
                            http_build_query($paramDasar + ["hal" => $halaman - 1])
                        ); ?>">
                            &larr; Sebelumnya
                        </a>
                    <?php else: ?>
                        <span class="disabled">&larr; Sebelumnya</span>
                    <?php endif; ?>

                    <?php if ($halaman < $totalHalaman): ?>
                        <a href="?<?= htmlspecialchars(
                            http_build_query($paramDasar + ["hal" => $halaman + 1])
                        ); ?>">
                            Berikutnya &rarr;
                        </a>
                    <?php else: ?>
                        <span class="disabled">Berikutnya &rarr;</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </section>
