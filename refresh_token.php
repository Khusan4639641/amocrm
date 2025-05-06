<?php

include 'AmoCrmApi.class.php';

// Инициализация класса AmoCrmApi
$api = new AmoCrmApi();

try {
    // Обновление токена
    if (!empty($api->getConfig()['auth_code'])) {
        // Если есть auth_code, используем его для получения нового токена
        echo "Получение нового токена с использованием auth_code...\n";
        $api->updateAccessToken($api->getConfig()['auth_code']);
        echo "Auth code успешно использован и очищен.\n";
    } else {
        // Если auth_code отсутствует, обновляем токен с использованием refresh_token
        echo "Обновление токена с использованием refresh_token...\n";
        $api->updateAccessToken($api->getConfig()['refresh_token']);
    }

    echo "Access токен успешно обновлён и сохранён.\n";
} catch (Exception $e) {
    // Обработка ошибок
    die("Ошибка: {$e->getMessage()}\nКод ошибки: {$e->getCode()}");
}
