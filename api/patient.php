<?php
include_once "patientFunctions/createNewPatient.php";
include_once "patientFunctions/getPatientCard.php";
include_once "patientFunctions/getPatientList.php";
include_once "patientFunctions/getAlistOfPatientMedicalInspections.php";
include_once "patientFunctions/CreateInspectionForSpecifiedPatient.php";
include_once "patientFunctions/SearchForPatientWithoutChildInspections.php";

function route($method, $urlList, $requestData) {
    global $Link;

    $patientId = isset($urlList[2]) && is_numeric($urlList[2]) ? $urlList[2] : null;

    switch ($method) {
        case 'GET':
            if ($patientId !== null && isset($urlList[3]) && $urlList[3] === 'inspections') {
                getAlistOfPatientMedicalInspections($patientId, $requestData->parameters);
            } else {
                setHTTPSStatus("404", "Invalid GET route for patient");
            }
            break;

        case 'POST':
            if ($patientId !== null && isset($urlList[3]) && $urlList[3] === 'inspections') {
                CreateInspectionForSpecifiedPatient($Link, $patientId, $requestData);
            } else {
                createNewPatient($requestData);
            }
            break;

        default:
            setHTTPSStatus("405", "Method Not Allowed");
            break;
    }
}
