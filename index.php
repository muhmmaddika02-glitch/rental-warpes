<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

global $pdo;

$statDevices = 0;
$statCustomers = 0;
$statBookings = 0;
$devices = [];

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM devices WHERE status = 'available'");
    $statDevices = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    $statCustomers = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $statBookings = $stmt->fetch()['total'];
    
    $devices = $pdo->query("SELECT * FROM devices WHERE status != 'maintenance' ORDER BY type, name LIMIT 6")->fetchAll();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>GameZone - Pengalaman gaming terbaik</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800;900&amp;family=Inter:wght@400;500;600&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        "colors": {
                "on-surface": "#dae2fd",
                "on-secondary-container": "#00424e",
                "on-primary-fixed": "#25005a",
                "on-secondary-fixed-variant": "#004e5c",
                "surface-container-highest": "#2d3449",
                "primary-container": "#7c3aed",
                "surface-container": "#171f33",
                "on-secondary": "#003640",
                "surface": "#0b1326",
                "on-error-container": "#ffdad6",
                "ghost-white": "#F8FAFC",
                "on-primary-container": "#ede0ff",
                "error": "#ffb4ab",
                "inverse-primary": "#732ee4",
                "outline": "#958da1",
                "surface-container-lowest": "#060e20",
                "surface-container-high": "#222a3d",
                "on-tertiary-container": "#84ff9c",
                "on-primary": "#3f008e",
                "inverse-surface": "#dae2fd",
                "tertiary-fixed-dim": "#4ae176",
                "tertiary": "#4ae176",
                "surface-dim": "#0b1326",
                "secondary": "#4cd7f6",
                "border-low-opacity": "rgba(248, 250, 260, 0.1)",
                "primary": "#d2bbff",
                "secondary-fixed-dim": "#4cd7f6",
                "on-error": "#690005",
                "on-secondary-fixed": "#001f26",
                "surface-variant": "#2d3449",
                "on-tertiary-fixed-variant": "#005321",
                "on-background": "#dae2fd",
                "tertiary-fixed": "#6bff8f",
                "outline-variant": "#4a4455",
                "inverse-on-surface": "#283044",
                "slate-surface": "#1E293B",
                "surface-bright": "#31394d",
                "primary-fixed-dim": "#d2bbff",
                "on-tertiary-fixed": "#002109",
                "surface-container-low": "#131b2e",
                "error-container": "#93000a",
                "surface-tint": "#d2bbff",
                "primary-fixed": "#eaddff",
                "secondary-container": "#03b5d3",
                "secondary-fixed": "#acedff",
                "tertiary-container": "#007733",
                "on-tertiary": "#003915",
                "on-primary-fixed-variant": "#5a00c6",
                "on-surface-variant": "#ccc3d8",
                "background": "#05000a"
        },
        "borderRadius": {
                "DEFAULT": "0.25rem",
                "lg": "0.5rem",
                "xl": "0.75rem",
                "full": "9999px"
        },
        "spacing": {
                "base": "4px",
                "margin-mobile": "20px",
                "margin-desktop": "64px",
                "container-max": "1280px",
                "gutter": "24px"
        },
        "fontFamily": {
                "headline-sm": [
                        "Hanken Grotesk"
                ],
                "display-lg-mobile": [
                        "Hanken Grotesk"
                ],
                "code-sm": [
                        "JetBrains Mono"
                ],
                "body-lg": [
                        "Inter"
                ],
                "body-md": [
                        "Inter"
                ],
                "headline-md": [
                        "Hanken Grotesk"
                ],
                "display-lg": [
                        "Hanken Grotesk"
                ],
                "label-md": [
                        "JetBrains Mono"
                ]
        },
        "fontSize": {
                "headline-sm": [
                        "24px",
                        {
                                "lineHeight": "1.3",
                                "fontWeight": "600"
                        }
                ],
                "display-lg-mobile": [
                        "36px",
                        {
                                "lineHeight": "1.2",
                                "letterSpacing": "-0.02em",
                                "fontWeight": "800"
                        }
                ],
                "code-sm": [
                        "13px",
                        {
                                "lineHeight": "1.5",
                                "fontWeight": "400"
                        }
                ],
                "body-lg": [
                        "18px",
                        {
                                "lineHeight": "1.6",
                                "fontWeight": "400"
                        }
                ],
                "body-md": [
                        "16px",
                        {
                                "lineHeight": "1.5",
                                "fontWeight": "400"
                        }
                ],
                "headline-md": [
                        "32px",
                        {
                                "lineHeight": "1.2",
                                "fontWeight": "700"
                        }
                ],
                "display-lg": [
                        "56px",
                        {
                                "lineHeight": "1.1",
                                "letterSpacing": "-0.02em",
                                "fontWeight": "800"
                        }
                ],
                "label-md": [
                        "14px",
                        {
                                "lineHeight": "1.4",
                                "fontWeight": "500"
                        }
                ]
        }
},
    },
  }
</script>
<style>
        body {
            background: #05000a;
            color: white;
            overflow-x: hidden;
        }
        .logo-text {
            color: #ff00aa;
            text-shadow: 0 0 20px #ff00aa;
        }
        .hero-title {
            background: linear-gradient(90deg, #ff0099, #00c8ff);
            -webkit-background-clip: text;
            color: transparent;
        }
        .btn-neon {
            background: linear-gradient(90deg, #ff0099, #006eff);
            box-shadow: 0 0 25px #ff0099;
            color: white;
        }
        .card-neon {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #ff0099;
            transition: 0.3s;
        }
        .card-neon:hover {
            transform: translateY(-10px);
            box-shadow: 0 0 30px #ff0099;
        }
        .text-cyan {
            color: #00c8ff;
        }
        .nav-link {
            position: relative;
        }
        .nav-link:hover {
            color: #00c8ff;
        }
    </style>
</head>
<body class="bg-[#05000a] text-white font-body-md antialiased min-h-screen flex flex-col selection:bg-[#ff0099] selection:text-white">
<!-- Top Navigation -->
<nav class="w-full fixed top-0 z-50 bg-black/60 backdrop-blur-[10px] transition-all duration-300">
<div class="flex justify-between items-center max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop h-20">
<!-- Brand -->
<a class="flex items-center gap-3 hover:scale-105 active:scale-95 transition-transform" href="index.php">
<span class="font-display-lg text-[28px] font-black logo-text uppercase">GameZone</span>
</a>
<!-- Desktop Navigation Links -->
<div class="hidden md:flex space-x-10">
<a class="nav-link font-body-md text-[16px] font-medium text-white transition-colors" href="#devices">Perangkat</a>
<a class="nav-link font-body-md text-[16px] font-medium text-white transition-colors" href="#features">Fitur</a>
</div>
<!-- Actions -->
<div class="hidden md:flex items-center space-x-6">
<a class="font-label-md text-label-md text-white hover:text-[#00c8ff] transition-all px-4 py-2" href="login.php">Masuk</a>
<a class="font-label-md text-label-md btn-neon px-8 py-3 rounded-full hover:scale-105 active:scale-95 transition-all duration-300 font-bold tracking-wide" href="register.php">Register</a>
</div>
<!-- Mobile Menu Toggle -->
<button class="md:hidden text-white p-2">
<span class="material-symbols-outlined">menu</span>
</button>
</div>
</nav>
<!-- Main Content Canvas -->
<main class="flex-grow pt-20">
<!-- Hero Section -->
<section class="relative min-h-screen flex items-center justify-center overflow-hidden py-24 px-margin-mobile md:px-margin-desktop bg-[linear-gradient(rgba(0,0,0,0.5),rgba(0,0,0,0.9))]">
<div class="relative z-10 max-w-container-max mx-auto text-center flex flex-col items-center">
<img alt="Hero Image" class="w-[420px] max-w-[90%] drop-shadow-[0_0_25px_#ff00aa] mb-8 rounded-xl" src="assets/img/hero.png"/>
<h1 class="font-display-lg-mobile md:text-[55px] mb-6 max-w-5xl leading-tight font-black hero-title uppercase">
                    PLAY • CONNECT • WIN
                </h1>
<p class="font-body-lg text-[20px] text-[#ddd] mb-12 max-w-3xl leading-relaxed">
                    Pengalaman gaming terbaik. Pesan ruang gaming Anda, berkompetisi dalam turnamen, dan tingkatkan permainan Anda dengan perangkat keras premium.
                </p>
<div class="flex flex-col sm:flex-row gap-6 justify-center">
<a class="inline-flex items-center justify-center gap-3 btn-neon font-label-md text-[16px] px-10 py-4 rounded-[30px] hover:scale-105 active:scale-95 transition-all duration-300 font-bold tracking-wider" href="register.php">
                        MULAI SEKARANG
                    </a>
<a class="inline-flex items-center justify-center gap-3 card-neon text-white font-label-md text-[16px] px-10 py-4 rounded-[30px] transition-all duration-300 font-bold tracking-wider" href="#devices">
                        LIHAT PERANGKAT
                    </a>
</div>
</div>
</section>
<!-- Stats Section -->
<section class="py-16 bg-black/50 border-y border-[#ff0099]/20">
<div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
<div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-center divide-y md:divide-y-0 md:divide-x divide-white/10">
<div class="p-8 group">
<div class="font-display-lg text-6xl md:text-7xl text-[#00c8ff] mb-4 drop-shadow-[0_0_15px_#00c8ff] transition-transform group-hover:scale-110 duration-300"><?= $statDevices ?></div>
<div class="font-label-md text-[15px] font-bold text-white/80 uppercase tracking-[0.2em]">Perangkat Tersedia</div>
</div>
<div class="p-8 group">
<div class="font-display-lg text-6xl md:text-7xl text-[#ff0099] mb-4 drop-shadow-[0_0_15px_#ff0099] transition-transform group-hover:scale-110 duration-300"><?= $statCustomers ?></div>
<div class="font-label-md text-[15px] font-bold text-white/80 uppercase tracking-[0.2em]">Gamer Senang</div>
</div>
<div class="p-8 group">
<div class="font-display-lg text-6xl md:text-7xl text-[#00c8ff] mb-4 drop-shadow-[0_0_15px_#00c8ff] transition-transform group-hover:scale-110 duration-300"><?= $statBookings ?></div>
<div class="font-label-md text-[15px] font-bold text-white/80 uppercase tracking-[0.2em]">Pemesanan Dibuat</div>
</div>
</div>
</div>
</section>
<!-- Devices Gallery -->
<section class="py-24 px-margin-mobile md:px-margin-desktop relative" id="devices">
<div class="max-w-container-max mx-auto relative z-10">
<div class="text-center mb-16">
<h2 class="text-[40px] mb-4 font-black text-cyan uppercase tracking-wider">Perangkat Gaming Kami</h2>
<p class="font-body-md text-[18px] text-[#ddd] max-w-2xl mx-auto">Pilih dari setup gaming premium kami untuk sesi Anda berikutnya.</p>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($devices as $device): ?>
                <div class="card-neon rounded-[20px] overflow-hidden flex flex-col">
<div class="relative h-56 w-full overflow-hidden bg-black rounded-t-[20px]">
                            <?php if (!empty($device['image'])): ?>
                                <img alt="<?= escape($device['name']) ?>" class="w-full h-full object-cover opacity-80" src="assets/uploads/<?= htmlspecialchars($device['image']) ?>"/>
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-black">
                                    <span class="material-symbols-outlined text-6xl text-white/30">sports_esports</span>
                                </div>
                            <?php endif; ?>
</div>
<div class="p-8 flex flex-col flex-grow text-center">
<h3 class="text-[24px] mb-4 font-bold text-[#ff0099]"><?= escape($device['name']) ?></h3>
<p class="text-[35px] font-bold text-[#00c8ff] mb-4"><?= formatRupiah($device['price_per_hour']) ?><span class="text-[14px] text-[#ddd] font-normal">/hr</span></p>
<p class="font-body-md text-[15px] text-[#ddd] mb-6 leading-relaxed"><?= escape(substr($device['specification'] ?? '', 0, 80)) ?>...</p>
<div class="mt-auto">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a class="font-label-md text-label-md text-white border border-[#ff0099] hover:bg-[#ff0099] px-6 py-3 rounded-full transition-all duration-300 block" href="dashboard.php?page=bookings_create&device_id=<?= $device['id'] ?>">Pesan</a>
                                <?php else: ?>
                                    <a class="font-label-md text-label-md text-white border border-[#ff0099] hover:bg-[#ff0099] px-6 py-3 rounded-full transition-all duration-300 block" href="login.php">Pesan</a>
                                <?php endif; ?>
</div>
</div>
</div>
                <?php endforeach; ?>
</div>
</div>
</section>
<!-- Features Section -->
<section class="py-24 bg-black/40 border-y border-white/5 px-margin-mobile md:px-margin-desktop relative" id="features">
<div class="max-w-container-max mx-auto relative z-10">
<div class="text-center mb-16">
<h2 class="text-[40px] mb-4 font-black text-cyan uppercase tracking-wider">Mengapa Memilih GameZone</h2>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
<!-- Feature 1 -->
<div class="card-neon p-10 rounded-[20px] text-center md:col-span-2">
<span class="material-symbols-outlined text-5xl text-[#ff0099] mb-4 block drop-shadow-[0_0_15px_#ff0099]">sports_esports</span>
<h3 class="text-[24px] mb-4 font-bold text-white">Perangkat Premium</h3>
<p class="font-body-md text-[16px] text-[#ddd] max-w-lg mx-auto leading-relaxed">PS5, PC Gaming, VR - semua perangkat keras gaming terbaru dirawat dalam kondisi prima untuk performa maksimal.</p>
</div>
<!-- Feature 2 -->
<div class="card-neon p-10 rounded-[20px] text-center">
<span class="material-symbols-outlined text-5xl text-[#00c8ff] mb-4 block drop-shadow-[0_0_15px_#00c8ff]">event_available</span>
<h3 class="text-[24px] mb-4 font-bold text-white">Pemesanan Mudah</h3>
<p class="font-body-md text-[16px] text-[#ddd] leading-relaxed">Pesan online dalam hitungan menit dengan konfirmasi instan dan akses tanpa hambatan.</p>
</div>
<!-- Feature 3 -->
<div class="card-neon p-10 rounded-[20px] md:col-span-3 text-center border-[#00c8ff] hover:shadow-[0_0_30px_#00c8ff]">
<span class="material-symbols-outlined text-6xl text-[#00c8ff] mb-4 inline-block drop-shadow-[0_0_15px_#00c8ff]">emoji_events</span>
<h3 class="text-[30px] mb-4 font-bold text-white">Turnamen Epik</h3>
<p class="font-body-md text-[18px] text-[#ddd] max-w-3xl mx-auto leading-relaxed">Berkompetisi dalam turnamen reguler dengan hadiah besar. Uji kemampuan Anda melawan pemain terbaik di komunitas dan jadilah legenda di GameZone.</p>
</div>
</div>
</div>
</section>
</main>
<!-- Footer -->
<footer class="w-full py-10 bg-black text-center text-[#777]">
<div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop flex flex-col items-center">
<a class="flex items-center gap-3 mb-6" href="index.php">
<img alt="GameZone Logo" class="h-12 w-auto" src="https://lh3.googleusercontent.com/aida/AP1WRLvv_sna88_gquLeREqKnQFSvdUzzfIekwsd_LuxNIrfBEPpGUuuisjMVhzdji_CTy24nLt3N5u0lBg6X9gPY9YWfBp5gf8vlMeLuOoj0Xk4Gkr543ZUZZ5oWCJ1TGUtER7nMpDSKGMWgVsRSFkixQAkzrmvVgdMrvF7NAikCo-vLBhQkVfq2aQt8BJKzNxYr0JAzCZTAD_kpTiABdvT0B39VCfKtT94SCCKE2bOalCiXuT1-G7SaC6NF4Q"/>
</a>
<p class="font-body-md text-[15px] mb-4 uppercase tracking-widest font-bold text-white/50">PLAY • CONNECT • WIN</p>
<p class="font-code-sm text-[13px]">GAMEZONE © 2026. Hak cipta dilindungi.</p>
</div>
</footer>
</body></html>