<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS mess_management');
    echo 'DB_SUCCESS';
} catch (Exception $e) {
    echo 'DB_ERROR: ' . $e->getMessage();
}
