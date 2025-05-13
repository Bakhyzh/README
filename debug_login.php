<?php
// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Создаем файл журнала для отладки
$log_file = 'debug_login_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Начало отладки входа\n", FILE_APPEND);

// Логируем все входящие данные
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Метод запроса: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Данные POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Данные GET: " . print_r($_GET, true) . "\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Заголовки: " . print_r(getallheaders(), true) . "\n", FILE_APPEND);

// Начинаем сессию
session_start();

// Проверяем, отправлена ли форма
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Проверяем, что все необходимые поля существуют
    $required_fields = ['username', 'password'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Отсутствуют обязательные поля: " . implode(', ', $missing_fields) . "\n", FILE_APPEND);
        
        $response = [
            'status' => 'error',
            'message' => 'Отсутствуют обязательные поля: ' . implode(', ', $missing_fields),
            'debug' => true
        ];
        echo json_encode($response);
        exit;
    }
    
    // Получаем данные формы
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Получены данные: username=$username\n", FILE_APPEND);
    
    try {
        // Подключаем конфигурацию базы данных
        require_once 'config.php';
        
        // Проверяем, существует ли пользователь
        $sql = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - SQL запрос: $sql\n", FILE_APPEND);
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Пользователь найден: id=" . $user['id'] . ", is_admin=" . $user['is_admin'] . "\n", FILE_APPEND);
            
            // Проверяем пароль
            if (password_verify($password, $user['password'])) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Пароль верный\n", FILE_APPEND);
                
                // Пароль верный, создаем сессию
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Проверяем, является ли пользователь администратором
                if ($user['is_admin'] == 1) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Пользователь является администратором\n", FILE_APPEND);
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Вход выполнен успешно! Перенаправление в панель администратора...',
                        'redirect' => 'admin_panel.php',
                        'debug' => true
                    ];
                } else {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Пользователь не является администратором\n", FILE_APPEND);
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Вход выполнен успешно! Перенаправление в профиль...',
                        'redirect' => 'profile.html',
                        'debug' => true
                    ];
                }
                
                echo json_encode($response);
            } else {
                // Пароль неверный
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Пароль неверный\n", FILE_APPEND);
                
                $response = [
                    'status' => 'error',
                    'message' => 'Неверное имя пользователя или пароль',
                    'debug' => true
                ];
                echo json_encode($response);
            }
        } else {
            // Пользователь не существует
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Пользователь не найден\n", FILE_APPEND);
            
            $response = [
                'status' => 'error',
                'message' => 'Аккаунт не найден. Пожалуйста, зарегистрируйтесь.',
                'redirect_to_register' => true,
                'debug' => true
            ];
            echo json_encode($response);
        }
        
        $conn->close();
    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Критическая ошибка: " . $e->getMessage() . "\n", FILE_APPEND);
        
        // Возвращаем общее сообщение об ошибке клиенту
        $response = [
            'status' => 'error',
            'message' => 'Произошла ошибка при входе. Пожалуйста, попробуйте еще раз позже.',
            'debug_message' => $e->getMessage(),
            'debug' => true
        ];
        echo json_encode($response);
    }
} else {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Метод запроса не POST\n", FILE_APPEND);
    
    // Неверный метод запроса
    $response = [
        'status' => 'error',
        'message' => 'Неверный метод запроса',
        'debug' => true
    ];
    echo json_encode($response);
}
?> 