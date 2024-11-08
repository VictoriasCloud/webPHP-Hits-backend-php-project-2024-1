<?php 

    function createSpeciality($requestData){

        $body = $requestData->body;
        $speciality = $body->speciality;
        $timeToValid = date('Y-m-d\TH:i:s.u');
        //встроенная функция, которая фиксирует время сейчас
        $Link = mysqli_connect("127.0.0.1", "backend_demo_1", "password", "backend");

        $insertSpeciality = $Link ->query("INSERT INTO speciality(name, createTime) VALUES ('$speciality','$timeToValid')");
        if($insertSpeciality){
            setHTTPSStatus("200");
        }
        else{
            setHTTPSStatus("500", "InternalServerError: " . $Link->error);
        }
    }