<?php

function editInspection($requestData) {
    global $Link;
    $id = $_GET['id']; 
    // Проверка авторизации пользователя
    $checkTokenResult = checkToken($Link);
    if (!$checkTokenResult) {
        setHTTPSStatus("401", "Unauthorized");
        return;
    }

    // Проверка наличия осмотра с указанным идентификатором
    $checkInspectionQuery = "SELECT * FROM inspection WHERE id='$id'";
    $checkInspectionResult = $Link->query($checkInspectionQuery);

    if ($checkInspectionResult->num_rows == 0) {
        // Если осмотр не найден, возвращаем статус 404 (Not Found)
        setHTTPSStatus("404", "Not Found");
        return;
    }

    // Проверка прав на редактирование (автор ли пользователь осмотра)
    $inspectionData = $checkInspectionResult->fetch_assoc();
    $doctorId = $inspectionData['doctorId']; // Идентификатор врача, создавшего осмотр

    // Получаем идентификатор пользователя из токена
    $token=explode(' ', getallheaders()['Authorization'])[1];
    $checkTokenQuery = "SELECT * FROM token WHERE value='$token'";
    $userId = $Link->query($checkTokenQuery)->fetch_assoc()['userId'];

    if ($doctorId != $userId) {
        // Если пользователь не является автором осмотра, возвращаем статус 403 (Forbidden)
        setHTTPSStatus("403", "User doesn't have editing rights (not the inspection author)");
        return;
    }
    // Обновляем данные осмотра
    $anamnesis = $requestData->body->anamnesis;
    $complaints = $requestData->body->complaints;
    $treatment = $requestData->body->treatment;
    $conclusion = $requestData->body->conclusion;
    $nextVisitDate = $requestData->body->nextVisitDate;
    $deathDate = $requestData->body->deathDate;

    // Обновляем осмотр в базе данных
    $updateInspectionQuery = "UPDATE inspection SET anamnesis='$anamnesis', complaints='$complaints', treatment='$treatment', 
                                conclusion='$conclusion', nextVisitDate='$nextVisitDate', deathDate='$deathDate'
                                WHERE id='$id'";

    if ($Link->query($updateInspectionQuery) === TRUE) {
        // Если обновление прошло успешно, возвращаем статус 200 (Success)
        setHTTPSStatus("200", "Success");
    } else {
        // Если произошла ошибка при обновлении, возвращаем статус 500 (InternalServerError)
        setHTTPSStatus("500", "InternalServerError");
        return;
    }

    // Обновление информации о диагнозах
    if (isset($requestData->body->diagnoses)) {
        foreach ($requestData->body->diagnoses as $diagnosis) {
            $icdDiagnosisId = $diagnosis->icdDiagnosisId;
            $description = $diagnosis->description;
            
            $type = $diagnosis->type;

            // Обновляем информацию о диагнозе
            $updateDiagnosisQuery = "UPDATE diagnosis SET description='$description', type='$type' WHERE id='$icdDiagnosisId'";

            if ($Link->query($updateDiagnosisQuery) !== TRUE) {
                // Если произошла ошибка при обновлении диагноза, возвращаем статус 500 (InternalServerError)
                setHTTPSStatus("500", "InternalServerError");
                return;
            }
        }
    }

    // Возвращаем значение $requestData->body->anamnesis
    return $anamnesis;
}
