
<?php
function getRootICD10Elements() {
    global $Link;

    if (checkToken($Link)) {
    $query = "SELECT * FROM icd10 WHERE idParent='null'";

    // Выполнение запроса
    $result = $Link->query($query);
    if (!$result) {
        setHTTPSStatus("500", "InternalServerError");
        return;
    }

    $rootElements = [];
    while ($row = $result->fetch_assoc()) {
        $rootElements[] = $row;
    }   

    echo json_encode($rootElements);
    }
    setHTTPSStatus("200");

    return;
}