<?php
function getICD10RootsReport($requestData) {
    global $Link;


    $checkTokenResult = checkToken($Link);
    if (!$checkTokenResult) {
        return;
    }

    $start = $_GET['start'] ?? null;
    $end = $_GET['end'] ?? null;
    $icdRoots = isset($_GET['icdRoots']) ? (is_array($_GET['icdRoots']) ? $_GET['icdRoots'] : [$_GET['icdRoots']]) : [];


    if (!$start || !$end) {
        setHTTPSStatus("400", "Invalid date range");
        return;
    }

    // Форматируем даты
    $startDate = date('Y-m-d H:i:s', strtotime($start));
    $endDate = date('Y-m-d H:i:s', strtotime($end));

    // Если корни МКБ-10 указаны, получаем все диагнозы в поддереве этих корней
    $icdDiagnosisIds = [];
    if (!empty($icdRoots)) {
        foreach ($icdRoots as $root) {
            $icdDiagnosisIds = array_merge($icdDiagnosisIds, getICDSubtreeDiagnosisIds($root));
        }
        $icdDiagnosisIds = array_unique($icdDiagnosisIds);
    }

    $query = "
        SELECT p.name AS patientName, p.birthday AS patientbirthday, p.gender, d.icdDiagnosisId AS icdDiagnosisId, COUNT(i.id) AS visitCount
        FROM patient p
        JOIN inspection i ON i.idPatient = p.id
        JOIN diagnosis d ON i.id = d.idInspection
        WHERE i.date BETWEEN '$startDate' AND '$endDate'
        GROUP BY p.name, p.birthday, p.gender, d.icdDiagnosisId
        ORDER BY p.name";

    $result = $Link->query($query);

    if (!$result) {
        setHTTPSStatus("500", "Database error: " . $Link->error);
        return;
    }

    $records = [];
    $summaryByRoot = [];
    while ($row = $result->fetch_assoc()) {
        $patientName = $row['patientName'];
        $patientbirthday = $row['patientbirthday'];
        $gender = $row['gender'];
        $icdDiagnosisId = $row['icdDiagnosisId'];
        $visitCount = (int)$row['visitCount'];

        // Проверяем, входит ли диагноз в указанные корни
        $rootId = getRootICDId($icdDiagnosisId);
        if (empty($icdRoots) || in_array($rootId, $icdRoots)) {
            // Добавляем статистику по пациенту
            if (!isset($records[$patientName])) {
                $records[$patientName] = [
                    'patientName' => $patientName,
                    'patientbirthday' => $patientbirthday,
                    'gender' => $gender,
                    'visitsByRoot' => []
                ];
            }

            // Добавляем количество посещений по корню
            if (!isset($records[$patientName]['visitsByRoot'][$rootId])) {
                $records[$patientName]['visitsByRoot'][$rootId] = 0;
            }
            $records[$patientName]['visitsByRoot'][$rootId] += $visitCount;

            // Обновляем суммарные данные по каждому корню
            if (!isset($summaryByRoot[$rootId])) {
                $summaryByRoot[$rootId] = 0;
            }
            $summaryByRoot[$rootId] += $visitCount;
        }
    }

    $sortedRecords = array_values($records);
    usort($sortedRecords, function ($a, $b) {
        return strcmp($a['patientName'], $b['patientName']);
    });

    ksort($summaryByRoot);

    $response = [
        'filters' => [
            'start' => $start,
            'end' => $end,
            'icdRoots' => array_values(array_unique($icdRoots))
        ],
        'records' => $sortedRecords,
        'summaryByRoot' => $summaryByRoot
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
    setHTTPSStatus("200");
}

// Функция для получения корневого элемента icdDiagnosisId
function getRootICDId($icdDiagnosisId) {
    global $Link;
    $currentId = (int)$icdDiagnosisId;

    while (true) {
        $query = "SELECT id, idParent FROM icd10 WHERE id = '$currentId'";
        $result = $Link->query($query);

        if ($result && $row = $result->fetch_assoc()) {
            $idParent = is_null($row['idParent']) ? null : (int)$row['idParent'];
            
            // Если достигли корня, возвращаем текущий ID
            if (is_null($idParent) || $idParent === 0) {
                return (int)$row['id'];
            }
            $currentId = $idParent;  // Поднимаемся к родителю
        } else {
            return null;
        }
    }
}

// Вспомогательная функция для получения всех поддеревьев по корню МКБ-10
function getICDSubtreeDiagnosisIds($rootId) {
    global $Link;

    $diagnosisIds = [];
    $toProcess = [$rootId];

    while (!empty($toProcess)) {
        $currentId = array_pop($toProcess);
        $diagnosisIds[] = $currentId;

        $query = "SELECT id FROM icd10 WHERE idParent = '$currentId'";
        $result = $Link->query($query);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $toProcess[] = (int)$row['id'];
            }
        }
    }

    return $diagnosisIds;
}
