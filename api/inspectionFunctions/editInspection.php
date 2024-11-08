<?php

function editInspection($id, $requestData) {

    // Проверка наличия осмотра с указанным идентификатором
    $inspectionData = getInspectionById($id);
    if (!$inspectionData) {
        setHTTPSStatus("404", "Inspection not found");
        return;
    }

    // Проверка прав доктора на редактирование осмотра
    if (!hasEditPermission($inspectionData['idDoctor'])) {
        setHTTPSStatus("403", "You don't have permission to edit this inspection");
        return;
    }

    // Проверка присутствия и заполненности обязательных полей
    $requiredFields = ['conclusion', 'anamnesis', 'treatment', 'complaints', 'diagnoses'];
    if (!validateRequiredFields($requiredFields, $requestData->body)) {
        return;
    }

    // Проверка наличия одного основного диагноза и корректности `icdDiagnosisId` в icd10
    if (!validateDiagnoses($requestData)) {
        return;
    }

    //валидация зависимых полей для `conclusion`
    $conclusion = $requestData->body->conclusion;
    $nextVisitDate = $requestData->body->nextVisitDate ?? null;
    $deathDate = $requestData->body->deathDate ?? null;
    if (!validateConclusionFields($conclusion, $nextVisitDate, $deathDate)) {
        return;
    }

    // Проверка возможности изменения заключения "Death"
    if (!canEditDeathConclusion($inspectionData, $conclusion, $id)) {
        return;
    }

    // Очистка полей в зависимости от заключения
    switch ($conclusion) {
        case "Disease":
            $deathDate = null; 
            break;
        case "Death":
            $nextVisitDate = null;
            break;
        case "Recovery":
            $nextVisitDate = null;
            $deathDate = null;
            break;
    }

    // Обновление полей осмотра
    $updateFields = [
        "anamnesis" => $requestData->body->anamnesis,
        "complaints" => $requestData->body->complaints,
        "treatment" => $requestData->body->treatment,
        "conclusion" => $conclusion,
        "nextVisitDate" => $nextVisitDate,
        "deathDate" => $deathDate
    ];

    if (!updateInspection($id, $updateFields)) {
        return;
    }

    // Обновляем диагнозы
    if (!updateDiagnoses($id, $requestData->body->diagnoses)) {
        return;
    }

    setHTTPSStatus("200", "Inspection updated successfully");
}

// Проверка возможности редактирования заключения "Death". если смерть существует у данного пациента 
//и этот осмотр-не этот, то ищем есть ли у этого пациента ещё заключения со смертью
function canEditDeathConclusion($inspectionData, $newConclusion, $currentInspectionId) {
    global $Link;
    $patientId = $inspectionData['idPatient'];

    if ($newConclusion === "Death" && $inspectionData['conclusion'] !== "Death") {
        $query = "SELECT COUNT(*) as count FROM inspection WHERE idPatient = '$patientId' AND conclusion = 'Death' AND id != '$currentInspectionId'";
        $deathInspectionCount = $Link->query($query)->fetch_assoc()['count'];
        if ($deathInspectionCount > 0) {
            setHTTPSStatus("403", "Cannot set conclusion to 'Death' for this inspection, as another death inspection exists for this patient.");
            return false;
        }
    }
    return true;
}



// Получение данных осмотра по ID
function getInspectionById($id) {
    global $Link;
    $query = "SELECT * FROM inspection WHERE id='$id'";
    return $Link->query($query)->fetch_assoc();
}


// Проверка обязательных полей для этого поинта
function validateRequiredFields($requiredFields, $data) {
    foreach ($requiredFields as $field) {
        if (!isset($data->$field)) {
            setHTTPSStatus("400", "Missing required field: $field");
            return false;
        }
    }
    return true;
}

// Проверка существования айдишников диагнозов в icd10 и наличия
// одного основного диагноза в списке
function validateDiagnoses($requestData) {
    global $Link;
    $diagnoses=$requestData->body->diagnoses;

    foreach ($diagnoses as $diagnosis) {

        if (!checkMainDiagnosisAndValidType($requestData->body)) {
            return false;
        }

        $icdDiagnosisId = $diagnosis->icdDiagnosisId;
        $query = "SELECT id FROM icd10 WHERE id = '$icdDiagnosisId'";
        $icdCheckResult = $Link->query($query);
        
        // Проверка существования icdDiagnosisId в таблице icd10
        if ($icdCheckResult->num_rows === 0) {
            setHTTPSStatus("400", "Invalid icdDiagnosisId: $icdDiagnosisId does not exist in icd10");
            return false;
        }
    }

    return true;
}

function updateDiagnoses($inspectionId, $diagnoses) {
    global $Link;
    // Удаляем все диагнозы для указанного осмотра, 
    //тк количество диагнозов во время редактирования может меняться
    $deleteQuery = "DELETE FROM diagnosis WHERE idInspection='$inspectionId'";
    if ($Link->query($deleteQuery) !== TRUE) {
        setHTTPSStatus("500", "Error deleting existing diagnoses: " . $Link->error);
        return false;
    }

    // Добавляем новые диагнозы
    foreach ($diagnoses as $diagnosis) {
        $icdDiagnosisId = $diagnosis->icdDiagnosisId;
        $description = $diagnosis->description;
        $type = $diagnosis->type;

        // Проверка существования icdDiagnosisId в таблице icd10 и получение code и name
        $icdQuery = "SELECT mkb_code, mkb_name FROM icd10 WHERE id = '$icdDiagnosisId'";
        $icdResult = $Link->query($icdQuery);

        if (!$icdResult) {
            setHTTPSStatus("500", "Database error: " . $Link->error);
            return false;
        }
        
        if ($icdResult->num_rows === 0) {
            setHTTPSStatus("400", "Invalid icdDiagnosisId: $icdDiagnosisId does not exist in icd10");
            return false;
        }

        // Получаем code и name из результата
        $icdData = $icdResult->fetch_assoc();
        $code = $icdData['mkb_code'];
        $name = $icdData['mkb_name'];
        $createTime = date('Y-m-d\TH:i:s.u');


        $insertQuery = "INSERT INTO diagnosis (idInspection, icdDiagnosisId, description, type, code, name, createTime) 
                        VALUES ('$inspectionId', '$icdDiagnosisId', '$description', '$type', '$code', '$name', '$createTime')";

        if ($Link->query($insertQuery) !== TRUE) {
            setHTTPSStatus("500", "Error inserting diagnosis: " . $Link->error);
            return false;
        }
    }

    return true;
}

// Обновление полей осмотра
function updateInspection($id, $fields) {
    global $Link;
    $updateQuery = "UPDATE inspection SET ";
    foreach ($fields as $key => $value) {
        if (!is_null($value)) {
            $updateQuery .= "$key = '$value', ";
        }
    }
    //Rtrim Удаляет пробелы (или другие символы) из конца строки. 
    $updateQuery = rtrim($updateQuery, ", ") . " WHERE id='$id'";
    if ($Link->query($updateQuery) !== TRUE) {
        setHTTPSStatus("500", "Error updating inspection: " . $Link->error);
        return false;
    }
    return true;
}
