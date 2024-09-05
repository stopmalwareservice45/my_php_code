<?php

// Получить URL-адрес, который нужно запросить (например, через GET-параметр 'url')
$url = $_GET['url'] ?? '';

// Проверить на наличие текущего скрипта в качестве URL
$current_script = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
if ($url === $current_script) {
    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
    echo 'Ошибка: Нельзя запрашивать данный файл.';
    exit;
}

// Инициализировать cURL-сессию
$ch = curl_init();

// Установить URL, к которому нужно выполнить запрос
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Выполнить запрос и получить ответ
$response = curl_exec($ch);

// Проверка на ошибки cURL
if ($response === false) {
    $error = curl_error($ch);
    header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
    echo 'Ошибка cURL: ' . $error;
    curl_close($ch);
    exit;
}

// Получить HTTP-код ответа
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Закрыть cURL-сессию
curl_close($ch);

// Проверить, был ли запрос успешным
if ($http_code === 200) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="downloaded_file"'); // Можно изменить на нужное имя
    header('Content-Length: ' . strlen($response)); // Длина содержимого
    echo $response; // Отправить содержимое файла
} else {
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
    echo 'Ошибка: Не удалось получить файл. Код ответа: ' . $http_code;
}
?>
