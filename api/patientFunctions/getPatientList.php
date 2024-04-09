<?php

function getPatientList(){
    global $Link;

    $token = explode(' ', getallheaders()['Authorization'])[1];
    $checkTokenQuery = "SELECT * FROM token WHERE value='$token'";
    $doctorId = $Link->query($checkTokenQuery)->fetch_assoc()['doctorId'];

    $name = $_GET['name'];
    $conclusion = $_GET['conclusion']; // Поправил имя переменной

    $sorting = $_GET['sorting'];
    $scheduledVisits = $_GET['scheduledVisits'];
    $onlyMine = $_GET['onlyMine'];
    $page = $_GET['page'];
    $size = $_GET['size'];
    $countOfPages = 0;

    if (!validatePaginationParameters($page, $size)){
        return;
    }

    if (checkToken($Link)) {
        // Базовый запрос
        $sql = "SELECT * FROM patient";

        $conditions = [];

        // Добавление фильтрации по имени
        if (!empty($name)) {
            $conditions[] = "name LIKE '$name%'";
        }

        // Добавление фильтрации по заключениям осмотров
        if (!empty($conclusion)) {
            $conclusion = $Link->real_escape_string($conclusion); // Защита от SQL инъекций
            $result = $Link->query("SELECT DISTINCT idPatient FROM inspection WHERE conclusion = '$conclusion'");
            if ($result && $result->num_rows > 0) {
                $patientIds = [];
                while ($row = $result->fetch_assoc()) {
                    $patientIds[] = $row['idPatient'];
                }
                $conditions[] = "id IN (" . implode(",", $patientIds) . ")";
            } else {
                echo "No patients found with the specified conclusion.";
                return;
            }
        }

        // Добавление фильтрации по запланированным визитам
        if ($scheduledVisits == 'true') {
            $result = $Link->query("SELECT DISTINCT idPatient FROM inspection WHERE nextVisitDate IS NOT NULL");
            if ($result && $result->num_rows > 0) {
                $patientIds = [];
                while ($row = $result->fetch_assoc()) {
                    $patientIds[] = $row['patient'];
                }
                $conditions[] = "id IN (" . implode(",", $patientIds) . ")";
            } else {
                echo "No patients found with scheduled visits.";
                return;
            }
        }

        // Добавление фильтрации по "Мои пациенты"
        if ($onlyMine == 'true') {
            $conditions[] = "id IN (SELECT DISTINCT patient FROM inspection WHERE doctor = '$doctorId')";
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
                $sql .= " ORDER BY (SELECT MAX(createTime) FROM inspection WHERE patient = patients.id) ASC";
                break;
            case 'inspectionDateDesc':
                $sql .= " ORDER BY (SELECT MAX(createTime) FROM inspection WHERE patient = patients.id) DESC";
                break;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        // Выполнение основного запроса
        $listOfPatients = $Link->query($sql);

        if ($listOfPatients->num_rows > 0) {
            $ArrayOfPatients = [];
            $countOfPages = ceil($listOfPatients->num_rows / $size);
            //начало и конец страницы
            $startPage = $size * ($page - 1);
            $finishPage = min(($startPage + ($size - 1)), ($listOfPatients->num_rows - 1));
            $currentPage = 0;

            while ($row = $listOfPatients->fetch_assoc() AND $currentPage <= $finishPage) {
                if ($currentPage >= $startPage) {
                    $ArrayOfPatients[] = $row;
                }

                $currentPage += 1;
            }

            $patientsOnPage = [];
            $patientsOnPage['patients'] = $ArrayOfPatients;
            $pagination = [];
            $pagination['size'] = $size;
            $pagination['count'] = $countOfPages;
            $pagination['current'] = $page;
            $patientsOnPage['pagination'] = $pagination;
            echo json_encode($patientsOnPage);
            setHTTPSStatus("200", "Patients paged list retrieved");
        } elseif(!$listOfPatients) {
            setHTTPSStatus("500", "InternalServerError");
            return false;
        }
        else{
            echo "Patients List is empty";
        }
    }return;
}
