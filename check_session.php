<?php
// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Начинаем сессию
session_start();

echo "<h1>Проверка сессии</h1>";

if (isset($_SESSION['user_id'])) {
    echo "<p style='color:green'>Вы вошли в систему!</p>";
    echo "<p>Информация о пользователе:</p>";
    echo "<ul>";
    echo "<li>ID: " . $_SESSION['user_id'] . "</li>";
    echo "<li>Имя пользователя: " . $_SESSION['username'] . "</li>";
    echo "<li>Полное имя: " . $_SESSION['fullname'] . "</li>";
    echo "<li>Администратор: " . ($_SESSION['is_admin'] ? 'Да' : 'Нет') . "</li>";
    echo "</ul>";
    
    // Проверяем, существует ли пользователь в базе данных
    try {
        // Подключаем конфигурацию базы данных
        require_once 'config.php';
        
        $user_id = $_SESSION['user_id'];
        $result = $conn->query("SELECT * FROM users WHERE id = $user_id");
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo "<p>Данные пользователя из базы данных:</p>";
            echo "<ul>";
            echo "<li>ID: " . $user['id'] . "</li>";
            echo "<li>Полное имя: " . $user['fullname'] . "</li>";
            echo "<li>Email: " . $user['email'] . "</li>";
            echo "<li>Имя пользователя: " . $user['username'] . "</li>";
            echo "<li>Дата создания: " . $user['created_at'] . "</li>";
            echo "<li>Администратор: " . ($user['is_admin'] ? 'Да' : 'Нет') . "</li>";
            echo "</ul>";
        } else {
            echo "<p style='color:red'>Пользователь с ID " . $user_id . " не найден в базе данных!</p>";
        }
        
        $conn->close();
    } catch (Exception $e) {
        echo "<p style='color:red'>Ошибка при проверке пользователя: " . $e->getMessage() . "</p>";
    }
    
    echo "<p><a href='logout.php'>Выйти</a></p>";
} else {
    echo "<p style='color:red'>Вы не вошли в систему!</p>";
    echo "<p>Возможные причины:</p>";
    echo "<ul>";
    echo "<li>Вы не авторизовались</li>";
    echo "<li>Сессия не сохраняется между запросами</li>";
    echo "<li>Сессия истекла</li>";
    echo "</ul>";
    echo "<p><a href='simple_login.html'>Войти</a> | <a href='simple_register.html'>Зарегистрироваться</a></p>";
}

// Выводим информацию о сессии
echo "<h2>Информация о сессии:</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session name: " . session_name() . "</p>";
echo "<p>Session status: " . session_status() . " (1 = disabled, 2 = enabled but no session, 3 = enabled and session exists)</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Проверяем настройки PHP для сессий
echo "<h2>Настройки PHP для сессий:</h2>";
echo "<ul>";
echo "<li>session.save_path: " . ini_get('session.save_path') . "</li>";
echo "<li>session.cookie_domain: " . ini_get('session.cookie_domain') . "</li>";
echo "<li>session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "</li>";
echo "<li>session.cookie_path: " . ini_get('session.cookie_path') . "</li>";
echo "<li>session.cookie_secure: " . ini_get('session.cookie_secure') . "</li>";
echo "<li>session.cookie_httponly: " . ini_get('session.cookie_httponly') . "</li>";
echo "<li>session.use_cookies: " . ini_get('session.use_cookies') . "</li>";
echo "<li>session.use_only_cookies: " . ini_get('session.use_only_cookies') . "</li>";
echo "</ul>";
?> 