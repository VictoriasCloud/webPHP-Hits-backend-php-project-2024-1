<?php

// Функция для создания нового пациента
function createNewPatient($requestBody) {
    // Проверяем наличие обязательных полей
    if (!isset($requestBody->name) || !isset($requestBody->gender)) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "Неверные аргументы"));
        return;
    }

    // Создаем пациента
    $patient = array(
        "id" => uniqid(), // Генерируем уникальный ID
        "createTime" => date("Y-m-d H:i:s"), // Устанавливаем текущее время
        "name" => $requestBody->name,
        "gender" => $requestBody->gender
    );

    // Устанавливаем необязательное поле
    if (isset($requestBody->birthday)) {
        $patient["birthday"] = $requestBody->birthday;
    }

    // Сохраняем информацию о пациенте в базе данных или выполняем другие действия по необходимости

    // Возвращаем успешный ответ
    http_response_code(200);
    echo $patient["id"];
}

// Функция для получения списка пациентов
function getPatientsList($queryParams) {
    // Ваша логика для получения списка пациентов с учетом параметров запроса

    // Примерный код, который возвращает пустой список пациентов
    $response = array(
        "patients" => array(),
        "pagination" => array(
            "size" => 0,
            "count" => 0,
            "current" => 0
        )
    );

    // Отправляем ответ
    http_response_code(200);
    echo json_encode($response);
}

// Обработка запросов
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Если это POST запрос, обрабатываем создание нового пациента
    $requestBody = json_decode(file_get_contents("php://input"));
    createNewPatient($requestBody);
} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Если это GET запрос, обрабатываем получение списка пациентов
    $queryParams = $_GET;
    getPatientsList($queryParams);
} else {
    // Если метод запроса не поддерживается, отправляем ошибку
    http_response_code(405);
    echo json_encode(array("status" => "error", "message" => "Метод не поддерживается"));
}

?>
