<?php

function SearchForPatientWithoutChildInspections($patientId, $parameters) {
    global $Link;

    $searchItem = $parameters['request'] ?? ''; 

    // Проверяем, существует ли пациент с указанным идентификатором
    $checkPatientQuery = "SELECT * FROM patient WHERE id='$patientId'";
    $checkPatientResult = $Link->query($checkPatientQuery);

    if ($checkPatientResult === false) {
        setHTTPSStatus("500", "Internal Server Error");
        return;
    }

    if ($checkPatientResult->num_rows == 1) {
        // Если пациент найден, выполняем поиск осмотров
        $searchQuery = "SELECT i.id, i.createTime, i.date, d.id AS diagnosis_id, d.createTime AS diagnosis_createTime, d.code, d.name, d.description, d.type
                        FROM inspection i
                        INNER JOIN diagnosis d ON i.id = d.idInspection
                        WHERE i.idPatient = '$patientId' 
                          AND (i.previousInspectionId IS NULL OR i.previousInspectionId = '') 
                          AND d.type = 'Main'
                          AND (d.code LIKE '%$searchItem%' OR d.name LIKE '%$searchItem%')";

        $searchResult = $Link->query($searchQuery);

        if ($searchResult === false) {
            setHTTPSStatus("500", "Internal Server Error");
            return;
        }

        if ($searchResult->num_rows > 0) {
            $inspections = [];

            while ($row = $searchResult->fetch_assoc()) {
                // Добавляем диагноз к осмотру
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
                unset($row['name']);
                unset($row['code']);
                unset($row['description']);

                // Добавляем осмотр в список
                $inspections[] = $row;
            }

            echo json_encode($inspections);
            setHTTPSStatus("200");
        } else {
            // Если осмотры не найдены, возвращаем пустой массив
            echo json_encode([]);
            setHTTPSStatus("200");
        }
    } else {
        setHTTPSStatus("404", "Patient not found");
    }
}
