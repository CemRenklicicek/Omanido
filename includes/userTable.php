<?php

// Controleer of de 'user' tabel al bestaat
$checkTable = $pdo->query("SHOW TABLES LIKE 'user'");
if ($checkTable->rowCount() == 0) {
    // Maak de 'user' tabel als deze nog niet bestaat
   $pdo->exec("CREATE TABLE `user` (
        `id` int NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `balance` decimal(10,2) NOT NULL,
        `isAdmin` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$adminHash = password_hash('AlfaBankAdminAccount', PASSWORD_DEFAULT);
$ferryHash = password_hash('12345678', PASSWORD_DEFAULT);
$hanHash   = password_hash('password', PASSWORD_DEFAULT);
$royHash   = password_hash('qwerty', PASSWORD_DEFAULT);

    $insertUsersQuery = "
    INSERT INTO `user` (`id`, `username`, `password`, `balance`, `isAdmin`) VALUES
    (1, 'Admin', '$adminHash', 1000.00, 0),
    (2, 'FerryKuhlman', '$ferryHash', 1255.36, 0),
    (5, 'Han2002', '$hanHash', 23424.84, 0),
    (6, 'RoyBos', '$royHash', 9.23, 0);
    ";

    // Voer de SQL-query uit om de gebruikers toe te voegen
    $pdo->exec($insertUsersQuery);
}
