<?php

require_once 'classes/botClass.php';
$ChatBot = new botClass();

//=================================================
$AccessToken = "EAAfay6c6q9sBAHfZBGyjhVV6aLPisolfzKEyyJZCVAXwjyCzrzaGCtpyNjQIFR7Db56uU4qlxpy5ZB1MQIzaW6Q4zWvQcG2nZBhG7Bj8J3VtFpEtx2ikulID2gRqZAj03WynCJqOqFwqVvw0BgAVZChN6Bo0jnSKgJw0iCLHd6lDCDdNWL7Tgn";
$VerifyToken = "ChatBotVerificationMindValley";
$URL = "https://graph.facebook.com/v2.6/me/messages?access_token=".$AccessToken;
$GoodReadsAPIKey = "99CyLWIiIFoXXM68cq5LQ";
//=================================================
$WatsonToneAnalyserUsername='1e376f45-44a9-49d0-8f0c-9687fcc0a8dc';
$WatsonToneAnalyserPassword='O3z4BQZemMsx';
$WatsonToneAnalyserURL='https://gateway.watsonplatform.net/tone-analyzer/api/v3/tone?version=2017-09-21';
//=================================================

$ChatBot->AccessToken = $AccessToken;
$ChatBot->VerifyToken = $VerifyToken;
$ChatBot->GoodReadsAPIKey = $GoodReadsAPIKey;
$ChatBot->URL = $URL;
$ChatBot->Send();


?>