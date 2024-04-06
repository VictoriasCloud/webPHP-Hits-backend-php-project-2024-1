<?php
    include_once "patientFunctions/patient.php";
    function route($method, $urlList, $requestData){
        global $Link;
        switch ($method) {
            case 'GET':
                switch ($urlList[2]) {
                    case 'getPatientsList':
                        echo "5ураа";
                        getPatientsList(explode(' ', getallheaders()['Authorization'])[1]);
                        break;
                    case 'getPatientCard':
                        getPatientCard($requestData);
                        break;
                    case 'getAlistOfPatientMedicalInspections':
                        getAlistOfPatientMedicalInspections($requestData);
                        break;
                    case 'SearchForPatientWithoutChildInspections':
                        SearchForPatientWithoutChildInspections($requestData);
                        break;
                    
                    default:
                        # code...
                        break;
                }

            case 'POST':
                switch ($urlList[2]) {
                    case 'createNewPatient':  
                        createNewPatient($requestData);
                        break;
                    case 'CreateInspectionForSpecifiedPatient':  
                        CreateInspectionForSpecifiedPatient($requestData);
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