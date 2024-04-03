<?php

// Обработка POST запроса на регистрацию врача
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Проверка типа контента на JSON
    if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
        
        // Получение тела запроса
        $request_body = file_get_contents('php://input');
        
        // Декодирование JSON данных
        $data = json_decode($request_body, true);
        
        // Проверка наличия обязательных полей
        if (isset($data['name'], $data['password'], $data['email'], $data['gender'], $data['speciality'])) {
            
            // Проверка валидности полей
            if (validateFields($data)) {
                
                // Выполнение регистрации врача
                $registration_result = registerDoctor($data);
                
                // Отправка соответствующего ответа
                if ($registration_result['success']) {
                    http_response_code(200);
                    header('Content-Type: text/plain');
                    echo json_encode(array('token' => $registration_result['token']));
                    exit();
                } else {
                    http_response_code(500);
                    header('Content-Type: application/json');
                    echo json_encode(array('status' => 'error', 'message' => $registration_result['message']));
                    exit();
                }
                
            } else {
                // Ошибка - невалидные данные
                http_response_code(400);
                exit();
            }
            
        } else {
            // Ошибка - недостаточно аргументов
            http_response_code(400);
            exit();
        }
        
    } else {
        // Ошибка - неверный тип контента
        http_response_code(400);
        exit();
    }
    
} else {
    // Ошибка - неверный метод запроса
    http_response_code(405);
    exit();
}

// Функция регистрации врача
function registerDoctor($data) {
    // Здесь должна быть ваша логика регистрации врача
    // Например, вам нужно сохранить данные в базу данных, выполнить проверки и т.д.
    
    // Предположим, что регистрация успешно выполнена и токен сгенерирован
    $token = generateToken(); // Генерация токена, ваша собственная логика
    
    // Возвращаем результат регистрации
    return array('success' => true, 'token' => $token);
}

// Функция генерации токена
function generateToken() {
    // Здесь должна быть ваша логика генерации токена
    return 'generated_token'; // Возвращаем фиксированный токен в качестве примера
}

// Функция валидации полей
function validateFields($data) {
    // Проверка валидности полей данных
    
    // Проверка на соответствие длины имени
    if (isset($data['name']) && (strlen($data['name']) < 1 || strlen($data['name']) > 1000)) {
        return false;
    }
    
    // Проверка на соответствие длины пароля
    if (isset($data['password']) && strlen($data['password']) < 6) {
        return false;
    }
    
    // Проверка валидности email
    if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Проверка валидности даты рождения (если предоставлена)
    if (isset($data['birthday']) && !validateDateTime($data['birthday'])) {
        return false;
    }
    
    // Проверка валидности номера телефона (если предоставлен)
    if (isset($data['phone']) && !validatePhoneNumber($data['phone'])) {
        return false;
    }
    
    return true;
}

// Функция проверки валидности формата даты и времени
function validateDateTime($dateTime) {
    $format = 'Y-m-d\TH:i:s.u\Z';
    $d = DateTime::createFromFormat($format, $dateTime);
    return $d && $d->format($format) === $dateTime;
}

// Функция проверки валидности номера телефона
function validatePhoneNumber($phoneNumber) {
    // Здесь может быть ваша логика проверки номера телефона
    // Возвращаем true для примера
    return true;
}

?>
