<?php
include_once "helpers/headers.php";

function logout($token) {
    global $Link;

    // Проверяем, передан ли токен
    if (!isset($token)) {
        setHTTPSStatus("401", "Unauthorized");
        return;
    }

    // Получаем ID врача по токену
    $doctorQuery = "SELECT doctorId FROM token WHERE value='$token'";
    $doctorResult = $Link->query($doctorQuery);

    // Проверяем, существует ли такой токен
    if ($doctorResult && $doctorResult->num_rows > 0) {
        $doctorId = $doctorResult->fetch_assoc()['doctorId'];

        // Удаляем все токены для данного врача
        $deleteAllTokensQuery = "DELETE FROM token WHERE doctorId='$doctorId'";
        $deleteAllTokensResult = $Link->query($deleteAllTokensQuery);

        // Проверяем успешность выполнения запроса
        if ($deleteAllTokensResult) {
            setHTTPSStatus("200", "Logged out successfully");
        } else {
            setHTTPSStatus("500", "InternalServerError " . $Link->error);
        }
    } else {
        setHTTPSStatus("404", "Token not found or invalid");
    }
}
