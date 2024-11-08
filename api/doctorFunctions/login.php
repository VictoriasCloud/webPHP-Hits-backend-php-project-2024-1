<?php
include_once "helpers/headers.php";

function login($requestData) {
    global $Link;

    // Проверка наличия email и пароля
    if (empty($requestData->body->email) || empty($requestData->body->password)) {
        setHTTPSStatus("400", "Invalid arguments: email and password are required.");
        return;
    }

    $email = $requestData->body->email;
    $password = $requestData->body->password;

    if (!validateEmail($email)) {
        setHTTPSStatus("400", "Invalid email format.");
        return;
    }

    if (!validatePassword($password)) {
        setHTTPSStatus("400", "Password must be at least 6 characters long and include both letters and numbers.");
        return;
    }

    // Хешируем пароль после валидации
    $hashedPassword = hash("sha1", $password);

    // Проверка существования врача с такими учетными данными
    $result = $Link->query("SELECT * FROM doctor WHERE email='$email' AND password='$hashedPassword'");
    if (!$result) {
        setHTTPSStatus("500", "InternalServerError: " . $Link->error);
        return;
    }

    if ($result->num_rows == 1) {
        $doctorId = $result->fetch_assoc()['id'];

        // Удаление всех существующих токенов для данного врача
        if (!deleteExistingTokens($doctorId)) {
            setHTTPSStatus("500", "InternalServerError: Failed to delete old tokens.");
            return;
        }

        // Генерация и вставка нового токена
        $token = generateToken();
        $createTime = date('Y-m-d\TH:i:s.u');

        if (!insertToken($token, $doctorId, $createTime)) {
            setHTTPSStatus("500", "InternalServerError: Failed to insert new token.");
            return;
        }

        echo json_encode(['token' => $token]);
        setHTTPSStatus("200", "Doctor was registered");
    } else {
        setHTTPSStatus("400", "Invalid arguments");
        return;
    }
}
