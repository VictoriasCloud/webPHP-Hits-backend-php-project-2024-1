<?php

// Функция для создания нового пациента
function createNewPatient($requestData) {
    // Проверяем, передан ли токен
    global $Link;
    $checkTokenResult=checkToken($Link);

    if ($checkTokenResult) {
        // Извлекаем данные пациента из тела запроса
        $name = $requestData->body->name;
        $birthday = $requestData->body->birthday;
        $gender = $requestData->body->gender;
        $createTime=date('Y-m-d\TH:i:s.u');
        // Проверка валидности данных пациента

        if (!validatePatientData($name, $birthday, $gender)) {
            // Если данные пациента невалидны, возвращаем статус 400 (Bad Request) с сообщением об ошибке
            setHTTPSStatus("400", "Invalid arguments");
            return;
        }
        // Проверяем, существует ли пациент с такими же данными
        if (checkPatientExistence($Link, $name, $birthday, $gender)) {
            setHTTPSStatus("409", "Patient already exists");
            return;
        }

        // Вставляем данные пациента в базу данных
        $insertPatientQuery = "INSERT INTO patient(name, birthday, gender, createTime) VALUES('$name', '$birthday', '$gender', '$createTime')";
        $insertPatientResult = $Link->query($insertPatientQuery);

        if ($insertPatientResult) {
            // Если пациент успешно создан, возвращаем статус 200 (OK) с идентификатором созданного пациента
            $patientId = $Link->insert_id;
            setHTTPSStatus("200", "Patient was created id='$patientId'");
        } else {
            // Если произошла ошибка при создании пациента, возвращаем статус 500 (Internal Server Error) с сообщением об ошибке
            setHTTPSStatus("500", $Link->error);
        }
    } /* не надо ошибку прописывать, она в функции есть если че перед фолс
    else {
        // Если токен не найден, возвращаем статус 401 (Unauthorized)
        setHTTPSStatus("401", "Unauthorized");
    }*/
}

function checkPatientExistence($Link, $name, $birthday, $gender) {
    $checkPatientQuery = "SELECT * FROM patient WHERE name='$name' AND birthday='$birthday' AND gender='$gender'";
    $checkPatientResult = $Link->query($checkPatientQuery);
    return $checkPatientResult->num_rows > 0;
}


function validatePatientData($name, $birthday, $gender){

    if (!(validateName($name)||validateGender($gender)||validateBirthday($birthday))){
        return false;
    }
    return true;
}

?>
