<?php
include_once "patientFunctions/createNewPatient.php";
include_once "patientFunctions/getPatientCard.php";
include_once "patientFunctions/getPatientList.php";
include_once "patientFunctions/getAlistOfPatientMedicalInspections.php";
include_once "patientFunctions/CreateInspectionForSpecifiedPatient.php";
include_once "patientFunctions/SearchForPatientWithoutChildInspections.php";

function route($method, $urlList, $requestData) {
    global $Link;
    // Проверка токена, чтобы не проверять его в каждой функци.
    if (!checkToken($Link)) {
        return;
    }
    $patientId = isset($urlList[2]) && is_numeric($urlList[2]) ? $urlList[2] : null;
    

    switch ($method) {
        case 'GET':
            if ($patientId !== null && isset($urlList[3]) && $urlList[3] === 'inspections') {
                getAlistOfPatientMedicalInspections($patientId, $requestData->parameters);
                //var_dump($requestData->parameters); 
            } 
            elseif ($patientId === null && count($urlList) == 2 && $urlList[1] === 'patient') {
                // Путь соответствует /api/patient, выполняем получение списка пациентов
                getPatientList();
            }
            else {
                setHTTPSStatus("404", "Invalid GET route for patient");
            }
            break;

        case 'POST':
            if ($patientId !== null && isset($urlList[3]) && $urlList[3] === 'inspections') {
                CreateInspectionForSpecifiedPatient($Link, $patientId, $requestData);
            } 
            elseif ($patientId === null && count($urlList) == 2 && $urlList[1] === 'patient') {
                createNewPatient($requestData);
            }
            else {
                setHTTPSStatus("400", "Incorrect path");
            }
            break;

        default:
            setHTTPSStatus("405", "Method Not Allowed");
            break;
    }
}
