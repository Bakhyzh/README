<?php
// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Функция для безопасного логирования
function db_log($message) {
    try {
        file_put_contents('db_log.txt', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
    } catch (Exception $e) {
        // Если не удается записать в файл, игнорируем ошибку
    }
}

// Database configuration для macOS с Homebrew MySQL
$db_host = 'localhost';
$db_user = 'root';  // По умолчанию для Homebrew MySQL
$db_pass = '';      // По умолчанию для Homebrew MySQL пароль пустой
$db_name = 'portfolio_db';

db_log("Попытка подключения к базе данных: host=$db_host, user=$db_user, db=$db_name");

// Создаем подключение к базе данных
$conn = new mysqli($db_host, $db_user, $db_pass);

// Проверяем подключение
if ($conn->connect_error) {
    db_log("Ошибка подключения к базе данных: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Проверяем, существует ли база данных
$result = $conn->query("SHOW DATABASES LIKE '$db_name'");
if ($result->num_rows == 0) {
    // Создаем базу данных, если она не существует
    db_log("База данных $db_name не существует. Создаем...");
    if ($conn->query("CREATE DATABASE IF NOT EXISTS $db_name") === TRUE) {
        db_log("База данных $db_name успешно создана");
    } else {
        db_log("Ошибка при создании базы данных: " . $conn->error);
        die("Error creating database: " . $conn->error);
    }
}

// Выбираем базу данных
$conn->select_db($db_name);

// Устанавливаем кодировку
$conn->set_charset("utf8");

// Логируем успешное подключение
db_log("Успешное подключение к базе данных");
?> 