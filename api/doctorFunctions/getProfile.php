<?php
include_once "helpers/headers.php";

function getProfile($token) {
    global $Link;

    // Проверяем, передан ли токен
    if (!isset($token)) {
        setHTTPSStatus("401", "Token is missing");
        return;
    }

    // Проверяем, существует ли токен в базе данных
    $checkTokenQuery = "SELECT * FROM token WHERE value='$token'";
    $checkTokenResult = $Link->query($checkTokenQuery);

    if ($checkTokenResult->num_rows == 1) {
        // Если токен существует, получаем данные пользователя
        $userId = $checkTokenResult->fetch_assoc()['doctorId'];
        $getUserQuery = "SELECT * FROM doctor WHERE id='$userId'";
        $getUserResult = $Link->query($getUserQuery);

        if ($getUserResult->num_rows == 1) {
            $profile = $getUserResult->fetch_assoc();
            unset($profile['password']); // Удаляем пароль из профиля
            echo json_encode($profile);
        } else {
            setHTTPSStatus("404", "User not found");
        }
    } else {
        setHTTPSStatus("401", "Unauthorized");
    }
}