<?php
   include_once "inspectionFunctions/getInspectionChain.php";
   include_once "inspectionFunctions/editInspection.php";
   include_once "inspectionFunctions/getFullInfo.php";
   
   function route($method, $urlList, $requestData) {
       global $Link;
   
       $checkTokenResult = checkToken($Link);
       if (!$checkTokenResult) {
           setHTTPSStatus("401", "Unauthorized");
           return;
       }
   
       // Проверка, что ID осмотра передан и корректен
       $inspectionId = $urlList[2] ?? null;
       if (!$inspectionId || !is_numeric($inspectionId)) {
           setHTTPSStatus("400", "Invalid or missing inspection ID");
           return;
       }
   
       switch ($method) {
           case 'GET':
               if (isset($urlList[3]) && $urlList[3] === 'chain') {
                   // GET /api/inspection/{id}/chain
                   getInspectionChain($inspectionId);

               } elseif (count($urlList) == 3) {
                   // GET /api/inspection/{id}
                   getFullInfo($inspectionId);
               } else {
                   setHTTPSStatus("404", "Incorrect path for inspection");
               }
               break;
   
           case 'PUT':
               if (count($urlList) == 3) {
                   // PUT /api/inspection/{id}
                   editInspection($inspectionId, $requestData);
               } else {
                   setHTTPSStatus("404", "Incorrect path for inspection");
               }
               break;
   
           default:
               setHTTPSStatus("405", "Incorrect path for inspection");
               break;
       }
   }
   