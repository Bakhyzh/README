<?php
// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Функция для безопасного логирования
function safe_log($message) {
    try {
        file_put_contents('register_log.txt', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
    } catch (Exception $e) {
        // Если не удается записать в файл, игнорируем ошибку
    }
}

try {
    safe_log("Начало обработки регистрации");
    
    // Включаем конфигурацию базы данных
    require_once 'config.php';
    
    // Проверяем, отправлена ли форма
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Проверяем, что все необходимые поля существуют
        if (!isset($_POST['fullname']) || !isset($_POST['email']) || !isset($_POST['username']) || 
            !isset($_POST['password']) || !isset($_POST['confirmPassword'])) {
            throw new Exception("Отсутствуют необходимые поля формы");
        }
        
        // Получаем данные формы и очищаем их
        $fullname = $conn->real_escape_string($_POST['fullname']);
        $email = $conn->real_escape_string($_POST['email']);
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirmPassword'];
        
        safe_log("Получены данные: fullname=$fullname, email=$email, username=$username");
        
        // Проверяем данные
        $errors = [];
        
        // Проверяем, совпадают ли пароли
        if ($password !== $confirm_password) {
            $errors[] = "Пароли не совпадают";
            safe_log("Ошибка: пароли не совпадают");
        }
        
        // Проверяем, существует ли уже email
        $check_email = $conn->query("SELECT * FROM users WHERE email = '$email'");
        if (!$check_email) {
            throw new Exception("Ошибка SQL при проверке email: " . $conn->error);
        }
        
        if ($check_email->num_rows > 0) {
            $errors[] = "Email уже зарегистрирован. Пожалуйста, войдите в систему.";
            safe_log("Ошибка: email уже существует");
        }
        
        // Проверяем, существует ли уже имя пользователя
        $check_username = $conn->query("SELECT * FROM users WHERE username = '$username'");
        if (!$check_username) {
            throw new Exception("Ошибка SQL при проверке username: " . $conn->error);
        }
        
        if ($check_username->num_rows > 0) {
            $errors[] = "Имя пользователя уже занято. Пожалуйста, выберите другое.";
            safe_log("Ошибка: имя пользователя уже существует");
        }
        
        // Если нет ошибок, продолжаем регистрацию
        if (empty($errors)) {
            safe_log("Нет ошибок, продолжаем регистрацию");
            
            // Хешируем пароль
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Вставляем пользователя в базу данных
            $sql = "INSERT INTO users (fullname, email, username, password) 
                    VALUES ('$fullname', '$email', '$username', '$hashed_password')";
            
            safe_log("SQL запрос: $sql");
            
            if ($conn->query($sql) === TRUE) {
                // Регистрация успешна
                safe_log("Регистрация успешна");
                
                // Начинаем сессию и сохраняем данные пользователя
                session_start();
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['fullname'] = $fullname;
                $_SESSION['is_admin'] = 0; // По умолчанию не администратор
                
                $response = [
                    'status' => 'success',
                    'message' => 'Регистрация успешна! Перенаправление на страницу входа...',
                    'redirect' => 'login.html'
                ];
                echo json_encode($response);
            } else {
                // Регистрация не удалась
                throw new Exception("Ошибка при выполнении SQL запроса: " . $conn->error);
            }
        } else {
            // Возвращаем ошибки
            safe_log("Возвращаем ошибки: " . implode(', ', $errors));
            
            $response = [
                'status' => 'error',
                'message' => implode(', ', $errors),
                'login_instead' => (strpos(implode(' ', $errors), 'войдите в систему') !== false)
            ];
            echo json_encode($response);
        }
        
        $conn->close();
    } else {
        safe_log("Метод запроса не POST");
        
        // Неверный метод запроса
        $response = [
            'status' => 'error',
            'message' => 'Неверный метод запроса'
        ];
        echo json_encode($response);
    }
} catch (Exception $e) {
    safe_log("Критическая ошибка: " . $e->getMessage());
    
    // Возвращаем общее сообщение об ошибке клиенту
    $response = [
        'status' => 'error',
        'message' => 'Произошла ошибка при регистрации. Пожалуйста, попробуйте еще раз позже.',
        'debug' => $e->getMessage() // Только для отладки, в продакшене лучше убрать
    ];
    echo json_encode($response);
}
?> 