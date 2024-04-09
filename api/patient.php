<?php
    include_once "patientFunctions/createNewPatient.php";
    include_once "patientFunctions/getPatientCard.php";
    include_once "patientFunctions/getPatientList.php";
    include_once "patientFunctions/getAlistOfPatientMedicalInspections.php";
    include_once "patientFunctions/CreateInspectionForSpecifiedPatient.php";
    include_once "patientFunctions/SearchForPatientWithoutChildInspections.php";

    function route($method, $urlList, $requestData){
        global $Link;
        switch ($method) {
            case 'GET':
                switch ($urlList[2]) {
                    case 'getPatientsList':
                        echo "5ураа";
                        getPatientList();
                        break;
                    case 'getPatientCard':
                        getPatientCard();
                        break;
                    case 'getAlistOfPatientMedicalInspections':
                        getAlistOfPatientMedicalInspections();
                        break;
                    case 'SearchForPatientWithoutChildInspections':
                        SearchForPatientWithoutChildInspections();
                        break;
                    
                    default:
                        # code...
                        break;
                }
                break;

            case 'POST':
                switch ($urlList[2]) {
                    case 'createNewPatient':  
                        createNewPatient($requestData);
                        break;
                    case 'CreateInspectionForSpecifiedPatient':  
                        CreateInspectionForSpecifiedPatient($Link, $requestData);
                        break;
                    default:
                    //или 400 ошибка(неверный запрос к урлу)
                        setHTTPSStatus("404", "There is no such path as 'patient/$urlList[1]'");
                        break;             
                }                  
                break;
            
            default:
                //или 400 ошибка(неверный запрос к урлу/синтаксическая ошибка)
                setHTTPSStatus("404", "There is no such method here or Method Not Allowed(405)");
                break;
        }
    }