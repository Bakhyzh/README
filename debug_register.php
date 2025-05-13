<?php
// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Создаем файл журнала для отладки
$log_file = 'debug_register_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Начало отладки\n", FILE_APPEND);

// Логируем все входящие данные
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Метод запроса: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Данные POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Данные GET: " . print_r($_GET, true) . "\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Заголовки: " . print_r(getallheaders(), true) . "\n", FILE_APPEND);

// Проверяем, отправлена ли форма
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Проверяем, что все необходимые поля существуют
    $required_fields = ['fullname', 'email', 'username', 'password', 'confirmPassword'];
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
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirmPassword'];
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Получены данные: fullname=$fullname, email=$email, username=$username\n", FILE_APPEND);
    
    // Проверяем данные
    $errors = [];
    
    // Проверяем, совпадают ли пароли
    if ($password !== $confirm_password) {
        $errors[] = "Пароли не совпадают";
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Ошибка: пароли не совпадают\n", FILE_APPEND);
    }
    
    // Если нет ошибок, продолжаем регистрацию
    if (empty($errors)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Нет ошибок валидации, продолжаем регистрацию\n", FILE_APPEND);
        
        try {
            // Подключаем конфигурацию базы данных
            require_once 'config.php';
            
            // Проверяем, существует ли уже email
            $check_email = $conn->query("SELECT * FROM users WHERE email = '$email'");
            if (!$check_email) {
                throw new Exception("Ошибка SQL при проверке email: " . $conn->error);
            }
            
            if ($check_email->num_rows > 0) {
                $errors[] = "Email уже зарегистрирован. Пожалуйста, войдите в систему.";
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Ошибка: email уже существует\n", FILE_APPEND);
            }
            
            // Проверяем, существует ли уже имя пользователя
            $check_username = $conn->query("SELECT * FROM users WHERE username = '$username'");
            if (!$check_username) {
                throw new Exception("Ошибка SQL при проверке username: " . $conn->error);
            }
            
            if ($check_username->num_rows > 0) {
                $errors[] = "Имя пользователя уже занято. Пожалуйста, выберите другое.";
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Ошибка: имя пользователя уже существует\n", FILE_APPEND);
            }
            
            // Если нет ошибок, продолжаем регистрацию
            if (empty($errors)) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Нет ошибок, продолжаем регистрацию\n", FILE_APPEND);
                
                // Хешируем пароль
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Вставляем пользователя в базу данных
                $sql = "INSERT INTO users (fullname, email, username, password) 
                        VALUES ('$fullname', '$email', '$username', '$hashed_password')";
                
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - SQL запрос: $sql\n", FILE_APPEND);
                
                if ($conn->query($sql) === TRUE) {
                    // Регистрация успешна
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Регистрация успешна\n", FILE_APPEND);
                    
                    // Начинаем сессию и сохраняем данные пользователя
                    session_start();
                    $_SESSION['user_id'] = $conn->insert_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['fullname'] = $fullname;
                    $_SESSION['is_admin'] = 0; // По умолчанию не администратор
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Регистрация успешна! Перенаправление на страницу входа...',
                        'redirect' => 'login.html',
                        'debug' => true
                    ];
                    echo json_encode($response);
                } else {
                    // Регистрация не удалась
                    throw new Exception("Ошибка при выполнении SQL запроса: " . $conn->error);
                }
            } else {
                // Возвращаем ошибки
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Возвращаем ошибки: " . implode(', ', $errors) . "\n", FILE_APPEND);
                
                $response = [
                    'status' => 'error',
                    'message' => implode(', ', $errors),
                    'login_instead' => (strpos(implode(' ', $errors), 'войдите в систему') !== false),
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
                'message' => 'Произошла ошибка при регистрации. Пожалуйста, попробуйте еще раз позже.',
                'debug_message' => $e->getMessage(),
                'debug' => true
            ];
            echo json_encode($response);
        }
    } else {
        // Возвращаем ошибки
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Возвращаем ошибки валидации: " . implode(', ', $errors) . "\n", FILE_APPEND);
        
        $response = [
            'status' => 'error',
            'message' => implode(', ', $errors),
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