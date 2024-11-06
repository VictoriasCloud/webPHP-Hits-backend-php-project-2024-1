<?php

function checkConclusionWithDeath($idPatient){
    global $Link;
    $countDeath = $Link->query("SELECT COUNT(*) AS death_count FROM inspection WHERE idPatient = '$idPatient' AND conclusion = 'Death'")->fetch_assoc()['death_count'];
    if ($countDeath==0){
        return true;
    }
    setHTTPSStatus("400", "The patient already has a 'death' in the inspection.");
    return false;

}


// Проверка, что дата создания осмотра не больше даты
function checkCreateTimeAndPresentTime($data){
    $presentTime= date('Y-m-d\TH:i:s.u');
    if ($data<=$presentTime){
        return true;
    }
    else{
        setHTTPSStatus("400", "Problems with time of inspection");
        return false;
    }
}


// Проверка наличия хотя бы одного диагноза с типом "Main"
function checkMainDiagnosisCount($requestData) {
    $mainDiagnosisCount = 0;

    if (isset($requestData->diagnoses) && is_array($requestData->diagnoses)) {
        foreach ($requestData->diagnoses as $diagnosis) {
            $type = $diagnosis->type;
            if ($type === "Main") {
                $mainDiagnosisCount++;
            }
        }
    }

    if ($mainDiagnosisCount !== 1) {
        setHTTPSStatus("400", "Invalid diagnoses. Inspection must have exactly one Main diagnosis.");
        return false;
    }
    return true;
}