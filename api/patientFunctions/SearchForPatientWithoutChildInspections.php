<?php

function SearchForPatientWithoutChildInspections(){
    global $Link;
    $idPatient=$_GET['idPatient'];
    $searchItem=$_GET['request'];

    $checkTokenResult = checkToken($Link);
    
    // Проверяем, существует ли пациент с указанным идентификатором
    $checkPatientQuery = "SELECT * FROM patient WHERE id='$idPatient'";
    $checkPatientResult = $Link->query($checkPatientQuery);

    if ($checkPatientResult === false) {
        // Если произошла ошибка при выполнении запроса, возвращаем ошибку 500
        setHTTPSStatus("500", "InternalServerError");
        return;
    }

    if ($checkPatientResult->num_rows == 1 && $checkTokenResult) {
        // Если пациент найден, выполняем поиск осмотров
        
        // Формируем запрос для поиска осмотров с типом диагноза "Main"
        $searchQuery = "SELECT i.id, i.createTime, i.date, d.id AS diagnosis_id, d.createTime AS diagnosis_createTime, d.code, d.name, d.description, d.type
                        FROM inspection i
                        INNER JOIN diagnosis d ON i.id = d.idInspection
                        WHERE i.idPatient='$idPatient' AND d.type='Main' AND (d.code LIKE '%$searchItem%' OR d.name LIKE '%$searchItem%')";
        
        $searchResult = $Link->query($searchQuery);

        if ($searchResult === false) {
            // Если произошла ошибка при выполнении запроса, возвращаем ошибку 500
            setHTTPSStatus("500", "InternalServerError");
            return;
        }
        
        if ($searchResult->num_rows > 0) {
            $inspections = [];
            
            while ($row = $searchResult->fetch_assoc()) {
                // Добавляем информацию о диагнозе к осмотру
                $row['diagnosis'] = [
                    'id' => $row['diagnosis_id'],
                    'createTime' => $row['diagnosis_createTime'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'type' => $row['type']
                ];
                
                // Удаляем лишние поля
                unset($row['diagnosis_id']);
                unset($row['diagnosis_createTime']);
                unset($row['type']);
                // Добавляем осмотр в список
                $inspections[] = $row;
            }
            
            // Возвращаем данные в виде JSON
            echo json_encode($inspections);
            setHTTPSStatus("200", "Success");
        } else {
            // Если осмотры не найдены, возвращаем пустой массив
            echo json_encode([]);
            setHTTPSStatus("200", "Such inspections not found.");
        }
    } else {
        // Если пациент не найден, возвращаем статус 404 (Not Found)
        setHTTPSStatus("404", "Patient not found");
    }
}
