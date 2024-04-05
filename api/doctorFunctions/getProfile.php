<?php
include_once "helpers/headers.php";

function getProfile($token) {
    global $Link;

    // Проверяем, передан ли токен
    if (!isset($token)) {
        // Если токен не передан, возвращаем статус 400 (Bad Request) с сообщением об ошибке
        setHTTPSStatus("400", "Token is missing");
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
            // Если пользователь найден, возвращаем его профиль
            $profile = $getUserResult->fetch_assoc();
            unset($profile['password']); // Удаляем пароль из профиля
            echo json_encode($profile);
        } else {
            // Если пользователь не найден, возвращаем статус 404 (Not Found)
            setHTTPSStatus("404", "User not found");
        }
    } else {
        // Если токен не найден, возвращаем статус 401 (Unauthorized)
        setHTTPSStatus("401", "Unauthorized");
    }
}