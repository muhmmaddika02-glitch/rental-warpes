<?php
if (!defined('GAMEZONE_ACCESS') && basename($_SERVER['SCRIPT_NAME'] ?? '') === 'header.php') {
    http_response_code(403);
    exit;
}
$isAuthed = !empty($_SESSION['user_id']);
$navItems = $navItems ?? [];
?>
<nav class="w-full fixed top-4 z-50 flex justify-center px-4">
  <div class="w-full max-w-5xl flex items-center justify-between rounded-full border border-white/15 bg-white/[0.08] px-6 py-3 backdrop-blur-2xl shadow-[0_18px_80px_rgba(0,0,0,0.55)]">
    <a href="index.php" class="flex items-center gap-3 shrink-0">
      <span class="grid size-10 place-items-center rounded-2xl neon-grad shadow-[0_0_30px_rgba(255,79,216,0.25)]">
        <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </span>
      <span class="text-xl font-black tracking-tight">GameZone</span>
    </a>
    <?php if ($navItems): ?>
    <div class="hidden md:flex items-center gap-10 text-[15px] font-bold text-slate-200">
      <?php foreach ($navItems as $label => $href): ?>
        <a href="<?= htmlspecialchars($href) ?>" class="hover:text-white transition"><?= htmlspecialchars($label) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="flex items-center gap-3">
      <a href="index.php" class="btn-neon btn-neon-ghost text-sm px-5 py-2">Beranda</a>
      <?php if ($isAuthed): ?>
        <a href="dashboard.php" class="btn-neon btn-neon-primary text-sm px-5 py-2">Dashboard</a>
      <?php else: ?>
        <a href="login.php" class="btn-neon btn-neon-ghost text-sm px-5 py-2">Masuk</a>
        <a href="register.php" class="btn-neon btn-neon-primary text-sm px-5 py-2">Daftar</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
