<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/inc/functions.php';

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $errorMessage = 'Please enter both email and password.';
    } else {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT id, name, email, password, role, membership_level, points 
                FROM users 
                WHERE email = ? 
                LIMIT 1
            ");
            
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['membership_level'] = $user['membership_level'];
                $_SESSION['points'] = $user['points'];
                
                addNotification(
                    $user['id'],
                    'Login Successful',
                    'You have successfully logged into your GameZone account.',
                    'system'
                );
                
                header('Location: dashboard.php');
                exit;
            } else {
                $errorMessage = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $errorMessage = 'Database error. Please try again.';
        }
    }
}

$flashMessage = getFlashMessage();
if ($flashMessage) {
    if ($flashMessage['type'] === 'success') {
        $successMessage = $flashMessage['text'];
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Masuk - GameZone</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800;900&amp;family=Inter:wght@400;500;600&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
  tailwind.config = {
    darkMode: "class",
    theme: { extend: { "colors": { "on-surface": "#dae2fd","background": "#05000a","primary": "#ff0099","secondary": "#00c8ff","on-surface-variant": "#ccc3d8","error": "#ffb4ab","tertiary": "#4ae176","primary-container": "#7c3aed","surface-container": "#171f33","surface-container-lowest": "#060e20","inverse-primary": "#732ee4","outline": "#958da1","on-primary-container": "#ede0ff","on-error-container": "#ffdad6","error-container": "#93000a" }, "borderRadius": { "DEFAULT": "0.25rem","lg": "0.5rem","xl": "0.75rem","full": "9999px" }, "fontFamily": { "headline-sm": ["Hanken Grotesk"],"display-lg-mobile": ["Hanken Grotesk"],"code-sm": ["JetBrains Mono"],"body-lg": ["Inter"],"body-md": ["Inter"],"headline-md": ["Hanken Grotesk"],"display-lg": ["Hanken Grotesk"],"label-md": ["JetBrains Mono"] }, "fontSize": { "headline-sm": ["24px",{"lineHeight": "1.3","fontWeight": "600"}],"display-lg-mobile": ["36px",{"lineHeight": "1.2","letterSpacing": "-0.02em","fontWeight": "800"}],"code-sm": ["13px",{"lineHeight": "1.5","fontWeight": "400"}],"body-lg": ["18px",{"lineHeight": "1.6","fontWeight": "400"}],"body-md": ["16px",{"lineHeight": "1.5","fontWeight": "400"}],"headline-md": ["32px",{"lineHeight": "1.2","fontWeight": "700"}],"display-lg": ["56px",{"lineHeight": "1.1","letterSpacing": "-0.02em","fontWeight": "800"}],"label-md": ["14px",{"lineHeight": "1.4","fontWeight": "500"}] } } } }
</script>
<style>
        body { background: #05000a; color: white; overflow-x: hidden; }
        .logo-text { color: #ff00aa; text-shadow: 0 0 20px #ff00aa; }
        .btn-neon { background: linear-gradient(90deg, #ff0099, #006eff); box-shadow: 0 0 25px #ff0099; color: white; }
        .card-neon { background: rgba(255,255,255,0.05); border: 1px solid #ff0099; transition: 0.3s; }
        input { background: rgba(255,255,255,0.08) !important; border: 1px solid #ff009966 !important; color: white !important; border-radius: 12px !important; padding: 12px 16px !important; transition: all 0.3s !important; }
        input:focus { border-color: #ff0099 !important; box-shadow: 0 0 0 3px #ff009944 !important; outline: none !important; }
        label { color: #ccc3d8; font-size: 14px; font-weight: 500; }
        input[type="checkbox"] { appearance: none; -webkit-appearance: none; width: 20px; height: 20px; border: 2px solid #00c8ff; border-radius: 4px; background: rgba(255,255,255,0.08); cursor: pointer; position: relative; flex-shrink: 0; padding: 0 !important; }
        input[type="checkbox"]:checked { background: #ff0099; border-color: #ff0099; }
        input[type="checkbox"]:checked::after { content: ''; position: absolute; left: 6px; top: 2px; width: 6px; height: 11px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); }
    </style>
</head>
<body class="bg-[#05000a] text-white font-body-md antialiased min-h-screen selection:bg-[#ff0099] selection:text-white">
<nav class="w-full fixed top-0 z-50 bg-black/60 backdrop-blur-[10px]">
<div class="flex justify-between items-center max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop h-20">
<a class="flex items-center gap-3 hover:scale-105 active:scale-95 transition-transform" href="index.php">
<span class="font-display-lg text-[28px] font-black logo-text uppercase">GameZone</span>
</a>
<div class="hidden md:flex items-center space-x-6">
<a class="font-label-md text-label-md text-white hover:text-[#00c8ff] transition-all px-4 py-2" href="index.php">Beranda</a>
<a class="font-label-md text-label-md btn-neon px-8 py-3 rounded-full hover:scale-105 active:scale-95 transition-all duration-300 font-bold tracking-wide" href="register.php">Daftar</a>
</div>
</div>
</nav>

<main class="flex-grow pt-20 flex items-center min-h-screen">
<div class="relative z-10 w-full max-w-md mx-auto px-margin-mobile md:px-margin-desktop py-16">
<div class="card-neon rounded-[20px] p-10">
<div class="text-center mb-8">
<h1 class="font-display-lg text-4xl font-black hero-title uppercase mb-2" style="background:linear-gradient(90deg,#ff0099,#00c8ff);-webkit-background-clip:text;color:transparent;">Masuk</h1>
<p class="font-body-md text-[#ddd]">Masuk ke akun GameZone Anda</p>
</div>

                <?php if ($successMessage): ?>
                    <div class="p-4 rounded-xl mb-6 bg-green-900/40 border border-[#4ae176] text-[#4ae176] flex items-center gap-3">
                        <span class="material-symbols-outlined">check_circle</span>
                        <span class="font-body-md text-[14px]"><?= htmlspecialchars($successMessage) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($errorMessage): ?>
                    <div class="p-4 rounded-xl mb-6 bg-red-900/40 border border-[#ffb4ab] text-[#ffb4ab] flex items-center gap-3">
                        <span class="material-symbols-outlined">error</span>
                        <span class="font-body-md text-[14px]"><?= htmlspecialchars($errorMessage) ?></span>
                    </div>
                <?php endif; ?>

<form action="login.php" method="post" class="space-y-6">
<div>
<label for="email" class="block mb-2 font-label-md">Email</label>
<input type="email" id="email" name="email" placeholder="your@email.com" required class="w-full" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
</div>
<div>
<label for="password" class="block mb-2 font-label-md">Kata Sandi</label>
<input type="password" id="password" name="password" placeholder="Masukkan kata sandi" required class="w-full">
</div>
<div class="flex items-center gap-2">
<input type="checkbox" name="remember" id="remember">
<label for="remember" class="cursor-pointer text-sm text-[#ddd]">Ingat Saya</label>
</div>
<button type="submit" class="w-full font-label-md btn-neon px-8 py-4 rounded-full hover:scale-[1.02] active:scale-95 transition-all duration-300 font-bold tracking-wide">
                        Masuk
                    </button>
</form>

<div class="mt-8 text-center">
<p class="font-body-md text-[#ddd]">
                        Belum punya akun?
<a href="register.php" class="text-[#00c8ff] hover:text-[#ff0099] transition-colors font-semibold">Daftar di sini</a>
</p>
</div>

<div class="mt-8 pt-6 border-t border-white/10">
<p class="font-code-sm text-xs text-white/40 text-center mb-4">Akun Demo:</p>
<div class="space-y-2 text-sm">
<div class="flex items-center justify-between card-neon rounded-lg px-4 py-3">
<span class="font-code-sm text-white">Admin</span>
<span class="font-code-sm text-white/50">admin@gamezone.com / admin123</span>
<span class="bg-[#ff0099]/30 text-[#ff0099] text-xs px-3 py-1 rounded-full font-code-sm">Full Access</span>
</div>
<div class="flex items-center justify-between card-neon rounded-lg px-4 py-3">
<span class="font-code-sm text-white">Customer</span>
<span class="font-code-sm text-white/50">customer@email.com / customer123</span>
<span class="bg-[#4ae176]/30 text-[#4ae176] text-xs px-3 py-1 rounded-full font-code-sm">Book Devices</span>
</div>
<div class="flex items-center justify-between card-neon rounded-lg px-4 py-3">
<span class="font-code-sm text-white">Staff</span>
<span class="font-code-sm text-white/50">staff@gamezone.com / staff123</span>
<span class="bg-[#00c8ff]/30 text-[#00c8ff] text-xs px-3 py-1 rounded-full font-code-sm">Manage</span>
</div>
</div>
</div>
</div>
</div>
</main>
</body>
</html>