<?php 
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


// Проверка прав на редактирование
function hasEditPermission($doctorId) {
    global $Link;
    $token = explode(' ', getallheaders()['Authorization'])[1];
    $query = "SELECT doctorId FROM token WHERE value='$token'";
    $currentDoctorId = $Link->query($query)->fetch_assoc()['doctorId'];
    return $doctorId == $currentDoctorId;
}
