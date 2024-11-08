<?php
// Основная функция для получения полной информации об осмотре
function getFullInspectionInfo($inspectionId) {

    $inspectionData = fetchInspectionData($inspectionId);
    if (!$inspectionData) return;

    $patientData = fetchPersonData('patient', $inspectionData['idPatient']);
    if (!$patientData) return;

    $doctorData = fetchPersonData('doctor', $inspectionData['idDoctor']);
    if (!$doctorData) return;

    $diagnoses = fetchDiagnoses($inspectionId);
    if ($diagnoses === false) return;

    $consultations = fetchConsultations($inspectionId);
    if ($consultations === false) return;

    // вся информация об осмотре, хз че такое baseInspectionId но пусть будет. 
    //если это то, к чему относится данный осмотр-то есть previousInspectionId. если это показатель, что
    //данный осмотр-главный, то опять же есть previousInspectionId.
    $inspectionInfo = [
        "id" => $inspectionData['id'],
        "createTime" => $inspectionData['createTime'],
        "date" => $inspectionData['date'],
        "anamnesis" => $inspectionData['anamnesis'],
        "complaints" => $inspectionData['complaints'],
        "treatment" => $inspectionData['treatment'],
        "conclusion" => $inspectionData['conclusion'],
        "nextVisitDate" => $inspectionData['nextVisitDate'],
        "deathDate" => $inspectionData['deathDate'],
        "baseInspectionId" => $inspectionData['baseInspectionId'],
        "previousInspectionId" => $inspectionData['previousInspectionId'],
        "patient" => $patientData,
        "doctor" => $doctorData,
        "diagnoses" => $diagnoses,
        "consultations" => $consultations
    ];

    echo json_encode($inspectionInfo);
    setHTTPSStatus("200");
}

// Функция для получения данных осмотра
function fetchInspectionData($inspectionId) {
    global $Link;
    $query = "SELECT * FROM inspection WHERE id='$inspectionId'";
    $result = $Link->query($query);
    if (!$result || $result->num_rows === 0) {
        setHTTPSStatus($result ? "404" : "500", $result ? "Inspection not found" : "InternalServerError: " . $Link->error);
        return false;
    }
    return $result->fetch_assoc();
}

// Функция для получения данных пациента или врача
function fetchPersonData($table, $personId) {
    global $Link;
    $query = "SELECT * FROM $table WHERE id='$personId'";
    $result = $Link->query($query);
    if (!$result) {
        setHTTPSStatus("500", "InternalServerError: " . $Link->error);
        return false;
    }
    return $result->fetch_assoc();
}

// Функция для получения диагнозов
function fetchDiagnoses($inspectionId) {
    global $Link;
    $query = "SELECT * FROM diagnosis WHERE idInspection='$inspectionId'";
    $result = $Link->query($query);
    if (!$result) {
        setHTTPSStatus("500", "InternalServerError: " . $Link->error);
        return false;
    }

    $diagnoses = [];
    while ($row = $result->fetch_assoc()) {
        $diagnoses[] = [
            "id" => $row['id'],
            "createTime" => $row['createTime'],
            "code" => $row['code'],
            "name" => $row['name'],
            "description" => $row['description'],
            "type" => $row['type']
        ];
    }
    return $diagnoses;
}

// Функция для получения консультаций и rootComment для этой консультации
function fetchConsultations($inspectionId) {
    global $Link;
    $consultationsQuery = "SELECT * FROM consultation WHERE inspectionId='$inspectionId'";
    $consultationsResult = $Link->query($consultationsQuery);
    if (!$consultationsResult) {
        setHTTPSStatus("500", "InternalServerError: " . $Link->error);
        return false;
    }

    $consultations = [];
    while ($consultation = $consultationsResult->fetch_assoc()) {
        $specialityData = fetchSpeciality($consultation['specialityId']);
        if (!$specialityData) return false;

        $rootComment = fetchRootComment($consultation['id']);
        if ($rootComment === false) return false;

        $commentsNumber = countComments($consultation['id']);
        if ($commentsNumber === false) return false;

        $consultations[] = [
            "id" => $consultation['id'],
            "createTime" => $consultation['createTime'],
            "inspectionId" => $consultation['inspectionId'],
            "speciality" => $specialityData,
            "rootComment" => $rootComment,
            "commentsNumber" => $commentsNumber
        ];
    }
    return $consultations;
}

// Функция для получения информации о специальности доктора
function fetchSpeciality($specialityId) {
    global $Link;
    $query = "SELECT * FROM speciality WHERE id='$specialityId'";
    $result = $Link->query($query);
    if (!$result) {
        setHTTPSStatus("500", "InternalServerError: " . $Link->error);
        return false;
    }
    return $result->fetch_assoc();
}

// rootComment для консультации
function fetchRootComment($consultationId) {
    global $Link;
    $query = "SELECT * FROM comments WHERE idConsultation='$consultationId' AND (idParentComment IS NULL OR idParentComment='')";
    $result = $Link->query($query);
    if (!$result) {
        setHTTPSStatus("500", "InternalServerError: " . $Link->error);
        return false;
    }

    if ($result->num_rows > 0) {
        $rootCommentData = $result->fetch_assoc();
        $authorData = fetchPersonData('doctor', $rootCommentData['authorId']);
        if (!$authorData) return false;

        return [
            "id" => $rootCommentData['id'],
            "createTime" => $rootCommentData['createTime'],
            "parentId" => $rootCommentData['idParentComment'],
            "content" => $rootCommentData['content'],
            "author" => $authorData,
            "modifyTime" => $rootCommentData['modifiedDate']
        ];
    }
    return null;
}

// подсчёт комментариев к консультации
function countComments($consultationId) {
    global $Link;
    $query = "SELECT COUNT(*) as commentsCount FROM comments WHERE idConsultation='$consultationId'";
    $result = $Link->query($query);
    if (!$result) {
        setHTTPSStatus("500", "InternalServerError: " . $Link->error);
        return false;
    }
    return $result->fetch_assoc()['commentsCount'];
}
