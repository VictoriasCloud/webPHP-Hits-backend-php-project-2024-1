<?php
//  получение данных консультации
function getConsultationData($consultationId) {
    global $Link;
    $query = "SELECT * FROM consultation WHERE id='$consultationId'";
    $result = $Link->query($query);

    if (!$result) {
        setHTTPSStatus("500", "Internal Server Error: " . $Link->error);
        return null;
    }

    if ($result->num_rows === 0) {
        setHTTPSStatus("404", "Consultation not found");
        return null;
    }

    return $result->fetch_assoc();
}