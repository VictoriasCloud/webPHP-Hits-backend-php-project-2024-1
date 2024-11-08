<?php
//  получение данных консультации
function getConsultationData($consultationId) {
    global $Link;
    $query = "SELECT * FROM consultation WHERE id='$consultationId'";
    $result = $Link->query($query);

    if (!$result) {
        setHTTPSStatus("500", "Internal Server Error: " . $Link->error);
        return null;
    }

    if ($result->num_rows === 0) {
        setHTTPSStatus("404", "Consultation not found");
        return null;
    }

    return $result->fetch_assoc();
}

// Функция для получения ID врача из токена
function getDoctorIdFromToken() {
    global $Link;
    $token = explode(' ', getallheaders()['Authorization'])[1];
    $doctorQuery = "SELECT doctorId FROM token WHERE value='$token'";
    $doctorResult = $Link->query($doctorQuery);
    return $doctorResult ? $doctorResult->fetch_assoc()['doctorId'] : null;
}
