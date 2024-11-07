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


// Проверка наличия одного диагноза с типом "Main" и валидация типов
function checkMainDiagnosisAndValidType($requestData) {
    $mainDiagnosisCount = 0;

    if (isset($requestData->diagnoses) && is_array($requestData->diagnoses)) {
        foreach ($requestData->diagnoses as $diagnosis) {
            $type = $diagnosis->type;
            
            // недопдопустимость типа диагноза
            if (!checkDiagnosisType($type)) {
                setHTTPSStatus("400", "Invalid diagnosis type: $type. Allowed types are: " . implode(', ', VALID_DIAGNOSIS_TYPES));
                return false;
            }

            // Проверка на наличие одного Main диагноза
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


const VALID_DIAGNOSIS_TYPES = ['Main', 'Concomitant', 'Complication'];

// Функция для проверки, является ли тип диагноза допустимым
function checkDiagnosisType($type) {
    return in_array($type, VALID_DIAGNOSIS_TYPES);
}