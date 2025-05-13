<?php
// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Тестирование регистрации пользователя</h1>";

try {
    // Включаем конфигурацию базы данных
    require_once 'config.php';
    
    // Тестовые данные пользователя
    $fullname = "Test User";
    $email = "test" . time() . "@example.com"; // Уникальный email
    $username = "testuser" . time(); // Уникальное имя пользователя
    $password = "password123";
    
    echo "<p>Тестовые данные пользователя:</p>";
    echo "<ul>";
    echo "<li>Имя: $fullname</li>";
    echo "<li>Email: $email</li>";
    echo "<li>Имя пользователя: $username</li>";
    echo "<li>Пароль: $password</li>";
    echo "</ul>";
    
    // Хешируем пароль
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Вставляем пользователя в базу данных
    $sql = "INSERT INTO users (fullname, email, username, password) 
            VALUES ('$fullname', '$email', '$username', '$hashed_password')";
    
    echo "<p>SQL запрос: $sql</p>";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>Пользователь успешно зарегистрирован!</p>";
        
        // Проверяем, что пользователь действительно добавлен
        $result = $conn->query("SELECT * FROM users WHERE username = '$username'");
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo "<p>Данные добавленного пользователя:</p>";
            echo "<ul>";
            echo "<li>ID: " . $user['id'] . "</li>";
            echo "<li>Имя: " . $user['fullname'] . "</li>";
            echo "<li>Email: " . $user['email'] . "</li>";
            echo "<li>Имя пользователя: " . $user['username'] . "</li>";
            echo "<li>Дата создания: " . $user['created_at'] . "</li>";
            echo "</ul>";
        } else {
            echo "<p style='color:red'>Странно, пользователь был добавлен, но не найден при проверке.</p>";
        }
    } else {
        echo "<p style='color:red'>Ошибка при регистрации пользователя: " . $conn->error . "</p>";
    }
    
    // Закрываем соединение
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color:red'>Критическая ошибка: " . $e->getMessage() . "</p>";
}
?> 