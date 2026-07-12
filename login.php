<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

if (isset($_SESSION['user_id'])) {
    if (isAdmin() || isStaff()) { header('Location: admin/'); exit; }
    else { header('Location: dashboard.php'); exit; }
}

require_once __DIR__ . '/inc/functions.php';

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
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, membership_level, points FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                if ($user['role'] === 'customer') {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['membership_level'] = $user['membership_level'];
                    $_SESSION['points'] = $user['points'];
                    addNotification($user['id'], 'Login Successful', 'Welcome back to GameZone!', 'system');
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $errorMessage = 'Admin/staff users must use the /admin/ login page.';
                }
            } else {
                $errorMessage = 'Email atau password salah.';
            }
        } catch (PDOException $e) {
            $errorMessage = 'Database error. Please try again.';
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
<title>Masuk — GameZone</title>
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
        <h1 class="text-4xl font-black tracking-tight bg-gradient-to-r from-[#FF4FD8] to-[#06B6D4] bg-clip-text text-transparent">Masuk</h1>
        <p class="mt-2 text-slate-400">Masuk ke akun GameZone kamu</p>
      </div>

      <?php if ($successMessage): ?>
        <div class="p-4 rounded-xl mb-6 bg-[#22C55E]/15 border border-[#22C55E]/30 text-[#bbf7d0] flex items-center gap-3 text-sm">
          <span>✓</span> <?= htmlspecialchars($successMessage) ?>
        </div>
      <?php endif; ?>

      <?php if ($errorMessage): ?>
        <div class="p-4 rounded-xl mb-6 bg-[#FF3B6B]/15 border border-[#FF3B6B]/30 text-[#fecaca] flex items-center gap-3 text-sm">
          <span>✕</span> <?= htmlspecialchars($errorMessage) ?>
        </div>
      <?php endif; ?>

      <form action="login.php" method="post" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <div>
          <label for="email" class="block mb-2 text-sm font-semibold text-slate-300">Email</label>
          <input type="email" id="email" name="email" placeholder="your@email.com" required class="input-neon" style="background:#0f172a !important;color:#f1f5f9 !important;-webkit-text-fill-color:#f1f5f9 !important;" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
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

      <div class="mt-8 text-center">
        <p class="text-sm text-slate-400">
          Belum punya akun?
          <a href="register.php" class="text-[#06B6D4] hover:text-[#FF4FD8] transition-colors font-semibold">Daftar di sini</a>
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
