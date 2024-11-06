<?php

function addComment($requestData) {
    global $Link;
    $id = $_GET['id']; 

    $token=explode(' ', getallheaders()['Authorization'])[1];
    $checkTokenQuery = "SELECT * FROM token WHERE value='$token'";
    $idDoctor = $Link->query($checkTokenQuery)->fetch_assoc()['doctorId'];
    
    $queryResult = $Link->query("SELECT name FROM doctor WHERE id='$idDoctor'");
    $row = $queryResult->fetch_assoc();
    $commentAuthorName = $row['name'];
    // Проверка токена и наличия консультации
    if (checkToken($Link)&&checkConsultation($id)){

        $parentId = $requestData->body->parentId;
        $content=$requestData->body->content;
        $createTime = date("Y-m-d\TH:i:s.v\Z");
            // Проверка наличия родительского комментария
        if (!empty($parentId)&&(checkParentComment($parentId)==false)) {
            return;
        }
        // Определение parentId
        elseif(empty($parentId)){
            $parentId=definitionParentID($parentId, $id);
        }
        
    // Вставка комментария в базу данных
    $insertCommentQuery = "INSERT INTO comments (createTime, content, authorId, idParentComment, idConsultation, nameAuthor) 
                           VALUES ('$createTime', '$content', '$idDoctor', '$parentId', '$id', '$commentAuthorName')";

    if ($Link->query($insertCommentQuery) === TRUE) {
        // Возвращаем успех
        setHTTPSStatus("200", "Comment successfully added");
    } else {
        setHTTPSStatus("500", "InternalServerError");
    }
    return;
    }
}

function checkConsultation($id){
    global $Link;
    $consultationCheck = $Link->query("SELECT * FROM consultation WHERE id = '$id'");
    if (!$consultationCheck || $consultationCheck->num_rows === 0) {
        setHTTPSStatus("404", "Consultation not found");
        return 0;
    }
    return true;
}

// Проверка наличия родительского комментария
function checkParentComment($parentId){
    global $Link;
    $parentCommentCheck = $Link->query("SELECT * FROM comments WHERE id = '$parentId'");
    if (!$parentCommentCheck || $parentCommentCheck->num_rows === 0) {
        setHTTPSStatus("404", "Consultation or parent comment not found");
        return false;
    }
    return true;
}

// Если parentId не указан, берем самый последний комментарий у консультации
function definitionParentID($parentId, $id){
    global $Link;
    $lastCommentQuery = "SELECT id FROM comments WHERE idConsultation = '$id' ORDER BY createTime DESC LIMIT 1";
    $lastCommentResult = $Link->query($lastCommentQuery);
    if ($lastCommentResult && $lastCommentResult->num_rows > 0) {
        $lastComment = $lastCommentResult->fetch_assoc();
        $parentId = $lastComment['id'];
        return $parentId;
    }
    setHTTPSStatus("500", "InternalServerError");
    return 0;
}