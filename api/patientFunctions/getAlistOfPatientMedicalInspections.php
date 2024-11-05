<?php
function getAlistOfPatientMedicalInspections($patientId, $params) {
    global $Link;
    
    $checkTokenResult = checkToken($Link);
    $grouped = $params['grouped'] ?? 'false';
    $icdRoots = $params['icdRoots'] ?? [];
    $page = $params['page'] ?? 1;
    $size = $params['size'] ?? 10;

    // Преобразуем icdRoots в массив, если это одиночное значение
    if (!is_array($icdRoots) && !is_null($icdRoots)) {
        $icdRoots = [$icdRoots];
    }
    
    $startRow = ($page - 1) * $size;
    
    // Строим запрос в зависимости от grouped
    $query = "SELECT * FROM inspection WHERE idPatient='$patientId'";
    if ($grouped === 'true') {
        $query .= " AND previousInspectionId IS NULL";
    }
    $query .= " LIMIT $startRow, $size";
    
    $result = $Link->query($query);
    $inspections = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $diagnosisQuery = "SELECT * FROM diagnosis WHERE idInspection='{$row['id']}' AND type='Main'";
            $diagnosisResult = $Link->query($diagnosisQuery);
            $mainDiagnosis = $diagnosisResult->fetch_assoc();

            // Проверка: если diagnosis.icdDiagnosisId не в icdRoots, пропускаем осмотр
            if (!empty($icdRoots) && !in_array($mainDiagnosis['icdDiagnosisId'], $icdRoots)) {
                continue;
            }
            
            // Если диагноз прошёл фильтр, добавляем его и другие данные в результат
            $row['diagnosis'] = $mainDiagnosis;
            $row['hasChain'] = (bool)$row['hasChain'];
            $row['hasNested'] = (bool)$row['hasNested'];
            $inspections[] = $row;
        }
    }
    
    echo json_encode([
        'inspections' => $inspections,
        'pagination' => [
            'size' => $size,
            'count' => count($inspections),
            'current' => $page
        ]
    ]);
    setHTTPSStatus("200");
}


function validateArguments($grouped, $icdRoots, $page, $size) {
    // Преобразуем icdRoots в массив, если это одиночное значение
    if (!is_array($icdRoots) && !is_null($icdRoots)) {
        $icdRoots = [$icdRoots];
    }

    if (validatePaginationParameters($page, $size) && ($grouped === 'true' || $grouped === 'false')) {
        // Если icdRoots присутствует и не пустой, проверяем его
        if (is_array($icdRoots) && !empty($icdRoots)) {
            if (searchICD10Roots($icdRoots)) {
                return true;
            }
            return false;
        } elseif (is_null($icdRoots) || $icdRoots === "" || empty($icdRoots)) {
            // Если icdRoots пустой или отсутствует, пропускаем проверку
            return true;
        } else {
            setHTTPSStatus("400", "icdRoots must be an array if specified");
            return false;
        }
    }
    return false;
}


function searchICD10Roots($icdRoots) {
    global $Link;

    // Проверяем каждый элемент массива icdRoots
    foreach ($icdRoots as $root) {
        $query = "SELECT * FROM icd10 WHERE idParent IS NULL AND (id='$root' OR mkb_code='$root' OR mkb_name='$root')";
        $result = $Link->query($query);

        if (!$result || $result->num_rows == 0) {
            setHTTPSStatus("400", "Invalid argument(icdRoots) for filtration: $root not found");
            return false;
        }
    }

    return true;
}

