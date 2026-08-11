import fs from "node:fs/promises";
import { Workbook, SpreadsheetFile } from "@oai/artifact-tool";

const outputDir = "C:/laragon/www/website-karyawan/outputs/employee-composition-20260811";
const allocations = [
  ["FINANCE", "Direktur Utama", 1], ["FINANCE", "Manager Keuangan", 1],
  ["FINANCE", "Staff Keuangan", 5], ["FINANCE", "Admin", 2],
  ["FINANCE", "Pengadaan", 2], ["FINANCE", "Senior Advisor", 1],

  ["PROJECT", "Direktur", 1], ["PROJECT", "Site Manager PJBS", 1],
  ["PROJECT", "Business Advisor", 1], ["PROJECT", "Manager Contract Liaison", 1],
  ["PROJECT", "Staff Project", 6], ["PROJECT", "PIC", 2],
  ["PROJECT", "Site Manager", 2], ["PROJECT", "Leader Project", 3],
  ["PROJECT", "Surveyor", 4], ["PROJECT", "Admin Legal", 2],
  ["PROJECT", "HSSE", 2], ["PROJECT", "Admin HSSE", 2], ["PROJECT", "Driver", 3],

  ["HRGA", "Manager HRD", 1], ["HRGA", "Admin HR GA", 4],
  ["HRGA", "Staff IT", 3], ["HRGA", "Cleaning Office", 8],
  ["HRGA", "Security", 10], ["HRGA", "OB NPA", 5],
  ["HRGA", "Leader Housekeeping", 2], ["HRGA", "Helper Housekeeping", 5],

  ["OPERATIONAL & COMMERCIAL", "Admin Ops", 4],
  ["OPERATIONAL & COMMERCIAL", "Koordinator AYM FGD FABA", 2],
  ["OPERATIONAL & COMMERCIAL", "Admin AYM", 2],
  ["OPERATIONAL & COMMERCIAL", "Admin Pemanfaatan FABA", 2],
  ["OPERATIONAL & COMMERCIAL", "PIC MG JT Roadsweeper", 1],
  ["OPERATIONAL & COMMERCIAL", "PIC FGD FABA", 1],
  ["OPERATIONAL & COMMERCIAL", "Operator Alat Berat", 13],
  ["OPERATIONAL & COMMERCIAL", "Shift Leader", 5],
  ["OPERATIONAL & COMMERCIAL", "Main Gate", 6],
  ["OPERATIONAL & COMMERCIAL", "Jembatan Timbang", 4],
  ["OPERATIONAL & COMMERCIAL", "Rigger", 6],
  ["OPERATIONAL & COMMERCIAL", "Operator Pompa", 6],
  ["OPERATIONAL & COMMERCIAL", "Operator Jaringan", 4],
  ["OPERATIONAL & COMMERCIAL", "Cleaning Ash Yard", 10],
  ["OPERATIONAL & COMMERCIAL", "Grass Cutting", 5],
  ["OPERATIONAL & COMMERCIAL", "Mekanik", 8],
  ["OPERATIONAL & COMMERCIAL", "Staff Gudang", 4],
  ["OPERATIONAL & COMMERCIAL", "OW", 3],
  ["OPERATIONAL & COMMERCIAL", "Operator FGD", 6],
  ["OPERATIONAL & COMMERCIAL", "Mekanik FABA", 4],
  ["OPERATIONAL & COMMERCIAL", "Operator FABA", 9],
  ["OPERATIONAL & COMMERCIAL", "Supervisor Conveyor", 2],
  ["OPERATIONAL & COMMERCIAL", "Mekanik Conveyor", 3],
  ["OPERATIONAL & COMMERCIAL", "Operator Conveyor", 7],
  ["OPERATIONAL & COMMERCIAL", "Biopori", 3],
  ["OPERATIONAL & COMMERCIAL", "Mekanik HEM TJB", 4],
  ["OPERATIONAL & COMMERCIAL", "Operator Roadsweeper", 4],
  ["OPERATIONAL & COMMERCIAL", "Elektrik", 3],
  ["OPERATIONAL & COMMERCIAL", "Operator Fuel", 3],
  ["OPERATIONAL & COMMERCIAL", "Environment", 2],
  ["OPERATIONAL & COMMERCIAL", "Supervisor Mekanik HEM", 2],
  ["OPERATIONAL & COMMERCIAL", "Leader FABA", 2],
];

const salaryByRole = (role) => {
  if (/Direktur Utama/.test(role)) return 30000000;
  if (/Direktur/.test(role)) return 25000000;
  if (/Manager|Senior Advisor|Business Advisor/.test(role)) return 15000000;
  if (/Site Manager/.test(role)) return 13500000;
  if (/Supervisor|Koordinator/.test(role)) return 9500000;
  if (/Leader|PIC|HSSE/.test(role)) return 8000000;
  if (/Staff IT|Elektrik|Mekanik/.test(role)) return 7000000;
  if (/Admin|Staff|Surveyor|Pengadaan|Environment/.test(role)) return 6000000;
  if (/Operator|Rigger|Driver|Security|Main Gate|Jembatan/.test(role)) return 5500000;
  return 4800000;
};

const firstNames = ["Andi","Budi","Citra","Dedi","Eka","Fajar","Gita","Hendra","Indah","Joko","Kartika","Lukman","Maya","Naufal","Oktavia","Putra","Qori","Rizky","Sari","Taufik","Utami","Vina","Wahyu","Yuni","Zaki"];
const lastNames = ["Pratama","Santoso","Wijaya","Lestari","Saputra","Hidayat","Permata","Kurniawan","Ramadhan","Maulana","Nugroho"];
const employees = [];
let seq = 1;
for (const [department, position, count] of allocations) {
  for (let i = 0; i < count; i++) {
    const idx = seq - 1;
    const femaleOffice = /Admin|Finance|HR|Legal|Staff Keuangan/.test(position) && idx % 2 === 0;
    const gender = femaleOffice || idx % 7 === 0 ? "Perempuan" : "Laki-laki";
    const base = salaryByRole(position);
    const salary = base + (idx % 4) * 250000;
    const year = 2018 + (idx % 9);
    const month = idx % 12;
    const day = 1 + (idx % 27);
    employees.push([
      `EMP${String(seq).padStart(3, "0")}`,
      `${firstNames[idx % firstNames.length]} ${lastNames[Math.floor(idx / firstNames.length) % lastNames.length]}`,
      position, department, salary, gender,
      idx % 3 === 0 ? "Menikah" : "Belum Menikah",
      new Date(year, month, day),
      idx % 10 === 0 ? "Kontrak" : "Aktif",
      72 + (idx * 7) % 27,
    ]);
    seq++;
  }
}
if (employees.length !== 220) throw new Error(`Komposisi harus 220, saat ini ${employees.length}`);

const wb = Workbook.create();
const data = wb.worksheets.add("Data Karyawan");
const summary = wb.worksheets.add("Komposisi");
const guide = wb.worksheets.add("Panduan");
const headers = ["ID Karyawan","Nama Karyawan","Posisi","Departemen","Gaji","Jenis Kelamin","Status Pernikahan","Tanggal Masuk","Status Kerja","Skor Performa"];
data.getRange("A1:J1").values = [headers];
data.getRange(`A2:J${employees.length + 1}`).values = employees;
data.tables.add(`A1:J${employees.length + 1}`, true, "DataKaryawanTable").style = "TableStyleMedium2";
data.freezePanes.freezeRows(1);
data.showGridLines = false;
data.getRange("A1:J1").format = { fill: "#1E3A5F", font: {bold:true,color:"#FFFFFF"}, rowHeight: 26 };
data.getRange(`E2:E${employees.length + 1}`).format.numberFormat = '"Rp" #,##0';
data.getRange(`H2:H${employees.length + 1}`).format.numberFormat = "yyyy-mm-dd";
data.getRange(`J2:J${employees.length + 1}`).format.numberFormat = "0";
const widths = [14,22,28,28,16,16,19,16,14,16];
widths.forEach((w, i) => data.getRangeByIndexes(0, i, employees.length + 1, 1).format.columnWidth = w);

summary.getRange("A1:F1").merge();
summary.getRange("A1").values = [["ESTIMASI KOMPOSISI KARYAWAN"]];
summary.getRange("A1:F1").format = {fill:"#1E3A5F",font:{bold:true,color:"#FFFFFF",size:16},horizontalAlignment:"center",rowHeight:34};
summary.getRange("A3:B3").values = [["Total estimasi karyawan", null]];
summary.getRange("B3").formulas = [["=COUNTA('Data Karyawan'!$A$2:$A$221)"]];
summary.getRange("A3:B3").format = {fill:"#DCEAF7",font:{bold:true,color:"#17324D"},borders:{preset:"outside",style:"thin",color:"#9CB6CE"}};
summary.getRange("A5:B5").values = [["Departemen","Jumlah Karyawan"]];
const departments = ["FINANCE","PROJECT","HRGA","OPERATIONAL & COMMERCIAL"];
summary.getRange("A6:A9").values = departments.map(x => [x]);
summary.getRange("B6").formulas = [["=COUNTIF('Data Karyawan'!$D$2:$D$221,A6)"]];
summary.getRange("B6:B9").fillDown();
summary.getRange("D5:F5").values = [["Departemen","Posisi","Jumlah"]];
summary.getRange(`D6:E${allocations.length + 5}`).values = allocations.map(([d,p]) => [d,p]);
summary.getRange("F6").formulas = [["=COUNTIFS('Data Karyawan'!$D$2:$D$221,D6,'Data Karyawan'!$C$2:$C$221,E6)"]];
summary.getRange(`F6:F${allocations.length + 5}`).fillDown();
summary.getRange("A5:B9").format.borders = {preset:"all",style:"thin",color:"#D5DEE8"};
summary.getRange(`D5:F${allocations.length + 5}`).format.borders = {preset:"all",style:"thin",color:"#D5DEE8"};
summary.getRange("A5:B5").format = {fill:"#2E6BAE",font:{bold:true,color:"#FFFFFF"}};
summary.getRange("D5:F5").format = {fill:"#2E6BAE",font:{bold:true,color:"#FFFFFF"}};
summary.getRange("A1:F100").format.font = {name:"Aptos",size:10};
summary.getRange("A1:F1").format.font = {name:"Aptos Display",size:16,bold:true,color:"#FFFFFF"};
summary.getRange("A:A").format.columnWidth = 30;
summary.getRange("B:B").format.columnWidth = 18;
summary.getRange("C:C").format.columnWidth = 4;
summary.getRange("D:D").format.columnWidth = 28;
summary.getRange("E:E").format.columnWidth = 30;
summary.getRange("F:F").format.columnWidth = 14;
summary.freezePanes.freezeRows(1);
summary.showGridLines = false;

  guide.getRange("A1:B1").merge();
guide.getRange("A1").values = [["PANDUAN & ASUMSI DATA"]];
  guide.getRange("A1:B1").format = {fill:"#1E3A5F",font:{bold:true,color:"#FFFFFF",size:16},horizontalAlignment:"center",rowHeight:34};
const notes = [
  ["Tujuan", "Data contoh untuk memperkirakan komposisi karyawan dan menguji fitur import Excel."],
  ["Jumlah", "220 data karyawan sintetis; bukan data karyawan sebenarnya."],
  ["Departemen", "FINANCE, PROJECT, HRGA, dan OPERATIONAL & COMMERCIAL."],
  ["Format import", "Gunakan sheet pertama (Data Karyawan). Nama kolom telah disesuaikan dengan importer aplikasi."],
  ["Peringatan", "Fitur import aplikasi saat ini mengganti seluruh data karyawan. Cadangkan database sebelum import."],
  ["Tanggal", "Tanggal Masuk disimpan sebagai tanggal Excel dan ditampilkan yyyy-mm-dd."],
  ["Penyesuaian", "Ubah nama, gaji, status, dan jumlah baris sesuai data resmi perusahaan sebelum digunakan."],
];
guide.getRange("A3:B9").values = notes;
guide.getRange("A3:A9").format = {fill:"#DCEAF7",font:{bold:true,color:"#17324D"}};
guide.getRange("A3:B9").format.borders = {preset:"all",style:"thin",color:"#D5DEE8"};
guide.getRange("A:A").format.columnWidth = 22;
guide.getRange("B:B").format.columnWidth = 85;
guide.getRange("B3:B9").format.wrapText = true;
guide.getRange("A3:B9").format.rowHeight = 30;
guide.showGridLines = false;

await fs.mkdir(outputDir, {recursive:true});
for (const [sheetName, range] of [["Data Karyawan","A1:J24"],["Komposisi",`A1:F${allocations.length + 5}`],["Panduan","A1:B9"]]) {
  const img = await wb.render({sheetName, range, scale:1, format:"png"});
  await fs.writeFile(`${outputDir}/${sheetName.replaceAll(" ","-").toLowerCase()}.png`, new Uint8Array(await img.arrayBuffer()));
}
const inspect = await wb.inspect({kind:"table",range:"Komposisi!A1:F15",include:"values,formulas",tableMaxRows:15,tableMaxCols:6});
console.log(inspect.ndjson);
const errors = await wb.inspect({kind:"match",searchTerm:"#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",options:{useRegex:true,maxResults:100},summary:"formula error scan"});
console.log(errors.ndjson);
const xlsx = await SpreadsheetFile.exportXlsx(wb);
await xlsx.save(`${outputDir}/estimasi-data-karyawan-220.xlsx`);
console.log(`EXPORTED ${outputDir}/estimasi-data-karyawan-220.xlsx`);
