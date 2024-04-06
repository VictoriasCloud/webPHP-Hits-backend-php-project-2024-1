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
            setHTTPSStatus("200", "Patient was registered");
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

function checkToken($Link){

    $token=explode(' ', getallheaders()['Authorization'])[1];

    if (!isset($token)) {
        // Если токен не передан, возвращаем статус 400 (Bad Request) с сообщением об ошибке
        setHTTPSStatus("400", "Token is missing/Bad Request");
        return 0;
    }
    // Проверяем, существует ли токен в базе данных
    $checkTokenQuery = "SELECT * FROM token WHERE value='$token'";
    $checkTokenResult = $Link->query($checkTokenQuery);

    // Если токен существует
    if($checkTokenResult->num_rows==1){
        $doctorId = $checkTokenResult->fetch_assoc()['doctorId'];

        // Проверяем, существует ли доктор с указанным id
        $checkDoctorQuery = "SELECT * FROM doctor WHERE id='$doctorId'";
        $checkDoctorResult = $Link->query($checkDoctorQuery);
        if ($checkDoctorResult->num_rows == 1) {
            // Если доктор существует И ТОЛЬКО 1, возвращаем результат проверки
            return true;
        }
        else {
            // Если доктор не существует, выдаем сообщение об ошибке и удаляем токен
            setHTTPSStatus("400", "Токен принадлежит несуществующему врачу и будет удален");
            $deleteTokenQuery = "DELETE FROM token WHERE value='$token'";
            $Link->query($deleteTokenQuery);
            return false;
        }
    }
    else {
        setHTTPSStatus("401", "Unauthorized");
        return false;
    }
}

function validatePatientData($name, $birthday, $gender){

    if (!(validateName($name)||validateGender($gender)||validateBirthday($birthday))){
        return false;
    }
    return true;
}


// Функция для валидации дня рождения
function validateBirthday($birthday) {
    // Проверяем корректность формата даты и времени
    $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $birthday);
    if (!$dateTime || $dateTime->format('Y-m-d\TH:i:s.u\Z') !== $birthday) {
        return false;
    }
    // Проверяем, что год не превышает текущий год
    $currentYear = date('Y');
    if ($dateTime->format('Y') > $currentYear) {
        return false;
    }
    return true;
}

function validateName($name) {
    // Проверка, что строка больше 1 символа,является строкой, содержит только буквы
    if ((!is_string($name) || strlen($name) < 2)||(!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s ]+$/', $name))) {
        return false;
    }
    return true;
}

function validateGender(){
    if($str == "Male" || $str == "Female"){
        return true;
    }
    return false;
}

?>
