<?php
include_once "helpers/headers.php";

function login($requestData) {
    global $Link;

    // Извлечение данных из запроса
    $email = $requestData->body->email;
    $password = hash("sha1", $requestData->body->password);

    // Проверка наличия пользователя с указанным email и паролем
    $result = $Link->query("SELECT * FROM doctor WHERE email='$email' AND password='$password'");

    // Проверяем, существует ли токен для этого пользователя
    $tokenCheckQuery = "SELECT * FROM token WHERE doctorId IN (SELECT id FROM doctor WHERE email='$email')";
    $tokenCheckResult = $Link->query($tokenCheckQuery);
    //num_rows-это метод объекта mysqli_result, который возвращает количество строк т.е 1 польз-ель с указанным email и паролем
    if ($result->num_rows == 1) {
        // Если пользователь существует

        if ($tokenCheckResult->num_rows == 1) {
            // Если токен существует, обновляем его

            // Генерируем новый токен
            $token = generateToken();

            // Обновляем токен в базе данных
            $timeToValid = date('Y-m-d\TH:i:s.u');
            $doctorId = $result->fetch_assoc()['id'];
            $tokenUpdateResult = $Link->query("UPDATE token SET value='$token', createTime='$timeToValid' WHERE doctorId='$doctorId'");

            if (!$tokenUpdateResult) {
                // В случае ошибки обновления токена, возвращаем статус 500 и сообщение об ошибке
                setHTTPSStatus("500", $Link->error);
            } else {
                // Возвращаем успешный ответ с обновленным токеном
                echo json_encode(['token' => $token]);
            }
        } else {
            // Если токен не существует, создаем новый токен

            // Генерируем новый токен
            $token = generateToken();

            // Добавляем токен в базу данных
            $timeToValid = date('Y-m-d\TH:i:s.u');
            $doctorId = $result->fetch_assoc()['id'];
            $tokenInsertResult = $Link->query("INSERT INTO token(value, doctorId, createTime) VALUES('$token', '$doctorId', '$timeToValid')");

            if (!$tokenInsertResult) {
                // В случае ошибки вставки токена, возвращаем статус 500 и сообщение об ошибке
                setHTTPSStatus("500", $Link->error);
            } else {
                // Возвращаем успешный ответ с новым токеном
                echo json_encode(['token' => $token]);
            }
        }
    } else {
        // Если пользователя с указанными данными не найден, возвращаем статус 401 (Unauthorized)
        setHTTPSStatus("401", "Unauthorized");
    }
}
?>

