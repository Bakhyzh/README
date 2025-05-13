<?php
// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Прямое тестирование регистрации пользователя</h1>";

try {
    // Подключаем конфигурацию базы данных
    require_once 'config.php';
    
    // Генерируем уникальные данные для тестового пользователя
    $timestamp = time();
    $fullname = "Test User " . $timestamp;
    $email = "test" . $timestamp . "@example.com";
    $username = "testuser" . $timestamp;
    $password = "password123";
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    echo "<p>Данные тестового пользователя:</p>";
    echo "<ul>";
    echo "<li>Полное имя: $fullname</li>";
    echo "<li>Email: $email</li>";
    echo "<li>Имя пользователя: $username</li>";
    echo "<li>Пароль: $password</li>";
    echo "</ul>";
    
    // Проверяем, существует ли таблица users
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows == 0) {
        echo "<p style='color:orange'>Таблица 'users' не существует. Создаем...</p>";
        
        // Создаем таблицу users
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fullname VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_admin TINYINT(1) DEFAULT 0
        )";
        
        if ($conn->query($sql) !== TRUE) {
            throw new Exception("Ошибка при создании таблицы users: " . $conn->error);
        }
        
        echo "<p style='color:green'>Таблица 'users' успешно создана!</p>";
    }
    
    // Вставляем тестового пользователя в базу данных
    $sql = "INSERT INTO users (fullname, email, username, password) 
            VALUES ('$fullname', '$email', '$username', '$hashed_password')";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>Тестовый пользователь успешно зарегистрирован!</p>";
        echo "<p>ID пользователя: " . $conn->insert_id . "</p>";
        
        // Проверяем, что пользователь действительно добавлен
        $result = $conn->query("SELECT * FROM users WHERE username = '$username'");
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo "<p>Данные добавленного пользователя из базы:</p>";
            echo "<ul>";
            echo "<li>ID: " . $user['id'] . "</li>";
            echo "<li>Полное имя: " . $user['fullname'] . "</li>";
            echo "<li>Email: " . $user['email'] . "</li>";
            echo "<li>Имя пользователя: " . $user['username'] . "</li>";
            echo "<li>Дата создания: " . $user['created_at'] . "</li>";
            echo "</ul>";
            
            // Проверяем вход с этими данными
            echo "<h2>Тестирование входа с созданными учетными данными</h2>";
            
            // Проверяем, существует ли пользователь
            $sql = "SELECT * FROM users WHERE username = '$username'";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Проверяем пароль
                if (password_verify($password, $user['password'])) {
                    echo "<p style='color:green'>Вход успешен! Пароль верный.</p>";
                } else {
                    echo "<p style='color:red'>Ошибка входа: неверный пароль.</p>";
                }
            } else {
                echo "<p style='color:red'>Ошибка входа: пользователь не найден.</p>";
            }
        } else {
            echo "<p style='color:red'>Странно, пользователь был добавлен, но не найден при проверке.</p>";
        }
    } else {
        throw new Exception("Ошибка при регистрации пользователя: " . $conn->error);
    }
    
    echo "<p><a href='register.html'>Перейти на страницу регистрации</a> | <a href='login.html'>Перейти на страницу входа</a></p>";
    
    // Закрываем соединение
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color:red'>Критическая ошибка: " . $e->getMessage() . "</p>";
}
?> 