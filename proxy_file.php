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

// Закрыть cURL-сессию
curl_close($ch);

// Вывести ответ в качестве ответа прокси-сервера
echo $response;

?>