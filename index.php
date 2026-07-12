<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

global $pdo;
$statDevices = 0;
$statCustomers = 0;
$statBookings = 0;
$devices = [];
$tournaments = [];
$promotions = [];

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM devices WHERE status = 'available'");
    $statDevices = $stmt->fetch()['total'];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    $statCustomers = $stmt->fetch()['total'];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $statBookings = $stmt->fetch()['total'];
    $devices = $pdo->query("SELECT * FROM devices WHERE status != 'maintenance' ORDER BY type, name LIMIT 6")->fetchAll();
    $tournaments = $pdo->query("SELECT * FROM tournaments WHERE status IN ('upcoming','registration_open') ORDER BY start_date ASC LIMIT 3")->fetchAll();
    $promotions = $pdo->query("SELECT * FROM promotions WHERE is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY created_at DESC LIMIT 3")->fetchAll();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>GameZone — Pengalaman Gaming Terbaik</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Black+Ops+One&family=Hanken+Grotesk:wght@600;700;800;900&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="assets/css/gamezone.css">
<style>
  body { background: var(--bg-deep); color: var(--text-primary); font-family: var(--font-sans); overflow-x: hidden; }
  .floating-particle {
    position: fixed; border-radius: 50%; pointer-events: none; z-index: 0;
    animation: float 7s ease-in-out infinite;
  }
  .hero-glow { text-shadow: 0 0 40px rgba(255,79,216,0.3), 0 0 120px rgba(6,182,212,0.15); }
  .bg-grid {
    background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 32px 32px;
  }
</style>
</head>
<body class="antialiased min-h-screen flex flex-col bg-grid selection:bg-[#FF4FD8]/30 selection:text-white">

<!-- Floating Particles -->
<div class="floating-particle size-2 left-[8%] top-[18%] bg-[#FF4FD8] shadow-[0_0_22px_#FF4FD8]" style="animation-delay:0s"></div>
<div class="floating-particle size-1.5 right-[16%] top-[32%] bg-[#06B6D4] shadow-[0_0_20px_#06B6D4]" style="animation-delay:2s;animation-direction:reverse"></div>
<div class="floating-particle size-1 left-[42%] bottom-[18%] bg-[#FF7B00] shadow-[0_0_18px_#FF7B00]" style="animation-delay:4s"></div>

<?php
$navItems = ['Home' => 'index.php', 'Rooms' => '#devices', 'Tournaments' => '#features'];
require_once __DIR__ . '/templates/header.php';
?>

<main class="flex-grow">
  <!-- Hero -->
  <section class="relative min-h-screen flex items-center overflow-hidden py-28 px-4">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_10%,rgba(255,79,216,0.35),transparent_40%),radial-gradient(circle_at_80%_20%,rgba(6,182,212,0.25),transparent_35%),linear-gradient(180deg,rgba(15,23,42,0.5),var(--bg-deep))]"></div>
    <div class="relative z-10 w-full max-w-5xl mx-auto grid items-center gap-10 lg:grid-cols-[1.05fr_0.95fr] animate-fadeUp">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 bg-[#FF4FD8]/15 text-[#FFD7F6] font-mono text-xs mb-6 border border-[#FF4FD8]/20">Vice Nights Booking Club</div>
        <h1 class="text-5xl md:text-7xl lg:text-8xl font-black leading-[0.86] tracking-[-0.015em]" style="font-family:'Black Ops One',Inter,sans-serif">Reserve the room.<br/>Own the night.</h1>
        <p class="mt-6 max-w-xl text-lg leading-8 text-slate-200/90">
          Premium interaktif buat booking PS5, PC rigs, VR, racing sims, turnamen, sampai kontrol admin — semua dalam satu malam epik.
        </p>
        <div class="mt-8 flex flex-wrap items-center gap-2">
          <a href="register.php" class="btn-neon btn-neon-primary text-sm px-5 py-2.5">Book Now →</a>
          <a href="#devices" class="btn-neon btn-neon-ghost text-sm px-5 py-2.5">Explore Rooms</a>
          <a href="login.php" class="btn-neon btn-neon-ghost text-sm px-4 py-2.5">Login</a>
          <a href="register.php" class="btn-neon btn-neon-ghost text-sm px-4 py-2.5">Register</a>
        </div>
      </div>
      <div class="glass-card relative overflow-hidden p-3">
        <img src="assets/img/hero.png" alt="GameZone" class="w-full rounded-[1.5rem] object-cover object-top" style="min-height:420px" />
      </div>
    </div>
  </section>

  <!-- Tournament & Promo Notifications -->
  <?php if (!empty($tournaments) || !empty($promotions)): ?>
  <section class="py-6 px-4 -mt-12 relative z-10">
    <div class="max-w-5xl mx-auto">
      <div class="flex gap-4 overflow-x-auto pb-2">
        <?php foreach ($tournaments as $t): ?>
        <a href="login.php" class="glass-card glass-card-interactive flex items-center gap-4 p-4 shrink-0 min-w-[280px]">
          <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-[#FF7B00]/20 text-[#FF7B00] text-xl">🏆</span>
          <div class="min-w-0">
            <p class="text-sm font-black text-white truncate"><?= escape($t['title']) ?></p>
            <p class="text-xs text-slate-400 mt-0.5"><?= escape($t['game_name']) ?> • <?= formatDate($t['start_date'], 'd M') ?></p>
            <span class="inline-block mt-1 badge-neon badge-orange text-[10px]"><?= $t['status'] === 'registration_open' ? 'Open' : 'Upcoming' ?></span>
          </div>
        </a>
        <?php endforeach; ?>
        <?php foreach ($promotions as $p): ?>
        <a href="login.php" class="glass-card glass-card-interactive flex items-center gap-4 p-4 shrink-0 min-w-[280px]">
          <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-[#FF4FD8]/20 text-[#FF4FD8] text-xl">🎁</span>
          <div class="min-w-0">
            <p class="text-sm font-black text-white truncate"><?= escape($p['title']) ?></p>
            <p class="text-xs text-slate-400 mt-0.5"><?= $p['discount_percentage'] ? $p['discount_percentage'] . '% OFF' : '' ?><?= $p['discount_amount'] ? 'Rp ' . number_format($p['discount_amount'],0,',','.') : '' ?> • <?= escape($p['code']) ?></p>
            <span class="inline-block mt-1 badge-neon badge-pink text-[10px]">Promo</span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Stats -->
  <section class="py-16 border-y border-white/10 bg-black/30">
    <div class="max-w-5xl mx-auto px-4">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
        <div class="glass-card glass-card-interactive p-6">
          <p class="text-sm text-slate-300">Active bookings</p>
          <b class="mt-2 block text-3xl md:text-4xl stat-neon">1,284</b>
          <span class="font-mono text-xs text-[#06B6D4]">+28%</span>
        </div>
        <div class="glass-card glass-card-interactive p-6">
          <p class="text-sm text-slate-300">Elite members</p>
          <b class="mt-2 block text-3xl md:text-4xl stat-neon">642</b>
          <span class="font-mono text-xs text-[#06B6D4]">+41%</span>
        </div>
        <div class="glass-card glass-card-interactive p-6">
          <p class="text-sm text-slate-300">Arena uptime</p>
          <b class="mt-2 block text-3xl md:text-4xl stat-neon">99.9%</b>
          <span class="font-mono text-xs text-[#06B6D4]">Live</span>
        </div>
        <div class="glass-card glass-card-interactive p-6">
          <p class="text-sm text-slate-300">Prize pool</p>
          <b class="mt-2 block text-3xl md:text-4xl stat-neon">Rp760Jt</b>
          <span class="font-mono text-xs text-[#06B6D4]">Season VI</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Devices -->
  <section class="py-24 px-4 relative" id="devices">
    <div class="max-w-5xl mx-auto">
      <div class="text-center mb-14">
        <h2 class="text-4xl md:text-5xl font-black tracking-tight"><span class="bg-gradient-to-r from-[#FF4FD8] to-[#06B6D4] bg-clip-text text-transparent">Perangkat Gaming Kami</span></h2>
        <p class="mt-4 text-lg text-slate-300 max-w-xl mx-auto">Pilih dari setup gaming premium untuk sesi kamu berikutnya.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($devices as $device): ?>
        <div class="card-neon-device flex flex-col animate-fadeUp">
          <div class="relative h-52 bg-black/60 flex items-center justify-center overflow-hidden">
            <?php if (!empty($device['image']) && file_exists(__DIR__ . '/assets/uploads/' . $device['image'])): ?>
              <img alt="<?= escape($device['name']) ?>" class="w-full h-full object-cover opacity-80" src="assets/uploads/<?= htmlspecialchars($device['image']) ?>"/>
            <?php else: ?>
              <svg class="size-16 text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?php endif; ?>
          </div>
          <div class="p-6 flex flex-col flex-grow text-center">
            <h3 class="text-xl font-bold text-[#FF4FD8]"><?= escape($device['name']) ?></h3>
            <p class="text-2xl font-black text-[#06B6D4] mt-2"><?= formatRupiah($device['price_per_hour']) ?><span class="text-sm text-slate-400 font-normal">/jam</span></p>
            <p class="text-sm text-slate-400 mt-2 leading-relaxed"><?= escape(substr($device['specification'] ?? '', 0, 80)) ?>...</p>
            <div class="mt-auto pt-4">
              <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php?page=bookings_create&device_id=<?= $device['id'] ?>" class="btn-neon btn-neon-primary text-sm w-full">Pesan Sekarang</a>
              <?php else: ?>
                <a href="login.php" class="btn-neon btn-neon-ghost text-sm w-full">Pesan Sekarang</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section class="py-24 px-4 relative" id="features">
    <div class="max-w-5xl mx-auto">
      <div class="text-center mb-14">
        <h2 class="text-4xl md:text-5xl font-black tracking-tight"><span class="bg-gradient-to-r from-[#06B6D4] to-[#7C3AED] bg-clip-text text-transparent">Mengapa GameZone</span></h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="glass-card glass-card-interactive p-8 text-center md:col-span-2">
          <span class="inline-block text-4xl mb-4 text-[#FF4FD8] drop-shadow-[0_0_15px_rgba(255,79,216,0.5)]">🎮</span>
          <h3 class="text-2xl font-black mb-3">Perangkat Premium</h3>
          <p class="text-slate-300 leading-relaxed">PS5, PC Gaming, VR — semua perangkat keras gaming terbaru dirawat dalam kondisi prima untuk performa maksimal.</p>
        </div>
        <div class="glass-card glass-card-interactive p-8 text-center">
          <span class="inline-block text-4xl mb-4 text-[#06B6D4] drop-shadow-[0_0_15px_rgba(6,182,212,0.5)]">📅</span>
          <h3 class="text-2xl font-black mb-3">Pemesanan Mudah</h3>
          <p class="text-slate-300 leading-relaxed">Pesan online dalam hitungan menit dengan konfirmasi instan dan akses tanpa hambatan.</p>
        </div>
        <div class="glass-card glass-card-interactive p-8 text-center lg:col-span-3 border-[#06B6D4]/40">
          <span class="inline-block text-5xl mb-4 text-[#06B6D4] drop-shadow-[0_0_15px_rgba(6,182,212,0.5)]">🏆</span>
          <h3 class="text-3xl font-black mb-3">Turnamen Epik</h3>
          <p class="text-slate-300 max-w-2xl mx-auto leading-relaxed">Berkompetisi dalam turnamen reguler dengan hadiah besar. Uji kemampuan kamu melawan pemain terbaik di komunitas dan jadilah legenda di GameZone.</p>
        </div>
      </div>
    </div>
  </section>
</main>

<!-- Footer -->
<footer class="border-t border-white/10 py-12">
  <div class="max-w-5xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-6">
    <a href="index.php" class="flex items-center gap-3">
      <span class="grid size-10 place-items-center rounded-2xl neon-grad shadow-[0_0_30px_rgba(255,79,216,0.3)]">
        <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </span>
      <span class="text-lg font-black">GameZone</span>
    </a>
    <p class="font-mono text-xs text-slate-500">© 2026 GameZone. Hak cipta dilindungi.</p>
  </div>
</footer>
</body>
</html>
