<?php
include_once "helpers/headers.php";

function login($requestData) {
    global $Link;
 
    $email = $requestData->body->email;
    $password = hash("sha1", $requestData->body->password);

    $result = $Link->query("SELECT * FROM doctor WHERE email='$email' AND password='$password'");

    $tokenCheckQuery = "SELECT * FROM token WHERE doctorId IN (SELECT id FROM doctor WHERE email='$email')";
    $tokenCheckResult = $Link->query($tokenCheckQuery);
    //num_rows-это метод объекта mysqli_result, который возвращает количество строк т.е 1 польз-ель с указанным email и паролем
    if ($result->num_rows == 1) {

        if ($tokenCheckResult->num_rows == 1) {
            // Если токен существует Генерируем новый токен
            $token = generateToken();

            // Обновляем токен в базе данных
            $timeToValid = date('Y-m-d\TH:i:s.u');
            $doctorId = $result->fetch_assoc()['id'];
            $tokenUpdateResult = $Link->query("UPDATE token SET value='$token', createTime='$timeToValid' WHERE doctorId='$doctorId'");

            if (!$tokenUpdateResult) {
                setHTTPSStatus("500", $Link->error);
            } else {
                echo json_encode(['token' => $token]);
            }
        } else {
            // Если токен не существует тоже генерируем новый токен
            $token = generateToken();

            // Добавляем токен в бд
            $timeToValid = date('Y-m-d\TH:i:s.u');
            $doctorId = $result->fetch_assoc()['id'];
            $tokenInsertResult = $Link->query("INSERT INTO token(value, doctorId, createTime) VALUES('$token', '$doctorId', '$timeToValid')");

            if (!$tokenInsertResult) {
                setHTTPSStatus("500", $Link->error);
            } else {
                echo json_encode(['token' => $token]);
            }
        }
    } else {
        setHTTPSStatus("401", "Unauthorized");
    }
}