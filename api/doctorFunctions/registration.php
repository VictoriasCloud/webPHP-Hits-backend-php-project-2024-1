<?php

// Функция регистрации доктора
function registerDoctor($requestData) {
    global $Link;

    // Извлечение данных из запроса("sha1": Это алгоритм хэширования, который
    // используется для преобразования входной строки в хэш для обеспечения безопасности пароля.)
    $password = hash("sha1", $requestData->body->password);
    $name = $requestData->body->name;
    $email = $requestData->body->email;
    $birthday = $requestData->body->birthday;
    $gender = $requestData->body->gender;
    $phone = $requestData->body->phone;
    $speciality = $requestData->body->speciality;

    // Проверка валидности данных
    $validationErrors = validateDoctorData($password, $name, $email, $gender, $phone);

    // Если есть ошибки валидации, возвращаем соответствующий статус и сообщение
    if (!empty($validationErrors)) {
        $validationMessage = "";
        foreach ($validationErrors as $err) {
            $validationMessage .= "$err[0]: $err[1] \n";
        }
        setHTTPSStatus("400", $validationMessage);
        return;
    }

    // Генерация ID и токена
    //не надо,поставила автоинкрементом $id = generate_uuid();
    $token = generateToken();

    // Вставка данных в базу данных
    $userInsertResult = $Link->query("INSERT INTO doctor(name, password, email, birthday, gender, phone, speciality) VALUES('$name', '$password', '$email', '$birthday',  '$gender', '$phone', '$speciality')");
    if (!$userInsertResult) {
        if ($Link->errno == 1062) {
            setHTTPSStatus("409", "Адрес электронной почты '$email' уже занят");
            return;
        }
    } else {
        // Вставка токена в базу данных
        echo "ураааа";
        $timeToValid = date('Y-m-d\TH:i:s.u');
        $doctorInfo=$Link->query("SELECT email, id FROM doctor WHERE email='$email'")->fetch_assoc();
        $doctorId=$doctorInfo['id'];
        $tokenInsertResult = $Link->query("INSERT INTO token(value, doctorId, createTime) VALUES('$token', '$doctorId', '$timeToValid')");

        if (!$tokenInsertResult) {
            // В случае ошибки вставки токена, возвращаем статус 500 и сообщение об ошибке
            setHTTPSStatus("500", $Link->error);
        } else {
            // Возвращаем успешный ответ с токеном
            echo "ураааа";
            echo json_encode(['token' => $token]);
        }
    }
}

// Функция валидации данных доктора
function validateDoctorData($password, $name, $email, $gender, $phone) {
    $validationErrors = [];

    if (!validateStringNotLess($password)) {
        $validationErrors[] = ["password", "Пароль менее 6 символов"];
    }

    if (!correctEmail($email)) {
        $validationErrors[] = ["email", "Некорректный адрес электронной почты"];
    }

    if (!correctPhoneNumber($phone)) {
        $validationErrors[] = ["phone", "Некорректный номер телефона"];
    }

    if (!validateName($name)) {
        $validationErrors[] = ["name", "Имя менее 1 символа"];
    }

    if (!validateGender($gender)) {
        $validationErrors[] = ["gender", "Некорректное значение пола"];
    }

    return $validationErrors;
}

