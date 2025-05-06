<?php

include 'AmoCrmApi.class.php';

try {
    // Создаём экземпляр класса
    $api = new AmoCrmApi();
    // Обрабатываем авторизационный код, если он есть
    $api->handleAuthCode();

} catch (Exception $e) {
    echo 'Ошибка: ' . $e->getMessage();
}
