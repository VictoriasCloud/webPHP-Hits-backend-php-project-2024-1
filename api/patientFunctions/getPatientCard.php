<?php
function getPatientCard($patientId) {
    global $Link;

    //получение основной информации о пациенте
    $patientQuery = "SELECT name, birthday, gender, id, createTime FROM patient WHERE id = '$patientId'";
    $patientResult = $Link->query($patientQuery);

    // Проверка на ошибку выполнения запроса
    if ($patientResult === false) {
        setHTTPSStatus("500", "Internal Server Error: " . $Link->error);
        return;
    }

    if ($patientResult && $patientResult->num_rows > 0) {
        $patientData = $patientResult->fetch_assoc();

        echo json_encode($patientData);
        setHTTPSStatus("200");
    } else {
        setHTTPSStatus("404", "Patient not found");
    }
}

