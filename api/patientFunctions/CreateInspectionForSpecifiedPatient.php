<?php

function CreateInspectionForSpecifiedPatient($Link, $requestData) {
    // Проверка токена
    if (!checkToken($Link)) {
        setHTTPSStatus("401", "Unauthorized");
        return;
    }

    $idPatient=$_GET['idPatient'];
    $patientCheck=$Link->query("SELECT * from patient Where id='$idPatient'");
    //проверка, что такой пациент существует
    if ($patientCheck->num_rows!==1){
        setHTTPSStatus("400", "Patient's identifier not found");
        return;
    }

    // Подготовка данных для вставки в базу данных
    $date = $requestData->body->date;
    $anamnesis = $requestData->body->anamnesis;
    $complaints = $requestData->body->complaints;
    $treatment = $requestData->body->treatment;
    $conclusion = $requestData->body->conclusion;
    $nextVisitDate = $requestData->body->nextVisitDate;
    $deathDate =$requestData->body->deathDate;
    $previousInspectionId =$requestData->body->previousId;
    $createTime = date('Y-m-d\TH:i:s.u');
    $date=$createTime;
    //проверяем есть ли мэйн диагноз
    if (checkMainDiagnosisCount($requestData)){

        // Проверка, что дата создания осмотра не больше предыдущего осмотра
        if (!is_null($previousInspectionId)){
            if(!checkDatePreviousInspection($Link, $previousInspectionId, $createTime)){
                return;
            }
        }
        //проверка, что дата создания осмотра не больше настоящего времени
        if(!checkCreateTimeAndPresentTime($createTime)){
            return;
        }
        //проверка заключения
        switch ($conclusion) {
            //при выборе заключения “Болезнь”, необходимо указать дату и время следующего визита,
            case "Disease":
                if (is_null($nextVisitDate)){
                    setHTTPSStatus("400", "Specify the date of the next visit.");
                }
                break;
            // при выборе заключения “Смерть”, необходимо указать дату и время смерти
            case "Death":
                //у пациента не может быть более одного осмотра с заключением “Смерть”;
                if(checkConclusionWithDeath($idPatient)){
                    if (is_null($deathDate)){
                        setHTTPSStatus("400", "Specify the date of death.");
                        return;
                    }
                }
                else{
                    echo "conclusion=bad";
                    return 0;
                }
                break;
            // Ничего не требуется делать при заключении "Выздоровление"
            case "Recovery":
                break;
            default:
                return "Error: Invalid conclusion.";
        }
        $token=explode(' ', getallheaders()['Authorization'])[1];
        $checkTokenQuery = "SELECT * FROM token WHERE value='$token'";
        $idDoctor = $Link->query($checkTokenQuery)->fetch_assoc()['doctorId'];

        $idInspection=insertInspection($Link, $date, $createTime, $anamnesis,$complaints, $treatment,$conclusion, $nextVisitDate, $deathDate, $previousInspectionId, $idDoctor, $idPatient);
        
        if ($idInspection!=0){
            
            if(insertDiagnosis($requestData, $idInspection, $createTime)){
                echo "диагнозы вставлены";
            }
            if(insertConsultation($requestData, $idInspection, $createTime, $idDoctor, $idPatient)){
                echo "консультации вставлены";
            }
        }
        echo "осмотры вставлены";

    }
    return;
}

function checkConclusionWithDeath($idPatient){
    global $Link;
    $countDeath = $Link->query("SELECT COUNT(*) AS death_count FROM inspection WHERE idPatient = '$idPatient' AND conclusion = 'Death'")->fetch_assoc()['death_count'];
    if ($countDeath==0){
        return true;
    }
    setHTTPSStatus("400", "The patient already has a 'death' in the inspection.");
    return false;

}

// Проверка, что дата создания осмотра не больше настоящего времени
function checkCreateTimeAndPresentTime($createTime){
    $presentTime= date('Y-m-d\TH:i:s.u');
    if ($createTime<=$presentTime){
        return true;
    }
    else{
        setHTTPSStatus("400", "Problems with time of inspection");
        return false;
    }
}

// Проверка, что дата создания осмотра не больше предыдущего
function checkDatePreviousInspection($Link, $previousInspectionId, $createTime){
    $previousInspectionResult = $Link->query("SELECT date FROM inspection WHERE id='$previousInspectionId'");
    $previousInspectionDate = $previousInspectionResult;
    if ($previousInspectionDate<$createTime){
        return true;
    }
    setHTTPSStatus("400", "Problems with time of inspection");
    return false;
}

// Проверка наличия хотя бы одного диагноза с типом "Main"
function checkMainDiagnosisCount($requestData){
    $mainDiagnosisCount = 0;

    if (isset($requestData->body->diagnoses) && is_array($requestData->body->diagnoses)) {
        foreach ($requestData->body->diagnoses as $diagnosis) {
            $type = $diagnosis->type;

            if ($type === "Main") {
                $mainDiagnosisCount++;
            }
        }
    }
    if ($mainDiagnosisCount !== 1) {
        setHTTPSStatus("400", "Invalid diagnoses. Inspection must have exactly one Main diagnosis.");
        return false;
    }
    return true;

}

function insertInspection($Link, $date, $createTime, $anamnesis,$complaints, $treatment,$conclusion, $nextVisitDate, $deathDate, $previousInspectionId, $idDoctor, $idPatient){
    // Создание запроса для вставки данных в базу данных
    $insertQuery = "INSERT INTO inspection (date, createTime, anamnesis, complaints, treatment, conclusion, nextVisitDate, deathDate, previousInspectionId, idDoctor, idPatient ) 
                    VALUES ('$date', '$createTime', '$anamnesis', '$complaints', '$treatment', '$conclusion', '$nextVisitDate', '$deathDate', '$previousInspectionId', '$idDoctor', '$idPatient')";

    // Выполнение запроса
    if ($Link->query($insertQuery)) {
        // Отправка успешного статуса
        setHTTPSStatus("200", "Inspection created successfully");
    } else {
        // Отправка статуса ошибки
        setHTTPSStatus("500", "Error occurred while creating inspection");
    }
    // Запрос для поиска idInspection по createTime
    $selectQuery = "SELECT id FROM inspection WHERE createTime = '$createTime'";
    $result = $Link->query($selectQuery);
    // Проверка результатов запроса
    if ($result->num_rows > 0) {
        $idInspection = $result->fetch_assoc()['id'];
        // Возвращаем idInspection
        return $idInspection;
    }
    return 0;
}

function insertDiagnosis($requestData, $inspectionId, $createTime){
    global $Link;
    // Добавляем диагнозы
    if (isset($requestData->body->diagnoses) && is_array($requestData->body->diagnoses)) {
        foreach ($requestData->body->diagnoses as $diagnosis) {
            $icdDiagnosisId = $diagnosis->icdDiagnosisId;
            $description = $diagnosis->description;
            $type = $diagnosis->type;
            $name= $Link->query("SELECT * FROM icd10 WHERE id='$icdDiagnosisId'")->fetch_assoc()['mkb_name'];
            $code= $Link->query("SELECT * FROM icd10 WHERE id='$icdDiagnosisId'")->fetch_assoc()['mkb_code'];

            // Вставляем диагноз в таблицу diagnosis
            $diagnosisInsertResult = $Link->query("INSERT INTO diagnosis(icdDiagnosisId, description, type, idInspection, createTime, name, code) VALUES('$icdDiagnosisId', '$description', '$type', '$inspectionId', '$createTime', '$name', '$code')");
            if (!$diagnosisInsertResult){
                setHTTPSStatus("500", "InternalServerError");
                return false;
            }
            else{
                return true;
            }
        }
    }
}

function insertConsultation($requestData, $inspectionId, $createTime, $idDoctor, $idPatient){
    global $Link;

    $consultations=$requestData->body->consultations;
    // Добавляем консультации 
    if (isset($consultations) && is_array($consultations)) { 
        foreach ($consultations as $consultation) { 
            $specialityId = $consultation->specialityId; 
            //проверка на уникальность специальности
            //array_column() извлекает (specialityId) и формирует новый массив
            //array_unique() удаляет повторяющиеся значения из массива. 
            $uniqueSpecialities = array_unique(array_column($consultations, 'specialityId'));

            if (count($uniqueSpecialities) !== count($consultations)) {
                setHTTPSStatus("400", "An examination cannot have multiple consultations with the same doctor's specialty;");
                return false;
            }

            // Вставляем консультацию в таблицу consultation
            $consultationInsertResult = $Link->query("INSERT INTO consultation(inspectionId, specialityId, createTime, idDoctor, idPatient) VALUES('$inspectionId', '$specialityId', '$createTime', '$idDoctor', '$idPatient')"); 
            if($consultationInsertResult){

                $idConsultation = $Link->query("SELECT id FROM consultation WHERE inspectionId = '$inspectionId' AND specialityId = '$specialityId'")->fetch_assoc()['id'];
                if(insertCommentFromParentId($Link, $consultation, $idConsultation, $idDoctor)){

                    $idParentComment = $Link->query("SELECT id FROM comments WHERE idConsultation = '$idConsultation' AND parentId = 'null'");
                    
                    $resultParentComment = $Link->query("INSERT INTO consultation(idParentComment) VALUES('$idParentComment')"); 
                    if($resultParentComment){
                        setHTTPSStatus("200", "Consultation was inserted successfully.");
                        return true;
                    }
                }
                return false;
            }
        } 
    } 
    setHTTPSStatus("500", $Link->error);
    return false;
}

//функция добавления комментария
function insertCommentFromParentId($Link, $consultation, $idConsultation, $doctorId){
    // Добавляем комментарий для консультации 
    $token=(explode(' ', getallheaders()['Authorization'])[1]);
    //$checkTokenResult = $Link->query("SELECT * FROM token WHERE value='$token'");
    //$doctorId = $checkTokenResult->fetch_assoc()['doctorId'];
    $parentId='null';
    $commentContent = $consultation->comment->content; 
    $commentAuthorId = $doctorId; 

    $queryResult = $Link->query("SELECT name FROM doctor WHERE id='$doctorId'");
    $row = $queryResult->fetch_assoc();
    $commentAuthorName = $row['name'];

    //echo $commentAuthorName;
    if (is_null($commentContent)){
        setHTTPSStatus("400", "The comment field is empty, it was not possible to insert a comment.");
        return 0;
    }
    $createTime= date('Y-m-d\TH:i:s.u');
    echo $idConsultation;
    $commentInsertResult = $Link->query("INSERT INTO comments(createTime, content, authorId, nameAuthor, idConsultation) VALUES('$createTime','$commentContent', '$commentAuthorId', '$commentAuthorName', '$idConsultation')"); 
    if ($commentInsertResult){
        setHTTPSStatus("200", "Comment was inserted successfully.");
        return true;
    }
    setHTTPSStatus("500", $Link->error);
    return false;
}

