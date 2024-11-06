<?php
function CreateInspectionForSpecifiedPatient($Link, $patientId, $requestData) {

    // Проверка существования пациента
    $patientCheck = $Link->query("SELECT * FROM patient WHERE id='$patientId'");
    if ($patientCheck->num_rows !== 1) {
        setHTTPSStatus("404", "Patient not found");
        return;
    }

    // Проверка на существование осмотра с заключением смееерть
    if (!checkConclusionWithDeath($patientId)) {
        return 0;
    }

    //Извлечение данных
    $date = $requestData->date;
    $createTime = date('Y-m-d\TH:i:s.u');
    // Проверка, что дата не больше текущего времени
    if (!checkCreateTimeAndPresentTime($date)) {
        return;  
    }

    $anamnesis = $requestData->anamnesis;
    $complaints = $requestData->complaints;
    $treatment = $requestData->treatment;
    $conclusion = $requestData->conclusion;
    $nextVisitDate = $requestData->nextVisitDate ?? null;
    $deathDate = $requestData->deathDate ?? null;
    $previousInspectionId = $requestData->previousInspectionId ?? null;
    $diagnoses = $requestData->diagnoses ?? [];


    // Валидация на наличие диагноза и дат для каждого типа заключения
    if (!validateConclusionLogic($conclusion, $nextVisitDate, $deathDate, $patientId)) {
        return;
    }

    // Очистка дат в зависимости от типа заключения
    if ($conclusion === "Death") {
        $nextVisitDate = null; 
    } elseif ($conclusion === "Disease") {
        $deathDate = null; 
    } elseif ($conclusion === "Recovery") {
        $nextVisitDate = null;
        $deathDate = null; 
    }

    // Проверка наличия основного диагноза (одного и только одного типа "Main") для всех типов диагнозов
    if (!empty($diagnoses) && !checkMainDiagnosisCount($requestData)) {
        return;
    }

    // Определение врача чтобы вставить его пйди в осмотр
    $token = explode(' ', getallheaders()['Authorization'])[1];
    $checkTokenQuery = "SELECT * FROM token WHERE value='$token'";
    $idDoctor = $Link->query($checkTokenQuery)->fetch_assoc()['doctorId'];

    // Вставка осмотра
    $idInspection = insertInspection($Link, $date, $createTime, $anamnesis, $complaints, $treatment, $conclusion, $nextVisitDate, $deathDate, $previousInspectionId, $idDoctor, $patientId);

    if ($idInspection) {
        if (!insertDiagnosis($requestData, $idInspection, $createTime)) {
            return;
        }
        if (!insertConsultation($requestData, $idInspection, $createTime, $idDoctor, $patientId)) {
            return;
        }
        echo json_encode(['id' => $idInspection]);
        setHTTPSStatus("200");
    } else {
        return;
    }
    
}


function insertInspection($Link, $date, $createTime, $anamnesis, $complaints, $treatment, $conclusion, $nextVisitDate, $deathDate, $previousInspectionId, $idDoctor, $idPatient) {
    // Устанавливаем значение hasChain для нового осмотра на false
    //логика такрва, что hasChain=true толко в том случае, 
    //если создаётся первый дочерний осмотр у осмотра. определяем, что первый по previousInspectionId=0
    $hasChain = 0; 

    // Если previousInspectionId не пустой, выполняем проверку и обновление hasChain для предыдущего осмотра
    if (!empty($previousInspectionId)) {
        // Проверяем существование осмотра с данным previousInspectionId
        $checkPreviousInspection = $Link->query("SELECT id, hasChain FROM inspection WHERE id = '$previousInspectionId'");
        
        if ($checkPreviousInspection->num_rows > 0) {
            $previousInspection = $checkPreviousInspection->fetch_assoc();

            // Обновляем hasChain для родительского осмотра на true, если это первый дочерний осмотр
            if ($previousInspection['hasChain'] == 0) {
                $updatePrevious = $Link->query("UPDATE inspection SET hasChain = 1 WHERE id = '$previousInspectionId'");
                if (!$updatePrevious) {
                    setHTTPSStatus("500", "Failed to update hasChain for previous inspection: " . $Link->error);
                    return 0;
                }
            }
        } else {
            setHTTPSStatus("400", "Previous inspection with ID $previousInspectionId not found.");
            return 0;
        }
    }

    // Создание нового осмотра с hasChain = 0 и (возможно) previousInspectionId
    $insertQuery = "INSERT INTO inspection (date, createTime, anamnesis, complaints, treatment, conclusion, nextVisitDate, deathDate, previousInspectionId, hasChain, idDoctor, idPatient) 
                    VALUES ('$date', '$createTime', '$anamnesis', '$complaints', '$treatment', '$conclusion', '$nextVisitDate', '$deathDate', '$previousInspectionId', $hasChain, '$idDoctor', '$idPatient')";


    if ($Link->query($insertQuery)) {
    } else {
        setHTTPSStatus("500", "Error occurred while creating inspection: " . $Link->error);
        return 0;
    }

    // Получаем айди нового осмотра
    $idInspection = $Link->insert_id;
    return $idInspection;
}


function insertDiagnosis($requestData, $inspectionId, $createTime) {
    global $Link;

    if (isset($requestData->diagnoses) && is_array($requestData->diagnoses)) {
        foreach ($requestData->diagnoses as $diagnosis) {
            $icdDiagnosisId = $diagnosis->icdDiagnosisId;
            $description = $diagnosis->description;
            $type = $diagnosis->type;
            
            // Получение имени и кода из таблицы icd10
            $icdData = $Link->query("SELECT mkb_name, mkb_code FROM icd10 WHERE id='$icdDiagnosisId'")->fetch_assoc();
            
            if ($icdData) {
                $name = $icdData['mkb_name'];
                $code = $icdData['mkb_code'];
            } else {
                setHTTPSStatus("400", "ICD diagnosis not found for id: $icdDiagnosisId");
                return false;
            }
            
            // Вставляем диагноз в таблицу
            $diagnosisInsertResult = $Link->query("INSERT INTO diagnosis (icdDiagnosisId, description, type, idInspection, createTime, name, code) VALUES ('$icdDiagnosisId', '$description', '$type', '$inspectionId', '$createTime', '$name', '$code')");
            
            if (!$diagnosisInsertResult) {
                setHTTPSStatus("500", "InternalServerError: " . $Link->error);
                return false;
            }
            
        }
    }
    return true;
}


function insertConsultation($requestData, $inspectionId, $createTime, $idDoctor, $idPatient) {
    global $Link;
    $consultations = $requestData->consultations ?? [];
    if (isset($consultations) && is_array($consultations)) {
        $uniqueSpecialities = array_unique(array_column($consultations, 'specialityId'));
        
        // Проверка на уникальность специальностей, ведь нельзя несколько консультаций на 1 специальность
        if (count($uniqueSpecialities) !== count($consultations)) {
            setHTTPSStatus("400", "An examination cannot have multiple consultations with the same doctor's specialty.");
            return false;
        }
        
        foreach ($consultations as $consultation) {
            $specialityId = $consultation->specialityId;
            
            // Проверка наличия и заполненности content
            if (empty($consultation->comment->content)) {
                setHTTPSStatus("400", "Consultation comment content is required and cannot be empty.");
                return false;
            }
            
            // Вставка консультации в таблицу
            $consultationInsertResult = $Link->query("INSERT INTO consultation (inspectionId, specialityId, createTime, idDoctor, idPatient) VALUES ('$inspectionId', '$specialityId', '$createTime', '$idDoctor', '$idPatient')");
            if (!$consultationInsertResult) {
                setHTTPSStatus("500", "Error inserting consultation: " . $Link->error);
                return false;
            }

            // Вставка комментария для консультации
            $idConsultation = $Link->insert_id;
            if (!insertComment($Link, $consultation, $idConsultation, $idDoctor)) {
                setHTTPSStatus("500", "Error inserting comment for consultation.");
                return false;
            }
        }
        return true;
    }
    setHTTPSStatus("500", "No consultations provided.");
    return false;
}

function insertComment($Link, $consultation, $idConsultation, $doctorId) {
    $commentContent = $consultation->comment->content ?? null;
    
    //наличие контента
    if (is_null($commentContent) || trim($commentContent) === '') {
        setHTTPSStatus("400", "The comment field is empty.");
        return false;
    }

    $createTime = date('Y-m-d\TH:i:s.u');
    $commentInsertResult = $Link->query("INSERT INTO comments (createTime, content, authorId, idConsultation) VALUES ('$createTime', '$commentContent', '$doctorId', '$idConsultation')");
    
    if (!$commentInsertResult) {
        setHTTPSStatus("500", "Error inserting comment: " . $Link->error);
        return false;
    }

    return true;
}
