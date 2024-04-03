<?php 

    function createSpeciality($requestData){
        
        //echo "123";
        $body = $requestData->body;
        $name=$body->name;
        $speciality = $body->speciality;
        $timeToValid = date('Y-m-d\TH:i:s.u');
        //встроенная функция, которая фиксирует время сейчас
        $Link = mysqli_connect("127.0.0.1", "backend_demo_1", "password", "backend");
        //echo $speciality;

        $insertSpeciality = $Link ->query("INSERT INTO speciality(name, createTime) VALUES ('$speciality','$timeToValid')");
        echo $insertSpeciality;
        if($insertSpeciality){
            
            http_response_code(200);
            echo "Успешно добавлено";
        }
        else{
            echo "000";

            http_response_code(500);
            //echo statusCode("Internal Server Error", 500);
        }
    }
?>