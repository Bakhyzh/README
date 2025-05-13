<?php
// Начинаем сессию
session_start();

// Проверяем, вошел ли пользователь в систему и является ли он администратором
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    $response = [
        'status' => 'error',
        'message' => 'Неавторизованный доступ'
    ];
    echo json_encode($response);
    exit();
}

// Включаем конфигурацию базы данных
require_once 'config.php';

// Проверяем, отправлена ли форма
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    // Получаем ID пользователя
    $user_id = $conn->real_escape_string($_POST['user_id']);
    
    // Удаляем пользователя
    $sql = "DELETE FROM users WHERE id = '$user_id'";
    
    if ($conn->query($sql) === TRUE) {
        // Удаление успешно
        $response = [
            'status' => 'success',
            'message' => 'Пользователь успешно удален'
        ];
        echo json_encode($response);
    } else {
        // Удаление не удалось
        $response = [
            'status' => 'error',
            'message' => 'Ошибка: ' . $conn->error
        ];
        echo json_encode($response);
    }
    
    $conn->close();
} else {
    // Неверный запрос
    $response = [
        'status' => 'error',
        'message' => 'Неверный запрос'
    ];
    echo json_encode($response);
}
?> 