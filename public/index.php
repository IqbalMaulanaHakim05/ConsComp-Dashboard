<?php
require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/Settings/pengaturan-publik.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache'); header('Expires: 0');
$pub = ambilPengaturanPublik($conn);
$namaSitus = $pub['nama_situs'] ?? 'Kalimayat Perkasa';
$judulHero = $pub['judul_hero'] ?? 'Solusi Terbaik untuk Kebutuhan Industri Anda';
$deskripsiHero = $pub['deskripsi_hero'] ?? 'Kelola data karyawan, absensi, cuti, penggajian, dan performa dalam satu sistem yang terintegrasi.';
$teksTombol = $pub['teks_tombol'] ?? 'Jelajahi Layanan';

// Nilai bawaan laman versi lama tetap dinormalisasi agar pengunjung langsung
// melihat company profile, tanpa menimpa personalisasi yang telah diisi admin.
if ($namaSitus === 'Profil Karyawan') $namaSitus = 'Kalimayat Perkasa';
if ($judulHero === 'Profil Pekerja Perusahaan' || preg_match('/^[A-Za-z]{6,12}$/', trim($judulHero)) || $judulHero === 'Kelola Karyawan Lebih Mudah dan Terintegrasi') $judulHero = 'Kelola SDM Lebih Efisien, Kerja Lebih Terorganisir';
if ($deskripsiHero === 'Website ini menyajikan informasi profil karyawan berdasarkan dataset Human Resources.' || $deskripsiHero === 'Menyediakan layanan engineering, konstruksi, dan pengadaan yang aman, profesional, dan tepat waktu.') $deskripsiHero = 'Kelola data karyawan, absensi, cuti, penggajian, dan performa dalam satu sistem yang terintegrasi.';
if ($teksTombol === 'Lihat Data Karyawan') $teksTombol = 'Jelajahi Layanan';
$warnaUtama = '#ffd500';
$warnaHero = '#082a57';
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="Profil perusahaan <?= htmlspecialchars($namaSitus); ?>"><title><?= htmlspecialchars($namaSitus); ?> | Company Profile</title><link rel="stylesheet" href="assets/css/publik.css?v=20260901-company-profile"><style>:root { --brand: <?= htmlspecialchars($warnaUtama); ?>; --navy: <?= htmlspecialchars($warnaHero); ?>; }</style></head>
<body><header class="site-header" id="beranda"><nav class="navbar container" aria-label="Navigasi utama"><a class="brand" href="#beranda" aria-label="<?= htmlspecialchars($namaSitus); ?>, beranda"><img src="assets/images/logo-kalinyamat-perkasa.png" alt="Logo <?= htmlspecialchars($namaSitus); ?>"><span><?= htmlspecialchars($namaSitus); ?></span></a><button class="menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false"><span></span><span></span><span></span></button><div class="nav-menu"><a href="#tentang">Tentang</a><a href="#mitra">Mitra</a><a class="nav-dashboard" style="padding:10px 15px;background:var(--brand);color:#062a56;box-shadow:0 6px 15px rgba(0,0,0,.16)" href="../src/Controllers/login.php">Login Dashboard <span aria-hidden="true">→</span></a></div></nav><section class="hero container"><div class="hero-copy"><p class="eyebrow">HUMAN RESOURCES · EMPLOYEE MANAGEMENT</p><h1><?= htmlspecialchars($judulHero); ?></h1><p><?= nl2br(htmlspecialchars($deskripsiHero)); ?></p><a class="play-link" href="https://kalinyamatperkasa.co.id/" target="_blank" rel="noopener noreferrer"><span aria-hidden="true">↗</span><?= htmlspecialchars($teksTombol); ?></a></div><div class="hero-orbit" aria-hidden="true"><i></i><i></i><i></i></div></section></header>
<main><section class="about container section" id="tentang"><div class="about-copy reveal"><p class="section-kicker">Platform terintegrasi <?= htmlspecialchars($namaSitus); ?></p><h2>Kelola tim dengan <em>lebih mudah</em> dan terarah.</h2><p><?= nl2br(htmlspecialchars($deskripsiHero)); ?></p><a href="#kontak" class="text-link"></a></div><figure class="industry-visual reveal" style="margin:0"><img src="assets/images/company-team.jpg" alt="Tim Kalinyamat Perkasa di area proyek industri" style="display:block;width:100%;height:100%;object-fit:cover;object-position:center"><div class="photo-frame"></div></figure></section>
<section class="services section" id="layanan"><div class="container"><div class="section-heading reveal"><p class="section-kicker">Keahlian kami</p><h2>Apa yang kami tawarkan</h2><p>Solusi terintegrasi untuk menunjang kebutuhan proyek dan operasional Anda.</p></div><div class="service-grid"><?php foreach ([['⌂','Civil','Pekerjaan sipil dan konstruksi'],['⚙','Mechanical','Instalasi dan perawatan mekanikal'],['ϟ','Electrical','Sistem kelistrikan yang andal'],['▦','O & M Service','Operasional dan pemeliharaan'],['♙','Trading','Pengadaan material dan peralatan'],['⌘','EPS','Peralatan dan suku cadang'],['◲','EPC','Engineering, procurement, construction'],['▥','MPS','Solusi pasokan tenaga dan material'],['◈','HSE','Keselamatan, kesehatan, dan lingkungan kerja'],['◉','QA/QC','Jaminan mutu dan pengendalian kualitas'],['⌬','Logistics','Dukungan logistik dan rantai pasok'],['✦','Consulting','Konsultasi teknis dan manajemen proyek']] as $layanan): ?><article class="service-card reveal"><span class="service-icon" aria-hidden="true"><?= $layanan[0]; ?></span><h3><?= $layanan[1]; ?></h3><p><?= $layanan[2]; ?></p><span class="card-arrow">↗</span></article><?php endforeach; ?></div></div></section>
<section class="partners container section" id="mitra"><div class="section-heading reveal"><p class="section-kicker">Kepercayaan pelanggan</p><h2>Mitra kami</h2><p>Kami bangga dapat tumbuh bersama berbagai perusahaan dan institusi.</p></div><div class="partner-logos reveal" aria-label="Daftar mitra"><?php foreach ([['partner-agp.png','PT Adhi Guna Putera'],['partner-pln-np.png','PLN Nusantara Power'],['partner-kpjb.png','PT KPJB'],['partner-pln-nps.png','PLN Nusantara Power Services'],['partner-medco.png','MedcoEnergi'],['partner-bjs.png','PT Bhumi Jepara Service'],['partner-pln.png','PLN']] as $mitra): ?><span><img src="assets/images/<?= $mitra[0]; ?>" alt="<?= htmlspecialchars($mitra[1]); ?>"></span><?php endforeach; ?></div></section></main>
<footer id="kontak"><div class="container footer-grid"><div><a class="brand footer-brand" href="#beranda"><img src="assets/images/logo-kalinyamat-perkasa.png" alt=""><span><?= htmlspecialchars($namaSitus); ?></span></a><p>Mitra terpercaya untuk solusi industri dan proyek yang berkelanjutan.</p></div><div><h3>Hubungi Kami</h3><p><a href="https://www.google.co.id/maps/place/Kantor+PT.+Kalinyamat+Perkasa/@-6.968667,110.1234954,8z/data=!4m6!3m5!1s0x2e708bbbb3bfcb01:0xfb44a237d99c0011!8m2!3d-7.000004!4d110.3312482!16s%2Fg%2F11pr2ctqmm?entry=ttu" target="_blank" rel="noopener noreferrer">Jl. Bukit Watu Wila Permata Puri Blok H-IV<br>No. 4 RT. 01 RW. 11 – Ngaliyan – Semarang</a></p><p><a href="tel:+622917560707">0291 7560707</a><br><a href="mailto:info@kalinyamatperkasa.co.id">info@kalinyamatperkasa.co.id</a></p></div></div><div class="copyright container">© <?= date('Y'); ?> <?= htmlspecialchars($namaSitus); ?>. Seluruh hak cipta dilindungi.</div></footer>
<script>const toggle=document.querySelector('.menu-toggle'),menu=document.querySelector('.nav-menu');toggle.addEventListener('click',()=>{const open=toggle.getAttribute('aria-expanded')==='true';toggle.setAttribute('aria-expanded',String(!open));menu.classList.toggle('open',!open)});document.querySelectorAll('.nav-menu a').forEach(a=>a.addEventListener('click',()=>{menu.classList.remove('open');toggle.setAttribute('aria-expanded','false')}));const items=document.querySelectorAll('.reveal');if('IntersectionObserver'in window){const observer=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('is-visible');observer.unobserve(e.target)}}),{threshold:.12});items.forEach(item=>observer.observe(item))}else items.forEach(item=>item.classList.add('is-visible'));</script><script>let themeBtn=document.querySelector('.nav-menu .theme-toggle');if(!themeBtn){themeBtn=document.createElement('button');themeBtn.className='theme-toggle';document.querySelector('.nav-menu').appendChild(themeBtn)}function setTheme(mode){document.body.classList.toggle('dark-mode',mode==='dark');if(themeBtn)themeBtn.textContent=mode==='dark'?'☀ Light':'☾ Dark';localStorage.setItem('company-theme',mode)}if(themeBtn){setTheme(localStorage.getItem('company-theme')||'light');themeBtn.addEventListener('click',()=>setTheme(document.body.classList.contains('dark-mode')?'light':'dark'))}</script><script>(function(){const rail=document.querySelector(".service-grid");if(!rail)return;const originals=[...rail.children];originals.forEach(card=>{const clone=card.cloneNode(true);clone.classList.remove("reveal");clone.classList.add("is-visible");rail.appendChild(clone)});let cycle=0;function tick(){if(!cycle)cycle=rail.scrollWidth/2;rail.scrollLeft+=1;if(rail.scrollLeft>=cycle-2)rail.scrollLeft=rail.scrollLeft%cycle;requestAnimationFrame(tick)}requestAnimationFrame(tick)})();</script><script>document.querySelector(".hero").addEventListener("click",e=>{for(let i=0;i<28;i++){const dot=document.createElement("i");dot.className="click-spark";dot.style.left=e.clientX+"px";dot.style.top=e.clientY+"px";dot.style.setProperty("--dx",(Math.random()*180-90)+"px");dot.style.setProperty("--dy",(Math.random()*180-90)+"px");dot.style.setProperty("--size",(Math.random()*7+4)+"px");document.body.appendChild(dot);setTimeout(()=>dot.remove(),1500)}});</script><script>(function(){const img=document.querySelector(".industry-visual img");if(!img)return;const photos=["assets/images/company-team.jpg","assets/images/company-plant.jpg","assets/images/company-forklift.jpg"];let i=0;setInterval(()=>{i=(i+1)%photos.length;img.classList.add("photo-fade");setTimeout(()=>{img.src=photos[i];img.classList.remove("photo-fade")},450)},5000)})();</script></body></html>


























