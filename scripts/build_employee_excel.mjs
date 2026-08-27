import fs from 'node:fs/promises';
import { SpreadsheetFile, Workbook } from '@oai/artifact-tool';

const outputDir = 'outputs/karyawan-120';
await fs.mkdir(outputDir, { recursive: true });

const names = [
  'Andi Pratama','Budi Santoso','Citra Lestari','Dewi Anggraini','Eko Nugroho','Fajar Hidayat','Gita Permata','Hendra Wijaya','Indah Sari','Joko Susilo',
  'Kartika Putri','Lukman Hakim','Maya Amelia','Nanda Saputra','Olivia Maharani','Pandu Kurniawan','Qori Aulia','Rani Wulandari','Satria Ramadhan','Tika Anjani',
  'Umar Faruq','Vina Oktaviani','Wahyu Setiawan','Yuni Astuti','Zaki Maulana','Aditia Firmansyah','Bella Novitasari','Chandra Kusuma','Dian Puspita','Erwin Setiawan'
];
const positions = ['Software Engineer','HR Specialist','Finance Analyst','Marketing Executive','Sales Representative','Project Manager','UI/UX Designer','Data Analyst','Procurement Officer','Customer Service'];
const departments = ['Teknologi Informasi','Sumber Daya Manusia','Keuangan','Pemasaran','Penjualan','Operasional','Desain Produk','Analitik Data','Pengadaan','Layanan Pelanggan'];
const salaries = [8500000,7200000,7800000,6800000,6500000,12500000,9000000,10500000,7000000,5800000];

const headers = ['ID Karyawan','Nama Karyawan','Posisi','Departemen','Gaji','Jenis Kelamin','Status Pernikahan','Tanggal Masuk','Status Kerja','Skor Performa'];
const rows = [headers];
for (let i = 0; i < 120; i++) {
  const n = i + 1;
  const idx = i % 30;
  const role = i % 10;
  const gender = i % 2 === 0 ? 'M' : 'F';
  const hireYear = 2019 + (i % 7);
  const hireMonth = String((i % 12) + 1).padStart(2, '0');
  const hireDay = String((i % 27) + 1).padStart(2, '0');
  rows.push([
    `EMP${String(n).padStart(3, '0')}`,
    `${names[idx]} ${Math.floor(i / 30) + 1}`,
    positions[role],
    departments[role],
    salaries[role] + (Math.floor(i / 10) * 250000),
    gender,
    i % 3 === 0 ? 'Married' : 'Single',
    `${hireYear}-${hireMonth}-${hireDay}`,
    i % 11 === 0 ? 'Kontrak' : 'Aktif',
    72 + ((i * 7) % 29),
  ]);
}

const workbook = Workbook.create();
const sheet = workbook.worksheets.add('Data Karyawan');
sheet.showGridLines = false;
sheet.getRange(`A1:J${rows.length}`).values = rows;
sheet.getRange('A1:J1').format = { fill: '#1F4E78', font: { bold: true, color: '#FFFFFF' }, horizontalAlignment: 'center', verticalAlignment: 'center' };
sheet.getRange(`A1:J${rows.length}`).format.borders = { preset: 'all', style: 'thin', color: '#D9E2F3' };
sheet.getRange(`E2:E${rows.length}`).format.numberFormat = '#,##0';
sheet.getRange(`H2:H${rows.length}`).format.numberFormat = 'yyyy-mm-dd';
sheet.getRange(`J2:J${rows.length}`).format.numberFormat = '0';
sheet.getRange(`A2:A${rows.length}`).format.numberFormat = '@';
sheet.getRange(`A1:J${rows.length}`).format.verticalAlignment = 'center';
sheet.getRange(`E2:E${rows.length}`).format.horizontalAlignment = 'right';
sheet.getRange(`J2:J${rows.length}`).format.horizontalAlignment = 'center';
sheet.getRange('A:A').format.columnWidth = 14;
sheet.getRange('B:B').format.columnWidth = 24;
sheet.getRange('C:C').format.columnWidth = 24;
sheet.getRange('D:D').format.columnWidth = 22;
sheet.getRange('E:E').format.columnWidth = 14;
sheet.getRange('F:G').format.columnWidth = 18;
sheet.getRange('H:H').format.columnWidth = 16;
sheet.getRange('I:I').format.columnWidth = 16;
sheet.getRange('J:J').format.columnWidth = 14;
sheet.getRange('A1:J1').format.rowHeight = 28;
sheet.freezePanes.freezeRows(1);
sheet.tables.add(`A1:J${rows.length}`, true, 'DataKaryawan120');

const guide = workbook.worksheets.add('Petunjuk');
guide.showGridLines = false;
guide.getRange('A1:D5').values = [
  ['Template Import Data Karyawan', null, null, null],
  ['Jumlah data', 120, null, null],
  ['Sheet import', 'Data Karyawan', null, null],
  ['Catatan', 'Import akan mengganti seluruh data karyawan yang ada.', null, null],
  ['Nilai gender', 'M = Laki-laki, F = Perempuan', null, null],
];
guide.mergeCells('A1:D1');
guide.getRange('A1:D1').format = { fill: '#1F4E78', font: { bold: true, color: '#FFFFFF', size: 14 }, horizontalAlignment: 'center' };
guide.getRange('A2:A5').format = { fill: '#D9EAF7', font: { bold: true } };
guide.getRange('A1:D5').format.borders = { preset: 'all', style: 'thin', color: '#D9E2F3' };
guide.getRange('A:A').format.columnWidth = 20;
guide.getRange('B:B').format.columnWidth = 58;

const preview = await workbook.render({ sheetName: 'Data Karyawan', range: 'A1:J16', scale: 1, format: 'png' });
await fs.writeFile(`${outputDir}/preview.png`, new Uint8Array(await preview.arrayBuffer()));
const check = await workbook.inspect({ kind: 'table', range: 'Data Karyawan!A1:J6', include: 'values,formulas', tableMaxRows: 6, tableMaxCols: 10 });
console.log(check.ndjson);
const xlsx = await SpreadsheetFile.exportXlsx(workbook);
await xlsx.save(`${outputDir}/data-karyawan-120.xlsx`);
