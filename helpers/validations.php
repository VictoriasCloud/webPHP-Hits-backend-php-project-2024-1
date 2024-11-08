<?php
// Функция валидации данных доктора
function validateDoctorData($password, $name, $email, $gender, $phone, $birthday) {
    $validationErrors = [];

    if (!validatePassword($password)) {
        $validationErrors[] = ["password", "Password less than 6 characters long"];
    }

    if (!validateEmail($email)) {
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
    
    if (!validateBirthday($birthday)) {
        $validationErrors[] = ["birthday", "Invalid birthday format or future date."];
    }

    return $validationErrors;
}

function validateBirthday($birthday) {
    // Попробуем создать объект DateTime из строки
    $date = date_create($birthday);

    // Если дата корректна и не больше тек времени, - true, иначе - false
    if ($date) {
        $currentDate = new DateTime();
        return $date < $currentDate;
    }

    return false;
}




// Функция для преобразования даты в стандартный формат
function updateTimeFormat($dateString) {
    $date = new DateTime($dateString);
    return $date->format('Y-m-d\TH:i:s.u\Z');
}


function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= 6 && preg_match('/[A-Za-z]/', $password) && preg_match('/\d/', $password);
}

function validateName($name) {
    if (!is_string($name) || strlen($name) < 2) {
        return false;
    }

    if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]+$/u', $name)) {
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

function correctPhoneNumber($phone) {
    /* не хочу в с постман вписывать каждый раз номер, поэтому 
    пусть будет пока просто тру. регулярка внизу работает*/
    
    if(preg_match('/\+7\(\d{3}\)\d{3}\-\d{2}\-\d{2}/',$phone)){
        return true;
    }
    else{
        return false;
    }
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

function validateConclusion($conclusion) {
    $allowedValues = ['Disease', 'Recovery', 'Death'];
    
    if (in_array($conclusion, $allowedValues)) {
        return true;
    }
    return false;
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

// Допустимые значения для conclusion. это для файла editIn(файл что выше не подойдёт)
const VALID_CONCLUSIONS = ['Disease', 'Death', 'Recovery'];
function validateConclusionFields($conclusion, $nextVisitDate, $deathDate) {
    
    // Проверка, что conclusion имеет допустимое значение
    if (!in_array($conclusion, VALID_CONCLUSIONS)) {
        setHTTPSStatus("400", "Invalid conclusion value: $conclusion. Allowed values are: " . implode(', ', VALID_CONCLUSIONS));
        return false;
    }

    if ($conclusion === "Disease" && is_null($nextVisitDate)) {
        setHTTPSStatus("400", "Specify the date of the next visit for 'Disease' conclusion");
        return false;
    }
    
    if ($conclusion === "Death" && is_null($deathDate)) {
        setHTTPSStatus("400", "Specify the date of death for 'Death' conclusion");
        return false;
    }

    return true;
}
