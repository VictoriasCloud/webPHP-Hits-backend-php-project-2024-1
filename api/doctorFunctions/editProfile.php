<?php
include_once "helpers/headers.php";

function editProfile($requestData) {
    global $Link;

    $token=(explode(' ', getallheaders()['Authorization'])[1]);
    echo $token;
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

            // Подготавливаем SQL-запрос для обновления профиля и выполняем запрос
            $editProfileQuery = "UPDATE doctor SET email='$email', name='$name', birthday='$birthday', gender='$gender', phone='$phone' WHERE id='$userId'";
            $editProfileResult = $Link->query($editProfileQuery);

            if ($editProfileResult) {
                // Если обновление профиля выполнено успешно, возвращаем статус 200 (OK)
                setHTTPSStatus("200");
            } else {
                // Если произошла ошибка при обновлении профиля, возвращаем статус 500 (Internal Server Error) с сообщением об ошибке
                setHTTPSStatus("500", $Link->error);
            }
        } else {
            // Если пользователей несколько или 0, возвращаем статус 404
            setHTTPSStatus("404", "User not foun/not found");
        }
    } else {
        // Если токен не найден, возвращаем статус 401 (Unauthorized)
        setHTTPSStatus("401", "Unauthorized");
    }
}
?>
