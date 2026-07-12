<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/auth.php';

if (isset($_SESSION['user_id'])) {
    if (isAdmin() || isStaff()) { header('Location: ../dashboard.php'); exit; }
    else { destroySession(); header('Location: index.php'); exit; }
}

require_once __DIR__ . '/../inc/functions.php';

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Permintaan tidak valid. Coba lagi.';
    } else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) {
        $errorMessage = 'Masukkan email dan kata sandi.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, membership_level, points FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && verifyPassword($password, $user['password'])) {
                if ($user['role'] === 'admin' || $user['role'] === 'staff') {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['membership_level'] = $user['membership_level'];
                    $_SESSION['points'] = $user['points'];
                    addNotification($user['id'], 'Admin Login Successful', 'You have logged into GameZone admin.', 'system');
                    header('Location: ../dashboard.php');
                    exit;
                } else {
                    $errorMessage = 'Akses ditolak. Hanya admin atau staf yang dapat login di sini.';
                }
            } else {
                $errorMessage = 'Email atau password salah.';
            }
        } catch (PDOException $e) {
            error_log('Admin login error: ' . $e->getMessage());
            $errorMessage = 'Terjadi kesalahan. Coba lagi.';
        }
    }
    }
}
$flashMessage = getFlashMessage();
if ($flashMessage && $flashMessage['type'] === 'success') {
    $successMessage = $flashMessage['text'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Login — GameZone</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800;900&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/gamezone.css">
</head>
<body class="antialiased min-h-screen flex flex-col bg-grid selection:bg-[#FF4FD8]/30 selection:text-white">

<div class="floating-particle size-2 left-[8%] top-[18%] bg-[#FF4FD8] shadow-[0_0_22px_#FF4FD8]"></div>
<div class="floating-particle size-1.5 right-[16%] top-[32%] bg-[#06B6D4] shadow-[0_0_20px_#06B6D4]" style="animation-delay:2s;animation-direction:reverse"></div>

<nav class="w-full fixed top-4 z-50 flex justify-center px-4">
  <div class="w-full max-w-5xl flex items-center justify-between rounded-full border border-white/15 bg-white/[0.08] px-6 py-3 backdrop-blur-2xl shadow-[0_18px_80px_rgba(0,0,0,0.55)]">
    <a href="../index.php" class="flex items-center gap-3">
      <span class="grid size-10 place-items-center rounded-2xl neon-grad shadow-[0_0_30px_rgba(255,79,216,0.25)]">
        <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </span>
      <span class="text-xl font-black">GameZone Admin</span>
    </a>
    <a href="../index.php" class="btn-neon btn-neon-ghost text-sm px-5 py-2">Beranda</a>
  </div>
</nav>

<main class="flex-grow flex items-center justify-center min-h-screen px-4 py-28">
  <div class="w-full max-w-md animate-fadeUp">
    <div class="glass-card p-8 md:p-10">
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 bg-[#FF4FD8]/15 text-[#FFD7F6] font-mono text-xs mb-4 border border-[#FF4FD8]/20">🔐 Staff Only</div>
        <h1 class="text-4xl font-black tracking-tight bg-gradient-to-r from-[#FF4FD8] to-[#06B6D4] bg-clip-text text-transparent">Admin Login</h1>
        <p class="mt-2 text-slate-400">Masuk sebagai admin atau staf</p>
      </div>

      <?php if ($successMessage): ?>
        <div class="p-4 rounded-xl mb-6 bg-[#22C55E]/15 border border-[#22C55E]/30 text-[#bbf7d0] flex items-center gap-3 text-sm"><span>✓</span> <?= htmlspecialchars($successMessage) ?></div>
      <?php endif; ?>
      <?php if ($errorMessage): ?>
        <div class="p-4 rounded-xl mb-6 bg-[#FF3B6B]/15 border border-[#FF3B6B]/30 text-[#fecaca] flex items-center gap-3 text-sm"><span>✕</span> <?= htmlspecialchars($errorMessage) ?></div>
      <?php endif; ?>

      <form action="index.php" method="post" class="space-y-5">
        <?= csrfField() ?>
        <div>
          <label for="email" class="block mb-2 text-sm font-semibold text-slate-300">Email</label>
          <input type="email" id="email" name="email" placeholder="admin@gamezone.com" required class="input-neon" style="background:#0f172a !important;color:#f1f5f9 !important;-webkit-text-fill-color:#f1f5f9 !important;" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        </div>
        <div>
          <label for="password" class="block mb-2 text-sm font-semibold text-slate-300">Kata Sandi</label>
          <div class="relative">
            <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" required class="input-neon w-full pr-12" style="background:#0f172a !important;color:#f1f5f9 !important;-webkit-text-fill-color:#f1f5f9 !important;">
            <button type="button" onclick="togglePass('password',this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <input type="checkbox" name="remember" id="remember" class="size-4 accent-[#FF4FD8]">
          <label for="remember" class="text-sm text-slate-400 cursor-pointer">Ingat Saya</label>
        </div>
        <button type="submit" class="btn-neon btn-neon-primary w-full py-3.5 text-base">Masuk</button>
      </form>
    </div>
  </div>
</main>
</body>
<script>
function togglePass(id,btn){const i=document.getElementById(id);const isPass=i.type==='password';i.type=isPass?'text':'password';btn.innerHTML=isPass?'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>':'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';}
</script>
</html>
