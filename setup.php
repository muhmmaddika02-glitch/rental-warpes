<?php
declare(strict_types=1);

$db_host = 'localhost';
$db_name = 'db_gamezone';
$db_user = 'root';
$db_pass = '';

$statusMessages = [];
$error = false;

try {
    $conn = new mysqli($db_host, $db_user, $db_pass);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $createDbQuery = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    if ($conn->query($createDbQuery)) {
        $statusMessages[] = ['type' => 'success', 'text' => "Database `$db_name` created successfully or already exists."];
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }

    $conn->select_db($db_name);

    $createUsersTable = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `phone` VARCHAR(20) DEFAULT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('customer', 'staff', 'admin') DEFAULT 'customer',
        `profile_picture` VARCHAR(255) DEFAULT NULL,
        `membership_level` ENUM('bronze', 'silver', 'gold') DEFAULT 'bronze',
        `points` INT DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if ($conn->query($createUsersTable)) { $statusMessages[] = ['type' => 'success', 'text' => 'Table `users` created successfully.']; } else { throw new Exception("Error creating users table: " . $conn->error); }

    $createDevicesTable = "CREATE TABLE IF NOT EXISTS `devices` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `type` VARCHAR(100) NOT NULL,
        `price_per_hour` DECIMAL(10, 2) NOT NULL,
        `status` ENUM('available', 'booked', 'playing', 'maintenance') DEFAULT 'available',
        `image` VARCHAR(255) DEFAULT NULL,
        `specification` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if ($conn->query($createDevicesTable)) { $statusMessages[] = ['type' => 'success', 'text' => 'Table `devices` created successfully.']; } else { throw new Exception("Error creating devices table: " . $conn->error); }

    $createBookingsTable = "CREATE TABLE IF NOT EXISTS `bookings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `device_id` INT NOT NULL,
        `booking_date` DATE NOT NULL,
        `start_time` TIME NOT NULL,
        `duration_hours` INT NOT NULL,
        `total_price` DECIMAL(10, 2) NOT NULL,
        `booking_status` ENUM('pending', 'confirmed', 'completed', 'cancelled', 'playing') DEFAULT 'pending',
        `qr_code_path` VARCHAR(255) DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if ($conn->query($createBookingsTable)) { $statusMessages[] = ['type' => 'success', 'text' => 'Table `bookings` created successfully.']; } else { throw new Exception("Error creating bookings table: " . $conn->error); }

    $createPaymentsTable = "CREATE TABLE IF NOT EXISTS `payments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `booking_id` INT NOT NULL,
        `payment_method` ENUM('qris', 'bank_transfer', 'e_wallet') NOT NULL,
        `amount` DECIMAL(10, 2) NOT NULL,
        `payment_status` ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending',
        `transaction_id` VARCHAR(255) DEFAULT NULL,
        `paid_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if ($conn->query($createPaymentsTable)) { $statusMessages[] = ['type' => 'success', 'text' => 'Table `payments` created successfully.']; } else { throw new Exception("Error creating payments table: " . $conn->error); }

    $createReviewsTable = "CREATE TABLE IF NOT EXISTS `reviews` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `device_id` INT NOT NULL,
        `booking_id` INT DEFAULT NULL,
        `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
        `comment` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if ($conn->query($createReviewsTable)) { $statusMessages[] = ['type' => 'success', 'text' => 'Table `reviews` created successfully.']; } else { throw new Exception("Error creating reviews table: " . $conn->error); }

    $createTournamentsTable = "CREATE TABLE IF NOT EXISTS `tournaments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `game_name` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `prize_pool` DECIMAL(10, 2) DEFAULT 0.00,
        `registration_fee` DECIMAL(10, 2) DEFAULT 0.00,
        `start_date` DATETIME NOT NULL,
        `end_date` DATETIME NOT NULL,
        `max_participants` INT DEFAULT NULL,
        `status` ENUM('upcoming', 'registration_open', 'in_progress', 'completed', 'cancelled') DEFAULT 'upcoming',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if ($conn->query($createTournamentsTable)) { $statusMessages[] = ['type' => 'success', 'text' => 'Table `tournaments` created successfully.']; } else { throw new Exception("Error creating tournaments table: " . $conn->error); }

    $createParticipantsTable = "CREATE TABLE IF NOT EXISTS `participants` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `tournament_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `registration_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `status` ENUM('registered', 'checked_in', 'eliminated', 'winner') DEFAULT 'registered',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        UNIQUE (`tournament_id`, `user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if ($conn->query($createParticipantsTable)) { $statusMessages[] = ['type' => 'success', 'text' => 'Table `participants` created successfully.']; } else { throw new Exception("Error creating participants table: " . $conn->error); }

    $createNotificationsTable = "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `is_read` BOOLEAN DEFAULT FALSE,
        `type` ENUM('booking_confirmation', 'payment_confirmation', 'tournament_announcement', 'promotion', 'system') DEFAULT 'system',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if ($conn->query($createNotificationsTable)) { $statusMessages[] = ['type' => 'success', 'text' => 'Table `notifications` created successfully.']; } else { throw new Exception("Error creating notifications table: " . $conn->error); }

    $createPromotionsTable = "CREATE TABLE IF NOT EXISTS `promotions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `discount_percentage` DECIMAL(5, 2) DEFAULT NULL,
        `discount_amount` DECIMAL(10, 2) DEFAULT NULL,
        `code` VARCHAR(50) UNIQUE DEFAULT NULL,
        `start_date` DATETIME NOT NULL,
        `end_date` DATETIME NOT NULL,
        `min_booking_amount` DECIMAL(10, 2) DEFAULT NULL,
        `usage_limit` INT DEFAULT NULL,
        `is_active` BOOLEAN DEFAULT TRUE,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if ($conn->query($createPromotionsTable)) { $statusMessages[] = ['type' => 'success', 'text' => 'Table `promotions` created successfully.']; } else { throw new Exception("Error creating promotions table: " . $conn->error); }

    $createSettingsTable = "CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(255) NOT NULL UNIQUE,
        `setting_value` TEXT NOT NULL,
        `description` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if ($conn->query($createSettingsTable)) { $statusMessages[] = ['type' => 'success', 'text' => 'Table `settings` created successfully.']; } else { throw new Exception("Error creating settings table: " . $conn->error); }

    $createInvoiceDetailsTable = "CREATE TABLE IF NOT EXISTS `invoice_details` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `booking_id` INT NOT NULL,
        `invoice_number` VARCHAR(100) NOT NULL UNIQUE,
        `invoice_date` DATE DEFAULT (CURRENT_DATE),
        `customer_name` VARCHAR(255) NOT NULL,
        `customer_email` VARCHAR(255) NOT NULL,
        `total_amount` DECIMAL(10, 2) NOT NULL,
        `payment_status` ENUM('paid', 'unpaid', 'cancelled') DEFAULT 'unpaid',
        `pdf_path` VARCHAR(255) DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if ($conn->query($createInvoiceDetailsTable)) { $statusMessages[] = ['type' => 'success', 'text' => 'Table `invoice_details` created successfully.']; } else { throw new Exception("Error creating invoice_details table: " . $conn->error); }

    $checkUsers = $conn->query("SELECT COUNT(*) as total FROM `users`");
    $userCount = $checkUsers->fetch_assoc()['total'];
    if ($userCount == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $customerPassword = password_hash('customer123', PASSWORD_DEFAULT);
        $staffPassword = password_hash('staff123', PASSWORD_DEFAULT);
        $insertUsers = "INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `membership_level`, `points`) VALUES
            ('Admin GameZone', 'admin@gamezone.com', '081234567890', '$adminPassword', 'admin', 'gold', 1000),
            ('John Customer', 'customer@email.com', '081122334455', '$customerPassword', 'customer', 'silver', 150),
            ('Jane Staff', 'staff@gamezone.com', '085544332211', '$staffPassword', 'staff', 'bronze', 50)";
        if ($conn->query($insertUsers)) { $statusMessages[] = ['type' => 'success', 'text' => 'Sample users inserted successfully.']; }
    }

    $checkDevices = $conn->query("SELECT COUNT(*) as total FROM `devices`");
    $deviceCount = $checkDevices->fetch_assoc()['total'];
    if ($deviceCount == 0) {
        $insertDevices = "INSERT INTO `devices` (`name`, `type`, `price_per_hour`, `status`, `specification`) VALUES
            ('PS5 Room 1', 'PlayStation 5', 30000.00, 'available', 'Latest Gen Console, 4K TV 55 inch, DualSense Controller x2, Premium Sound System'),
            ('PS5 Room 2', 'PlayStation 5', 30000.00, 'available', 'Latest Gen Console, 4K TV 55 inch, DualSense Controller x2, Premium Sound System'),
            ('PS4 Room 1', 'PlayStation 4', 20000.00, 'available', 'PS4 Pro, Full HD TV 42 inch, DualShock Controller x2'),
            ('PC Gaming Zone A', 'PC Gaming', 25000.00, 'available', 'Intel i7-13700K, RTX 4070, 32GB RAM, 144Hz Monitor, Mechanical Keyboard, Gaming Mouse'),
            ('PC Gaming Zone B', 'PC Gaming', 25000.00, 'available', 'Intel i7-13700K, RTX 4070, 32GB RAM, 144Hz Monitor, Mechanical Keyboard, Gaming Mouse'),
            ('VR Immersion Pod', 'VR Room', 45000.00, 'available', 'Meta Quest 3, Spacious 4x4m Play Area, Full Body Tracking, Premium Headphones'),
            ('Nintendo Switch Room', 'Nintendo Switch', 18000.00, 'maintenance', 'Nintendo Switch OLED, Pro Controller x4, Full HD TV')";
        if ($conn->query($insertDevices)) { $statusMessages[] = ['type' => 'success', 'text' => 'Sample devices inserted successfully.']; }
    }

    $checkSettings = $conn->query("SELECT COUNT(*) as total FROM `settings`");
    $settingCount = $checkSettings->fetch_assoc()['total'];
    if ($settingCount == 0) {
        $insertSettings = "INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
            ('site_name', 'GameZone', 'Website name'),
            ('site_email', 'info@gamezone.com', 'Contact email'),
            ('site_phone', '021-12345678', 'Contact phone'),
            ('booking_advance_days', '7', 'How many days in advance can customers book'),
            ('points_per_10k', '1', 'Points earned per 10,000 IDR spent')";
        if ($conn->query($insertSettings)) { $statusMessages[] = ['type' => 'success', 'text' => 'System settings initialized successfully.']; }
    }

    $statusMessages[] = ['type' => 'success', 'text' => 'Database setup completed successfully!'];
} catch (Exception $e) {
    $error = true;
    $statusMessages[] = ['type' => 'danger', 'text' => 'Setup failed: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Setup - GameZone</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800;900&amp;family=Inter:wght@400;500;600&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
  tailwind.config = {
    darkMode: "class",
    theme: { extend: { "colors": { "background": "#05000a","primary": "#ff0099","secondary": "#00c8ff","tertiary": "#4ae176","on-surface-variant": "#ccc3d8" }, "fontFamily": { "display-lg": ["Hanken Grotesk"],"body-md": ["Inter"],"code-sm": ["JetBrains Mono"] } } } }
</script>
<style>
        body { background: #05000a; color: white; overflow-x: hidden; }
        .logo-text { color: #ff00aa; text-shadow: 0 0 20px #ff00aa; }
        .btn-neon { background: linear-gradient(90deg, #ff0099, #006eff); box-shadow: 0 0 25px #ff0099; color: white; }
        .card-neon { background: rgba(255,255,255,0.05); border: 1px solid #ff0099; border-radius: 20px; }
        .alert-success { background: rgba(74,225,118,0.1); border: 1px solid #4ae17666; color: #4ae176; border-radius: 12px; padding: 12px 16px; }
        .alert-info { background: rgba(0,200,255,0.1); border: 1px solid #00c8ff66; color: #00c8ff; border-radius: 12px; padding: 12px 16px; }
        .alert-danger { background: rgba(255,180,171,0.1); border: 1px solid #ffb4ab66; color: #ffb4ab; border-radius: 12px; padding: 12px 16px; }
    </style>
</head>
<body class="bg-[#05000a] text-white font-body-md antialiased min-h-screen selection:bg-[#ff0099] selection:text-white">
<nav class="w-full fixed top-0 z-50 bg-black/60 backdrop-blur-[10px]">
<div class="flex justify-between items-center max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop h-20">
<a class="flex items-center gap-3 hover:scale-105 active:scale-95 transition-transform" href="index.php">
<span class="font-display-lg text-[28px] font-black logo-text uppercase">GameZone</span>
</a>
</div>
</nav>

<main class="flex-grow pt-20">
<div class="relative z-10 w-full max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-16">
<div class="card-neon p-10">
<div class="text-center mb-8">
<span class="material-symbols-outlined text-6xl text-[#ff0099] mb-4 inline-block drop-shadow-[0_0_15px_#ff0099]">database</span>
<h1 class="font-display-lg text-4xl font-black mb-2" style="background:linear-gradient(90deg,#ff0099,#00c8ff);-webkit-background-clip:text;color:transparent;">Database Setup</h1>
<p class="font-body-md text-[#ddd]">Initialize database tables and sample data</p>
</div>

                <?php foreach ($statusMessages as $msg): ?>
                    <div class="alert-<?= $msg['type'] === 'danger' ? 'danger' : ($msg['type'] === 'info' ? 'info' : 'success') ?> mb-4 flex items-center gap-3">
                        <span class="material-symbols-outlined text-lg">
                            <?php if ($msg['type'] === 'success'): ?>check_circle
                            <?php elseif ($msg['type'] === 'danger'): ?>error
                            <?php else: ?>info
                            <?php endif; ?>
                        </span>
                        <span><?= htmlspecialchars($msg['text']) ?></span>
                    </div>
                <?php endforeach; ?>

                <?php if (!$error): ?>
                    <div class="p-6 rounded-xl mb-6 bg-[#00c8ff]/10 border border-[#00c8ff]">
                        <h5 class="font-headline-sm text-[#00c8ff] mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined">info</span>
                            Default Login Credentials
                        </h5>
                        <div class="space-y-2 font-code-sm text-sm">
                            <div class="flex items-center gap-3"><span class="text-[#ff0099] font-bold">Admin:</span> admin@gamezone.com / admin123</div>
                            <div class="flex items-center gap-3"><span class="text-[#4ae176] font-bold">Customer:</span> customer@email.com / customer123</div>
                            <div class="flex items-center gap-3"><span class="text-[#00c8ff] font-bold">Staff:</span> staff@gamezone.com / staff123</div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 mt-8">
                        <a href="index.php" class="flex-1 font-label-md btn-neon px-8 py-4 rounded-full hover:scale-[1.02] active:scale-95 transition-all duration-300 font-bold tracking-wide text-center">
                            <span class="material-symbols-outlined inline-block align-middle mr-2">home</span>
                            Go to Homepage
                        </a>
                        <a href="login.php" class="flex-1 font-label-md card-neon text-white px-8 py-4 rounded-full hover:bg-white/10 transition-all duration-300 text-center border">
                            <span class="material-symbols-outlined inline-block align-middle mr-2">login</span>
                            Login to Dashboard
                        </a>
                    </div>
                <?php else: ?>
                    <a href="setup.php" class="w-full font-label-md bg-red-900/40 border border-[#ffb4ab] text-[#ffb4ab] px-8 py-4 rounded-full hover:scale-[1.02] transition-all duration-300 text-center block mt-8">
                        <span class="material-symbols-outlined inline-block align-middle mr-2">refresh</span>
                        Try Again
                    </a>
                <?php endif; ?>
</div>
</div>
</main>
</body>
</html>