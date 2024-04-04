<?php
include_once "helpers/headers.php";

function login($requestData) {
    global $Link;

    // Извлечение данных из запроса
    $email = $requestData->body->email;
    $password = hash("sha1", $requestData->body->password);

    // Проверка наличия пользователя с указанным email и паролем
    $result = $Link->query("SELECT * FROM doctor WHERE email='$email' AND password='$password'");
    //num_rows-это метод объекта mysqli_result, который возвращает количество строк т.е 1 польз-ель с указанным email и паролем
    if ($result->num_rows == 1) {
        // Генерация токена для пользователя
        $token = generateToken();
        
        // Обновление токена в базе данных
        $timeToValid = date('Y-m-d\TH:i:s.u');
        $doctorId = $result->fetch_assoc()['id'];
        $tokenUpdateResult = $Link->query("UPDATE token SET value='$token', createTime='$timeToValid' WHERE doctorId='$doctorId'");
        
        if (!$tokenUpdateResult) {
            // В случае ошибки обновления токена, возвращаем статус 500 и сообщение об ошибке
            setHTTPSStatus("500", $Link->error);
        } else {
            // Возвращаем успешный ответ с токеном
            echo json_encode(['token' => $token]);
        }
    } else {
        // Если пользователя с указанными данными не найдено, возвращаем статус 401 (Unauthorized)
        setHTTPSStatus("401", "Unauthorized");
    }
}
