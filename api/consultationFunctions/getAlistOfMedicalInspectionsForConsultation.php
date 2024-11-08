<?php

function getAlistOfMedicalInspectionsForConsultation() {
    global $Link;

    //id врача для определения его специализации
    $doctorId = getDoctorIdFromToken();
    if (!$doctorId) {
        setHTTPSStatus("401", "Unauthorized");
        return;
    }

    $icdRoots = [];    
    $grouped = false;  
    $page = 1;         
    $size = 5;         

    // Разбираем строку запроса вручную, чтобы собрать все `icdRoots` параметры в массив
    $queryString = $_SERVER['QUERY_STRING'];
    $params = explode('&', $queryString);
    foreach ($params as $param) {
        $keyValue = explode('=', $param);
        if (count($keyValue) === 2) {
            $key = $keyValue[0];
            $value = $keyValue[1];

            // icdRoots добавляем в массив
            if ($key === 'icdRoots') {
                $icdRoots[] = (int) $value;
            } elseif ($key === 'grouped') {
                $grouped = ($value === 'true');
            } elseif ($key === 'page') {
                $page = (int) $value;
            } elseif ($key === 'size') {
                $size = (int) $value;
            }
        }
    }

    // получение специальности врача
    $specialityQuery = "SELECT speciality FROM doctor WHERE id='$doctorId'";
    $specialityResult = $Link->query($specialityQuery);
    if (!$specialityResult || $specialityResult->num_rows === 0) {
        setHTTPSStatus("403", "Doctor has no associated speciality");
        return;
    }
    $specialityId = $specialityResult->fetch_assoc()['speciality'];

    // начало основного запроса для получения общего количества данных об осмотрах
    $countQuery = "
        SELECT COUNT(*) AS total
        FROM inspection i
        INNER JOIN diagnosis d ON i.id = d.idInspection AND d.type = 'Main'
        INNER JOIN consultation c ON i.id = c.inspectionId
        WHERE c.specialityId = '$specialityId'";

    // фильтрация по МКБ-10
    if (!empty($icdRoots)) {
        $filteredInspectionIds = getFilteredInspectionIdsByICDRoots($icdRoots);
        if (empty($filteredInspectionIds)) {
            echo json_encode(['inspections' => [], 'pagination' => ['size' => 0, 'count' => 0, 'current' => 0]]);
            setHTTPSStatus("200");
            return;
        }
        $countQuery .= " AND i.id IN (" . implode(',', $filteredInspectionIds) . ")";
    }

    // группировка, если grouped тру
    if ($grouped) {
        $countQuery .= " AND (i.previousInspectionId IS NULL OR i.previousInspectionId = '')";
    }

    //запрос для получения общего количества записей
    $countResult = $Link->query($countQuery);
    $totalRecords = $countResult ? (int)$countResult->fetch_assoc()['total'] : 0;


    $totalPages = $size > 0 ? (int)ceil($totalRecords / $size) : 1;
    $currentPage = min($page, $totalPages); //проверка текущая страница не превышает общее количество страниц

    $query = "
        SELECT i.id, i.createTime, i.date, i.anamnesis, i.complaints, i.treatment, i.conclusion, 
               i.nextVisitDate, i.deathDate, i.previousInspectionId, i.idDoctor, i.idPatient, 
               (CASE WHEN i.hasChain = 1 THEN 'true' ELSE 'false' END) AS hasChain,
               (CASE WHEN EXISTS (SELECT 1 FROM inspection nested WHERE nested.previousInspectionId = i.id) THEN 'true' ELSE 'false' END) AS hasNested,
               d.icdDiagnosisId, d.code AS diagnosis_code, d.name AS diagnosis_name
        FROM inspection i
        INNER JOIN diagnosis d ON i.id = d.idInspection AND d.type = 'Main'
        INNER JOIN consultation c ON i.id = c.inspectionId
        WHERE c.specialityId = '$specialityId'";

    if (!empty($icdRoots)) {
        $query .= " AND i.id IN (" . implode(',', $filteredInspectionIds) . ")";
    }
    if ($grouped) {
        $query .= " AND (i.previousInspectionId IS NULL OR i.previousInspectionId = '')";
    }

    $offset = ($currentPage - 1) * $size;
    $query .= " LIMIT $offset, $size";

    //запрос для получения данных об осмотрах
    $inspectionsResult = $Link->query($query);

    if (!$inspectionsResult) {
        setHTTPSStatus("500", "Database error: " . $Link->error);
        return;
    }

    // Собираем результат
    $inspections = [];
    while ($inspection = $inspectionsResult->fetch_assoc()) {
        $inspections[] = [
            'id' => $inspection['id'],
            'createTime' => $inspection['createTime'],
            'date' => $inspection['date'],
            'anamnesis' => $inspection['anamnesis'],
            'complaints' => $inspection['complaints'],
            'treatment' => $inspection['treatment'],
            'conclusion' => $inspection['conclusion'],
            'nextVisitDate' => $inspection['nextVisitDate'],
            'deathDate' => $inspection['deathDate'],
            'previousInspectionId' => $inspection['previousInspectionId'],
            'idDoctor' => $inspection['idDoctor'],
            'idPatient' => $inspection['idPatient'],
            'hasChain' => $inspection['hasChain'],
            'hasNested' => $inspection['hasNested'],
            'diagnosis' => [
                'code' => $inspection['diagnosis_code'],
                'name' => $inspection['diagnosis_name']
            ]
        ];
    }

    $response = [
        'inspections' => $inspections,
        'pagination' => [
            'size' => $size,
            'count' => $totalPages,
            'current' => $currentPage
        ]
    ];

    echo json_encode($response);
    setHTTPSStatus("200");
}


// получение всех осмотров, где основной диагноз относится к заданным корневым МКБ
function getFilteredInspectionIdsByICDRoots($icdRoots) {
    global $Link;
    $filteredInspectionIds = [];

    // Получаем все осмотры с их основными диагнозами
    $inspectionDiagnosisQuery = "SELECT idInspection, icdDiagnosisId FROM diagnosis WHERE type = 'Main'";
    $inspectionDiagnosisResult = $Link->query($inspectionDiagnosisQuery);

    if ($inspectionDiagnosisResult) {
        while ($row = $inspectionDiagnosisResult->fetch_assoc()) {
            $icdDiagnosisId = $row['icdDiagnosisId'];
            $inspectionId = $row['idInspection'];

           // корневой ID для текущего диагноза
           $rootId = getRootICDId((int)$icdDiagnosisId);

        //    // Вывод для отладки: сравнение rootId с icdRoots
        //    echo "Текущий ID диагноза: $icdDiagnosisId, Корневой ID: $rootId, Сравнение с icdRoots: ";
        //    print_r($icdRoots);
        //    echo "<br>";

           // Сравнение корневого ID с заданными корневыми ID url
           if (in_array($rootId, $icdRoots)) {
               $filteredInspectionIds[] = $inspectionId;
               //echo "Ура совпадение найдено!!! ID осмотра: $inspectionId<br>";
           }
        }
    }

    return array_unique($filteredInspectionIds); // Убираем дубли
}

// Функция для получения корневого элемента icdDiagnosisId
function getRootICDId($icdDiagnosisId) {
    global $Link;

    // Начинаем с текущего ID, преобразуя его в целочисленное значение
    $currentId = (int)$icdDiagnosisId;

    // Для предотвращения бесконечных циклов ограничиваем количество итераций
    $iterationLimit = 5000; 
    $iterationCount = 0;

    while ($iterationCount < $iterationLimit) {
        $query = "SELECT id, idParent FROM icd10 WHERE id = '$currentId'";
        $result = $Link->query($query);

        // Если запрос выполнен успешно и запись найдена
        if ($result && $row = $result->fetch_assoc()) {
            // Преобразуем idParent в целочисленный формат
            $idParent = is_null($row['idParent']) ? null : (int)$row['idParent'];

            //
            //echo "Текущий ID: $currentId, idParent: " . ($idParent ?? "NULL") . "<br>";

            // Если достигнут idParent == NULL, возвращаем текущий ID
            if (is_null($idParent) || $idParent === 0) {
                return (int)$row['id'];
            }

            // Если нет, поднимаемся по цепочке на уровень выше чтобы дальше искать корень
            $currentId = $idParent;
            $iterationCount++;
        } else {
            // Если id не найден или нет связей, возвращаем null
            return null;
        }
    }
    // Если превышен лимит итераций, возвращаем null, чтобы избежать бесконечного цикла
    return null;
}
