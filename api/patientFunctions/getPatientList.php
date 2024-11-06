<?php
// Сначала фильтрация идет по по имени и заключению — чтобы сузить выборку пациентов.
// Затем добавляются фильтры по наличию запланированных визитов и критерию "Мои пациенты",
// так как эти параметры могут далее сократить количество подходящих пациентов. 
//В конце применяется сортировка, если данные уже отфильтрованы
function getPatientList() {
    global $Link;

    $token = explode(' ', getallheaders()['Authorization'])[1];
    $checkTokenQuery = "SELECT * FROM token WHERE value='$token'";
    $tokenResult = $Link->query($checkTokenQuery);

    $doctorId = $tokenResult->fetch_assoc()['doctorId'];

    $name = $_GET['name'] ?? null;
    $conclusion = $_GET['conclusion'] ?? null;
    $sorting = $_GET['sorting'] ?? null;
    $scheduledVisits = $_GET['scheduledVisits'] ?? null;
    $onlyMine = $_GET['onlyMine'] ?? null;
    $page = $_GET['page'] ?? 1;
    $size = $_GET['size'] ?? 5;
    $countOfPages = 0;

    if (!validatePaginationParameters($page, $size)) {
        return;
    }

    if (checkToken($Link)) {
        $sql = "SELECT * FROM patient";
        $conditions = [];

        // Фильтрация по имени
        if (!empty($name)) {
            $name = $Link->real_escape_string($name);
            $conditions[] = "name LIKE '$name%'";
        }

        // Фильтрация по заключению
        if (!empty($conclusion)) {
            $conclusion = $Link->real_escape_string($conclusion);
            $result = $Link->query("SELECT DISTINCT idPatient FROM inspection WHERE conclusion = '$conclusion'");
            if ($result && $result->num_rows > 0) {
                $patientIds = array_column($result->fetch_all(MYSQLI_ASSOC), 'idPatient');
                $conditions[] = "id IN (" . implode(",", $patientIds) . ")";
            } else {
                setHTTPSStatus("404", "No patients found with the specified conclusion");
                return;
            }
        }

        // Фильтрация по запланированным визитам
        if ($scheduledVisits == 'true') {
            $result = $Link->query("SELECT DISTINCT idPatient FROM inspection WHERE nextVisitDate IS NOT NULL");
            if ($result && $result->num_rows > 0) {
                $patientIds = array_column($result->fetch_all(MYSQLI_ASSOC), 'idPatient');
                $conditions[] = "id IN (" . implode(",", $patientIds) . ")";
            } else {
                setHTTPSStatus("404", "No patients found with scheduled visits");
                return;
            }
        }

        // Фильтрация по "Мои пациенты". пациенты, у которых есть хотя бы один осмотр, проведенный данным врачом
        if ($onlyMine == 'true') {
            $conditions[] = "id IN (SELECT DISTINCT idPatient FROM inspection WHERE idDoctor = '$doctorId')";
        }

        // Добавление условий по заключению
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        // Добавление сортировки
        switch ($sorting) {
            case 'nameAsc':
                $sql .= " ORDER BY name ASC";
                break;
            case 'nameDesc':
                $sql .= " ORDER BY name DESC";
                break;
            case 'createdAtAsc':
                $sql .= " ORDER BY createTime ASC";
                break;
            case 'createdAtDesc':
                $sql .= " ORDER BY createTime DESC";
                break;
            case 'inspectionDateAsc':
                $sql .= " ORDER BY (SELECT MAX(createTime) FROM inspection WHERE idPatient = patient.id) ASC";
                break;
            case 'inspectionDateDesc':
                $sql .= " ORDER BY (SELECT MAX(createTime) FROM inspection WHERE idPatient = patient.id) DESC";
                break;
            default:
                break;
        }

        // Выполнение основного запроса
        $listOfPatients = $Link->query($sql);
        if (!$listOfPatients) {
            setHTTPSStatus("500", "InternalServerError: " . $Link->error);
            return;
        }

        // Обработка результатов
        $totalRecords = $listOfPatients->num_rows;
        $countOfPages = (int) ceil($totalRecords / $size);
        $startPage = ($page - 1) * $size;
        $ArrayOfPatients = $listOfPatients->fetch_all(MYSQLI_ASSOC);
        $paginatedPatients = array_slice($ArrayOfPatients, $startPage, $size);

        // Формирование ответа
        echo json_encode([
            'patients' => $paginatedPatients,
            'pagination' => [
                'size' => (int)$size,
                'count' => $countOfPages,
                'current' => (int)$page,
            ]
        ]);
        setHTTPSStatus("200");
    }
}
