<?php
// Проверка возможности записи в директорию
$test_file = 'test_write.txt';
$content = 'Test write at ' . date('Y-m-d H:i:s') . "\n";

if (file_put_contents($test_file, $content, FILE_APPEND)) {
    echo "Запись успешна! Проверьте файл $test_file";
} else {
    echo "Ошибка записи в файл $test_file";
}
?> 