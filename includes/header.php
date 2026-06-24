<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Controleer of de gebruiker is ingelogd
if (isset($_SESSION['loggedin'])) {
    $username = $_SESSION['user']['username'] ?? '';
}
?>

<div class="bg-white py-4 shadow-md">
    <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
        <a href="<?= isset($_SESSION['loggedin']) ? 'dashboard.php' : 'index.php' ?>">
            <img src="../img/Omanido2.png" alt="Bank Logo" class="h-12">
        </a>

        <?php if (!empty($username)): ?>
            <div class="text-right">
                <p class="text-gray-500 text-sm">
                    Welkom,  
                    <a href="transacties.php" class="text-blue-600 hover:underline">
                        <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['weak_password_warning'])) { ?>
    <div class="max-w-7xl mx-auto px-4 mt-3">
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 p-3 rounded">
            <?= htmlspecialchars($_SESSION['weak_password_warning'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
    <?php 
    unset($_SESSION['weak_password_warning']); 
    } ?>


</div>
