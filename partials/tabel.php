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
                menampilkan
                <strong><?= $jumlahData; ?></strong>
                baris.
            <?php else: ?>
                Menampilkan
                <strong><?= $jumlahData; ?></strong>
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
                    <th>Jenis Kelamin</th>
                    <th>Status Pernikahan</th>
                    <th>Tanggal Masuk</th>
                    <th>Status Kerja</th>
                    <th>Performa</th>
                    <th>Aksi</th>
                </tr>
                </thead>

                <tbody>

                <?php if ($jumlahData > 0): ?>

                    <?php $nomor = 1; ?>

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
                                <?= htmlspecialchars(
                                    $row["employee_name"] ?? "-"
                                ); ?>
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
                                $gender = trim(
                                    $row["gender"] ?? ""
                                );

                                if (
                                    $gender === "M"
                                    || $gender === "Male"
                                ) {
                                    echo "Laki-laki";
                                } elseif (
                                    $gender === "F"
                                    || $gender === "Female"
                                ) {
                                    echo "Perempuan";
                                } else {
                                    echo htmlspecialchars(
                                        $gender !== ""
                                            ? $gender
                                            : "-"
                                    );
                                }
                                ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["marital_status"] ?? "-"
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
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td
                            colspan="12"
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

    </section>
