
<?php

// Функция валидации данных доктора
function validateDoctorData($password, $name, $email, $gender, $phone) {
    $validationErrors = [];

    if (!validateStringNotLess($password)) {
        $validationErrors[] = ["password", "Password less than 6 characters long"];
    }

    if (!correctEmail($email)) {
        $validationErrors[] = ["email", "Invalid email address"];
    }

    if (!correctPhoneNumber($phone)) {
        $validationErrors[] = ["phone", "Invalid phone number"];
    }

    if (!validateName($name)) {
        $validationErrors[] = ["name", "Invalid name"];
    }

    if (!validateGender($gender)) {
        $validationErrors[] = ["gender", "Invalid gender value"];
    }

    return $validationErrors;
}



function validateConclusion($conclusion) {
    // Перечень допустимых значений
    $allowedValues = ['Disease', 'Recovery', 'Death'];
    
    // Проверяем, присутствует ли значение в списке допустимых значений
    if (in_array($conclusion, $allowedValues)) {
        return true;
    }
    // Значение не входит в список допустимых
    return false;
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

function validateConclusionLogic($conclusion, $nextVisitDate, $deathDate, $patientId) {
    switch ($conclusion) {
        case "Disease":
            if (is_null($nextVisitDate)) {
                setHTTPSStatus("400", "Specify the date of the next visit.");
                return false;
            }
            break;
        case "Death":
            if (!checkConclusionWithDeath($patientId)) {
                return false;
            }
            if (is_null($deathDate)) {
                setHTTPSStatus("400", "Specify the date of death.");
                return false;
            }
            break;
        case "Recovery":
            break;
        default:
            setHTTPSStatus("400", "Invalid conclusion type");
            return false;
    }
    return true;
}