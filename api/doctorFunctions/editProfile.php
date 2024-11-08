<?php
include_once "helpers/headers.php";

function editProfile($requestData) {
    global $Link;

    $token=(explode(' ', getallheaders()['Authorization'])[1]);
    // Проверяем наличие токена
    if (!isset($token)) {
        // Если токен не передан, возвращаем статус 400 (Bad Request) с сообщением об ошибке
        setHTTPSStatus("400", "Token is missing(Bad Request)");
        return;
    }
    
    // Получаем данные из тела запроса
    $email = $requestData->body->email;
    $name = $requestData->body->name;
    $birthday = $requestData->body->birthday;
    $gender = $requestData->body->gender;
    $phone = $requestData->body->phone;

    // Валидация данных профиля
    $validationErrors = validateDoctorData("kostil24", $name, $email, $gender, $phone, $birthday);
    if (!empty($validationErrors)) {
        $validationMessage = [];
        foreach ($validationErrors as $err) {
            $validationMessage[] = "$err[0]: $err[1]";
        }
        $formattedMessage = implode("; ", $validationMessage); // Преобразуем массив в строку
        setHTTPSStatus("400", $formattedMessage);
        return;
        // setHTTPSStatus("400", "Invalid params");
        // return;
    }


    // Проверяем, существует ли токен в базе данных
    $checkTokenQuery = "SELECT * FROM token WHERE value='$token'";
    $checkTokenResult = $Link->query($checkTokenQuery);

    if ($checkTokenResult->num_rows == 1) {
        // Если токен существует, получаем идентификатор пользователя
        $userId = $checkTokenResult->fetch_assoc()['doctorId'];

        // Проверяем, существует ли пользователь в базе данных
        $getUserQuery = "SELECT * FROM doctor WHERE id='$userId'";
        $getUserResult = $Link->query($getUserQuery);

        if ($getUserResult->num_rows == 1) {

            $editProfileQuery = "UPDATE doctor SET email='$email', name='$name', birthday='$birthday', gender='$gender', phone='$phone' WHERE id='$userId'";
            $editProfileResult = $Link->query($editProfileQuery);

            if ($editProfileResult) {
                setHTTPSStatus("200");
            } else {
                setHTTPSStatus("500", $Link->error);
            }
        } else {
            // Если пользователей несколько или 0, возвращаем статус 404
            setHTTPSStatus("404", "User not foun/not found");
        }
    } else {
        setHTTPSStatus("401", "Unauthorized");
    }
}

