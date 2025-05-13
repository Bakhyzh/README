<?php
// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Проверка состояния базы данных</h1>";

try {
    // Подключаем файл конфигурации
    require_once 'config.php';
    
    echo "<p style='color:green'>Успешное подключение к MySQL!</p>";
    
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
        
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color:green'>Таблица 'users' успешно создана!</p>";
            
            // Создаем администратора
            $password = 'admin123';
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (fullname, email, username, password, is_admin) 
                    VALUES ('Admin User', 'admin@example.com', 'admin', '$hashed_password', 1)";
            
            if ($conn->query($sql) === TRUE) {
                echo "<p style='color:green'>Администратор успешно создан!</p>";
                echo "<p>Логин: <strong>admin</strong><br>Пароль: <strong>admin123</strong></p>";
            } else {
                echo "<p style='color:red'>Ошибка при создании администратора: " . $conn->error . "</p>";
            }
        } else {
            throw new Exception("Ошибка при создании таблицы: " . $conn->error);
        }
    } else {
        echo "<p style='color:green'>Таблица 'users' существует.</p>";
        
        // Проверяем структуру таблицы
        $result = $conn->query("DESCRIBE users");
        echo "<h2>Структура таблицы users:</h2>";
        echo "<table border='1'><tr><th>Поле</th><th>Тип</th><th>Null</th><th>Ключ</th><th>По умолчанию</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Проверяем, есть ли записи в таблице
        $result = $conn->query("SELECT * FROM users");
        echo "<h2>Записи в таблице users:</h2>";
        if ($result->num_rows > 0) {
            echo "<table border='1'><tr><th>ID</th><th>Имя</th><th>Email</th><th>Имя пользователя</th><th>Дата создания</th><th>Админ</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['fullname'] . "</td>";
                echo "<td>" . $row['email'] . "</td>";
                echo "<td>" . $row['username'] . "</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "<td>" . $row['is_admin'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>В таблице users нет записей.</p>";
            
            // Создаем администратора, если записей нет
            $password = 'admin123';
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (fullname, email, username, password, is_admin) 
                    VALUES ('Admin User', 'admin@example.com', 'admin', '$hashed_password', 1)";
            
            if ($conn->query($sql) === TRUE) {
                echo "<p style='color:green'>Администратор успешно создан!</p>";
                echo "<p>Логин: <strong>admin</strong><br>Пароль: <strong>admin123</strong></p>";
            } else {
                echo "<p style='color:red'>Ошибка при создании администратора: " . $conn->error . "</p>";
            }
        }
    }
    
    echo "<p style='color:green'>Проверка базы данных завершена успешно!</p>";
    echo "<p><a href='register.html'>Перейти на страницу регистрации</a> | <a href='login.html'>Перейти на страницу входа</a></p>";
    
    // Закрываем соединение
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color:red'>Критическая ошибка: " . $e->getMessage() . "</p>";
    echo "<p>Убедитесь, что у вас установлен и запущен сервер MySQL, и что учетные данные для подключения верны.</p>";
}
?> 