<?php
// Buat file: generate_password.php
$password = 'admin';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo "Password Hash:\n";
echo $hash;
