<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Permintaan tidak valid. Coba lagi.';
    } else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $errorMessage = 'Nama, email, dan kata sandi wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $errorMessage = 'Kata sandi minimal 6 karakter.';
    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        try {
            global $pdo;
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errorMessage = 'Email sudah terdaftar.';
            } else {
                $hashedPassword = generatePasswordHash($password);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, membership_level, points) VALUES (?, ?, ?, ?, 'customer', 'bronze', 0)");
                if ($stmt->execute([$name, $email, $phone ?: null, $hashedPassword])) {
                    $successMessage = 'Pendaftaran berhasil! Silakan login.';
                } else {
                    $errorMessage = 'Pendaftaran gagal. Coba lagi.';
                }
            }
        } catch (PDOException $e) {
            $errorMessage = 'Terjadi kesalahan. Coba lagi.';
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Daftar — GameZone</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800;900&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="assets/css/gamezone.css">
<style>
  body { background: var(--bg-deep); color: var(--text-primary); font-family: var(--font-sans); overflow-x: hidden; }
  .floating-particle { position: fixed; border-radius: 50%; pointer-events: none; z-index: 0; animation: float 7s ease-in-out infinite; }
  .bg-grid { background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 32px 32px; }
</style>
</head>
<body class="antialiased min-h-screen flex flex-col bg-grid selection:bg-[#FF4FD8]/30 selection:text-white">

<div class="floating-particle size-2 left-[8%] top-[18%] bg-[#FF4FD8] shadow-[0_0_22px_#FF4FD8]"></div>
<div class="floating-particle size-1.5 right-[16%] top-[32%] bg-[#06B6D4] shadow-[0_0_20px_#06B6D4]" style="animation-delay:2s;animation-direction:reverse"></div>

<?php require_once __DIR__ . '/templates/header.php'; ?>

<main class="flex-grow flex items-center justify-center min-h-screen px-4 py-28">
  <div class="w-full max-w-md animate-fadeUp">
    <div class="glass-card p-8 md:p-10">
      <div class="text-center mb-8">
        <h1 class="text-4xl font-black tracking-tight bg-gradient-to-r from-[#FF4FD8] to-[#06B6D4] bg-clip-text text-transparent">Daftar</h1>
        <p class="mt-2 text-slate-400">Buat akun GameZone baru</p>
      </div>

      <?php if ($errorMessage): ?>
        <div class="p-4 rounded-xl mb-6 bg-[#FF3B6B]/15 border border-[#FF3B6B]/30 text-[#fecaca] flex items-center gap-3 text-sm">
          <span>✕</span> <?= htmlspecialchars($errorMessage) ?>
        </div>
      <?php endif; ?>

      <?php if ($successMessage): ?>
        <div class="p-4 rounded-xl mb-6 bg-[#22C55E]/15 border border-[#22C55E]/30 text-[#bbf7d0] flex items-center gap-3 text-sm">
          <span>✓</span> <?= htmlspecialchars($successMessage) ?>
        </div>
      <?php endif; ?>

      <form action="register.php" method="post" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <div>
          <label for="name" class="block mb-2 text-sm font-semibold text-slate-300">Nama Lengkap</label>
          <input type="text" id="name" name="name" placeholder="Nama kamu" required class="input-neon" style="background:#0f172a !important;color:#f1f5f9 !important;-webkit-text-fill-color:#f1f5f9 !important;" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
        </div>
        <div>
          <label for="email" class="block mb-2 text-sm font-semibold text-slate-300">Email</label>
          <input type="email" id="email" name="email" placeholder="your@email.com" required class="input-neon" style="background:#0f172a !important;color:#f1f5f9 !important;-webkit-text-fill-color:#f1f5f9 !important;" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        </div>
        <div>
          <label for="phone" class="block mb-2 text-sm font-semibold text-slate-300">No. Telepon <span class="text-slate-500 font-normal">(opsional)</span></label>
          <input type="text" id="phone" name="phone" placeholder="089xxx" class="input-neon" style="background:#0f172a !important;color:#f1f5f9 !important;-webkit-text-fill-color:#f1f5f9 !important;" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
        </div>
        <div>
          <label for="password" class="block mb-2 text-sm font-semibold text-slate-300">Kata Sandi</label>
          <div class="relative">
            <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required class="input-neon w-full pr-12" style="background:#0f172a !important;color:#f1f5f9 !important;-webkit-text-fill-color:#f1f5f9 !important;">
            <button type="button" onclick="togglePass('password',this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button>
          </div>
        </div>
        <div>
          <label for="confirm_password" class="block mb-2 text-sm font-semibold text-slate-300">Konfirmasi Kata Sandi</label>
          <div class="relative">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi kata sandi" required class="input-neon w-full pr-12" style="background:#0f172a !important;color:#f1f5f9 !important;-webkit-text-fill-color:#f1f5f9 !important;">
            <button type="button" onclick="togglePass('confirm_password',this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <input type="checkbox" id="terms" required class="size-4 accent-[#FF4FD8]">
          <label for="terms" class="text-sm text-slate-400 cursor-pointer">Saya setuju dengan <a href="#" class="text-[#06B6D4] hover:text-[#FF4FD8] transition-colors">syarat & ketentuan</a></label>
        </div>
        <button type="submit" class="btn-neon btn-neon-primary w-full py-3.5 text-base">Daftar</button>
      </form>

      <div class="mt-8 text-center">
        <p class="text-sm text-slate-400">
          Sudah punya akun?
          <a href="login.php" class="text-[#06B6D4] hover:text-[#FF4FD8] transition-colors font-semibold">Masuk di sini</a>
        </p>
      </div>
    </div>
  </div>
</main>
<script>
function togglePass(id,btn){const i=document.getElementById(id);const isPass=i.type==='password';i.type=isPass?'text':'password';btn.innerHTML=isPass?'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>':'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';}
</script>
</body>
</html>
