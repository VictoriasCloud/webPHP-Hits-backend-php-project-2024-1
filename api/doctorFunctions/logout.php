<?php
include_once "helpers/headers.php";

function logout($token) {
    global $Link;

    // Проверяем, передан ли токен
    if (!isset($token)) {
        setHTTPSStatus("401", "Token is missing");
        return;
    }

    // Удаляем токен из базы данных
    $deleteTokenQuery = "DELETE FROM token WHERE value='$token'";
    $deleteTokenResult = $Link->query($deleteTokenQuery);

    // Проверяем успешность выполнения запроса
    if ($deleteTokenResult) {
        setHTTPSStatus("200", "Logged out successfully");
    } else {
        setHTTPSStatus("500", "Failed to logout: " . $Link->error);
    }
}
