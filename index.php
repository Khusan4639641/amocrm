<?php

include 'AmoCrmApi.class.php';

require_once __DIR__ . '/Models/BaseModel.php';
require_once __DIR__ . '/Models/Lead.php';
require_once __DIR__ . '/Models/Contact.php';


// Проверка на пустоту $_REQUEST
if (empty($_REQUEST)) {
    echo "Нет данных в запросе.";
    exit;
}

// Инициализация класса AmoCrmApi
$api = new AmoCrmApi();

// Логирование запроса
$api->logRequest();

$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['leads']['add'])) {
    foreach ($data['leads']['add'] as $leadData) {
        $lead = new Lead($leadData);
        $lead->save();
    }
}

if (!empty($data['leads']['update'])) {
    foreach ($data['leads']['update'] as $leadData) {
        $lead = new Lead($leadData);
        $lead->save(); // Save уже обновит, если ID есть
    }
}

if (!empty($data['contacts']['add'])) {
    foreach ($data['contacts']['add'] as $contactData) {
        $contact = new Contact($contactData);
        $contact->save();
    }
}



?>