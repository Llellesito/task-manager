<?php

$host = 'db';
$dbname = 'task_manager';
$user = 'root';
$password = 'root';

$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $user,
    $password
);

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);