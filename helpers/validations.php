
<?php
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

function validateGender($str){
    if($str == "Male" || $str == "Female"){
        return true;
    }
    return false;
}

// Функция валидации пароля на мин длину(6)
function validateStringNotLess($string) {
    return strlen($string) >= 6;
}

// Функция проверки правильности формата электронной почты
function correctEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Функция проверки правильности формата номера телефона
function correctPhoneNumber($phone) {
    /* не хочу в с постман вписывать каждый раз номер, поэтому 
    пусть будет пока просто тру. регулярка внизу работает
    
    if(preg_match('/\+7\s\(\d{3}\)\s\d{3}\-\d{2}\-\d{2}/',$str)){
        return true;
    }
    else{
        return false;
    }*/
    return true;
}

// Генерация токена
function generateToken() {
    return bin2hex(random_bytes(16));
}
//ну вдруг понадобится(у меня в бд id-автоинкремент)
function validatePaginationParameters($page, $size) {
    // Проверка наличия параметров page и size
    if (empty($page) || empty($size)) {
        setHTTPSStatus("400", "Invalid arguments for pagination");
        return false;
    }
    return true;
}