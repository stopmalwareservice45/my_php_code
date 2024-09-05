<?php

// Получить URL-адрес, который нужно запросить (например, через GET-параметр 'url')
$url = $_GET['url'];

// Инициализировать cURL-сессию
$ch = curl_init();

// Установить URL, к которому нужно выполнить запрос
curl_setopt($ch, CURLOPT_URL, $url);

// Включить возможность передачи заголовков
curl_setopt($ch, CURLOPT_HEADER, 0);

// Включить передачу результата вместо вывода его на экран
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Выполнить запрос и получить ответ
$response = curl_exec($ch);

// Получить HTTP-код ответа
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Закрыть cURL-сессию
curl_close($ch);

// Проверить, был ли запрос успешным
if ($http_code === 200) {
    // Определить заголовки для передачи файла
    // Пример для скачивания файла - определяем тип содержимого
    header('Content-Type: application/octet-stream');
    // Установить заголовок для обеспечения скачивания
    header('Content-Disposition: attachment; filename="downloaded_file"'); // Можно изменить на название
    header('Content-Length: ' . strlen($response)); // Длина содержимого

    // Отправить содержимое файла
    echo $response;
} else {
    // Если не удалось получить файл, вывести сообщение об ошибке
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
    echo 'Ошибка: Не удалось получить файл. Код ответа: ' . $http_code;
}
?>
