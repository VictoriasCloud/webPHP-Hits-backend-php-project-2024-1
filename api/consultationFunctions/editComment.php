<?php

function editComment($requestData) {
    global $Link;

    $id = $_GET['id']; 
    $content=$requestData->body->content;
    //проверка токена, существования комментария и авторства комментария

    if(checkToken($Link)&&(checkIdComment($id)&&checkAuthor($id))){
        // Обновление комментария
        $currentTime = date("Y-m-d\TH:i:s.v\Z");

        $updateCommentQuery = "UPDATE comments SET content = '$content', modifiedDate = '$currentTime' WHERE id = '$id'";

        if ($Link->query($updateCommentQuery) === TRUE) {
            // Возвращаем успех
            setHTTPSStatus("200", "Comment successfully edited");
        } else {
            setHTTPSStatus("500", "InternalServerError");
            }
        }
    return false;

}

function checkAuthor($idComment){
    global $Link;
    $token=explode(' ', getallheaders()['Authorization'])[1];
    $checkTokenQuery = "SELECT * FROM token WHERE value='$token'";
    $idDoctor = $Link->query($checkTokenQuery)->fetch_assoc()['doctorId'];
    //idDoctora
    $authorId="SELECT * FROM comments WHERE id='$idComment'";
    $authorId=$Link->query($authorId)->fetch_assoc()['authorId'];
    if ($idDoctor!=$authorId){
        setHTTPSStatus("403", "User is not the author of the comment");
        return false;
    }
    return true;
}

function checkIdComment($idComment){
    global $Link;

    $id="SELECT id FROM comments WHERE id='$idComment'";
    $idResult=$Link->query($id);

    if ($idResult->num_rows==1){
        return true;
    }
    setHTTPSStatus("404", "Comment not found|Invalid arguments");
    return false;
}


