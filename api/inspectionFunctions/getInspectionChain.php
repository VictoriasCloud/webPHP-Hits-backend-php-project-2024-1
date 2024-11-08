<?php
function getInspectionChain($inspectionId) {
    global $Link;

    //  наличие осмотра с указанным идентификатором
    $checkInspectionQuery = "SELECT * FROM inspection WHERE id='$inspectionId'";
    $checkInspectionResult = $Link->query($checkInspectionQuery);

    if ($checkInspectionResult->num_rows == 0) {
        setHTTPSStatus("404", "Inspection not Found");
        return;
    }

    $inspectionChain = [];
    $currentInspectionId = $inspectionId;

    // Используем цикл для обхода цепочки осмотров
    while (true) {
        // Запрос для получения дочернего осмотра
        $getNextInspectionQuery = "
        SELECT i.id, i.createTime, i.date, i.conclusion, i.idDoctor, i.idPatient, 
               p.name AS patient, 
               d.id AS diagnosis_id, d.createTime AS diagnosis_createTime, d.code, d.name AS diagnosis_name, 
               d.description, d.type, 
               doc.name AS doctor_name, 
               CASE WHEN i.hasChain = 1 THEN 'true' ELSE 'false' END AS hasChain,
               CASE WHEN EXISTS (SELECT 1 FROM inspection nested WHERE nested.previousInspectionId = i.id) 
                    THEN 'true' ELSE 'false' END AS hasNested,
               i.previousInspectionId
        FROM inspection i
        INNER JOIN diagnosis d ON i.id = d.idInspection AND d.type = 'Main'
        INNER JOIN patient p ON i.idPatient = p.id
        INNER JOIN doctor doc ON i.idDoctor = doc.id
        WHERE i.previousInspectionId = '$currentInspectionId'";
    
    

        $nextInspectionResult = $Link->query($getNextInspectionQuery);

        // Если дочерний осмотр найден, добавляем его в массив
        if ($nextInspectionResult->num_rows == 1) {
            $nextInspectionData = $nextInspectionResult->fetch_assoc();

            // Добавляем информацию о диагнозе к текущему осмотру
            $nextInspectionData['diagnosis'] = [
                'id' => $nextInspectionData['diagnosis_id'],
                'createTime' => $nextInspectionData['diagnosis_createTime'],
                'code' => $nextInspectionData['code'],
                'name' => $nextInspectionData['diagnosis_name'],
                'description' => $nextInspectionData['description'],
                'type' => $nextInspectionData['type']
            ];

            // Удаляем лишние поля
            unset($nextInspectionData['diagnosis_id']);
            unset($nextInspectionData['diagnosis_createTime']);
            unset($nextInspectionData['code']);
            unset($nextInspectionData['diagnosis_name']);
            unset($nextInspectionData['description']);
            unset($nextInspectionData['type']);

            $inspectionChain[] = $nextInspectionData;

            // Обновляем `currentInspectionId` для поиска следующего осмотра
            $currentInspectionId = $nextInspectionData['id'];
        } else {
            break;
        }
    }

    echo json_encode($inspectionChain);
    setHTTPSStatus("200");
}

