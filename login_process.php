<?php
// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Начинаем сессию
session_start();

// Включаем конфигурацию базы данных
require_once 'config.php';

// Начинаем логирование
file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - Начало обработки входа\n", FILE_APPEND);

// Проверяем, отправлена ли форма
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Получаем данные формы и очищаем их
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - Получены данные: username=$username\n", FILE_APPEND);
    
    // Проверяем, существует ли пользователь
    $sql = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
    file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - SQL запрос: $sql\n", FILE_APPEND);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - Пользователь найден: id=" . $user['id'] . ", is_admin=" . $user['is_admin'] . "\n", FILE_APPEND);
        
        // Проверяем пароль
        if (password_verify($password, $user['password'])) {
            file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - Пароль верный\n", FILE_APPEND);
            
            // Пароль верный, создаем сессию
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // Проверяем, является ли пользователь администратором
            if ($user['is_admin'] == 1) {
                file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - Пользователь является администратором\n", FILE_APPEND);
                
                $response = [
                    'status' => 'success',
                    'message' => 'Вход выполнен успешно! Перенаправление в панель администратора...',
                    'redirect' => 'admin_panel.php'
                ];
            } else {
                file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - Пользователь не является администратором\n", FILE_APPEND);
                
                $response = [
                    'status' => 'success',
                    'message' => 'Вход выполнен успешно! Перенаправление в профиль...',
                    'redirect' => 'profile.html'
                ];
            }
            
            echo json_encode($response);
        } else {
            // Пароль неверный
            file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - Пароль неверный\n", FILE_APPEND);
            
            $response = [
                'status' => 'error',
                'message' => 'Неверное имя пользователя или пароль'
            ];
            echo json_encode($response);
        }
    } else {
        // Пользователь не существует
        file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - Пользователь не найден\n", FILE_APPEND);
        
        $response = [
            'status' => 'error',
            'message' => 'Аккаунт не найден. Пожалуйста, зарегистрируйтесь.',
            'redirect_to_register' => true
        ];
        echo json_encode($response);
    }
    
    $conn->close();
} else {
    file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - Метод запроса не POST\n", FILE_APPEND);
    
    // Неверный метод запроса
    $response = [
        'status' => 'error',
        'message' => 'Неверный метод запроса'
    ];
    echo json_encode($response);
}
?> 