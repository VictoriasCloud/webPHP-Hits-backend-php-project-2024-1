<?php

function getConsultationById() {
    global $Link;
    if(!checkToken($Link)){
        return;
    }
    $id=$_GET['id'];
    // Подготовка SQL запроса
    $sql = "SELECT c.id, c.createTime, c.inspectionId, c.specialityId, c.idParentConsultation, c.idDoctor, c.idPatient, 
            JSON_ARRAYAGG(JSON_OBJECT('id', cm.id, 'createTime', cm.createTime, 'modifiedDate', cm.modifiedDate, 
                                      'content', cm.content, 'authorId', cm.authorId, 'author', cm.nameAuthor, 
                                      'parentId', cm.idParentComment)) AS comments
            FROM consultation c
            LEFT JOIN comments cm ON c.id = cm.idConsultation
            WHERE c.id = '$id'
            GROUP BY c.id";

    // Выполнение запроса
    $result = $Link->query($sql);
    if ($result && $result->num_rows > 0) {
        $consultation = $result->fetch_assoc();

        // Преобразование времени в строковый формат
        //$consultation['createTime'] = date("Y-m-d\TH:i:s.v\Z", strtotime($consultation['createTime']));

        // Преобразование JSON строки комментариев в массив
        $consultation['comments'] = json_decode($consultation['comments'], true);

        // Вывод в формате JSON
        echo json_encode($consultation);
        setHTTPSStatus("200", "Success");
    } else {
        // Если консультация не найдена
        setHTTPSStatus("404", "Consultation not found");
    }
}