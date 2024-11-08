<?php
function registerDoctor($requestData) {
    global $Link;

    // Извлечение данных из запроса
    $password = $requestData->body->password;
    $name = $requestData->body->name;
    $email = $requestData->body->email;
    $birthday = $requestData->body->birthday;
    $gender = $requestData->body->gender;
    $phone = $requestData->body->phone;
    $speciality = $requestData->body->speciality;

    // Хешируем пароль для безопасности
    $hashedPassword = hash("sha1", $password);

    if (!fetchSpeciality($speciality)) {
        return;
    }


    // Валидация данных
    $validationErrors = validateDoctorData($password, $name, $email, $gender, $phone, $birthday);
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

    // Генерация токена
    $token = generateToken();
    $createTime = date('Y-m-d\TH:i:s.u');

    // Вставка данных о докторе в базу
    $insertDoctorQuery = "INSERT INTO doctor(name, password, email, birthday, gender, phone, speciality) 
                          VALUES('$name', '$hashedPassword', '$email', '$birthday', '$gender', '$phone', '$speciality')";
    $userInsertResult = $Link->query($insertDoctorQuery);

    if (!$userInsertResult) {
        if ($Link->errno == 1062) {
            setHTTPSStatus("409", "Email '$email' is already in use.");
        } else {
            setHTTPSStatus("500", "InternalServerError: " . $Link->error);
        }
        return;
    }

    // Получаем ID вставленного врача
    $doctorId = $Link->insert_id;

    // Вставка токена в базу
    $tokenInsertResult = $Link->query("INSERT INTO token(value, doctorId, createTime) VALUES('$token', '$doctorId', '$createTime')");
    if (!$tokenInsertResult) {
        setHTTPSStatus("500", "InternalServerError: " . $Link->error);
    } else {
        echo json_encode(['token' => $token]);
        setHTTPSStatus("200");
    }
}
