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

    if (!validateStringNotLess($password, 6)) {
        $validationErrors[] = ["password", "Пароль менее 6 символов"];
    }

    if (!correctEmail($email)) {
        $validationErrors[] = ["email", "Некорректный адрес электронной почты"];
    }

    if (!correctPhoneNumber($phone)) {
        $validationErrors[] = ["phone", "Некорректный номер телефона"];
    }

    if (!validateStringNotLess($name, 1)) {
        $validationErrors[] = ["name", "Имя менее 1 символа"];
    }

    if (!validateGender($gender)) {
        $validationErrors[] = ["gender", "Некорректное значение пола"];
    }

    return $validationErrors;
}

// Функция валидации строки на минимальную длину
function validateStringNotLess($string, $minLength) {
    return strlen($string) >= $minLength;
}

// Функция проверки правильности формата электронной почты
function correctEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Функция проверки правильности формата номера телефона
function correctPhoneNumber($phone) {
    // Ваша реализация проверки формата номера телефона
    return true; // Заглушка, замените на свою реализацию
}

// Функция валидации пола (допустимые значения "Male" и "Female")
function validateGender($gender) {
    return in_array($gender, array('Male', 'Female'));
}

// Генерация токена
function generateToken() {
    return bin2hex(random_bytes(16));
}

function generate_uuid() {
    // Генерация случайных байтов
    $data = random_bytes(16);

    // Установка версии 4
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    // Преобразование байтов в строк
}
?>