<?php
// Начинаем сессию
session_start();

// Удаляем все переменные сессии
$_SESSION = array();

// Уничтожаем сессию
session_destroy();

// Перенаправляем на страницу входа
header("Location: login.html");
exit();
?> 