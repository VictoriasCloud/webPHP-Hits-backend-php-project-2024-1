<?php
include_once "helpers/headers.php";

function logout($token) {
    global $Link;
    echo $token;

    // Проверяем, передан ли токен
    if (!isset($token)) {
        // Если токен не передан, возвращаем статус 400 (Bad Request) с сообщением об ошибке
        setHTTPSStatus("400", "Token is missing");
        return;
    }

    // Удаляем токен из базы данных
    $deleteTokenQuery = "DELETE FROM token WHERE value='$token'";
    $deleteTokenResult = $Link->query($deleteTokenQuery);

    // Проверяем успешность выполнения запроса
    if ($deleteTokenResult) {
        // Если токен успешно удален, возвращаем статус 200 (OK) с сообщением об успешном разлогинивании
        setHTTPSStatus("200", "Logged out successfully");
    } else {
        // Если возникла ошибка при удалении токена из базы данных, возвращаем статус 500 (Internal Server Error)
        setHTTPSStatus("500", "Failed to logout: " . $Link->error);
    }
}
?>
