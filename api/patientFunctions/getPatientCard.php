<?php
function getPatientCard() {
    global $Link;

    $checkTokenResult=checkToken($Link);
    $patientId= $_GET['id'];

    // Проверяем, существует ли пациент с указанным идентификатором
    $checkPatientQuery = "SELECT * FROM patient WHERE id='$patientId'";
    $checkPatientResult = $Link->query($checkPatientQuery);

    if (($checkPatientResult->num_rows == 1) && $checkTokenResult) {
        // Если пациент найден, извлекаем данные из базы данных
        $patientData = $checkPatientResult->fetch_assoc();

        // Возвращаем данные пациента в виде JSON
        echo json_encode($patientData);
         $_GET['id'];
        setHTTPSStatus("200", "Success");

    } else {
        // Если пациент не найден, возвращаем статус 404 (Not Found)
        setHTTPSStatus("404", "Patient not found");
    }
}