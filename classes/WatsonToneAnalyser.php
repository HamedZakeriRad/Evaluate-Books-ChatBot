<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GoodReaderClass
 *
 * @author jeral_000
 */
class WatsonToneAnalyser {

    public $WatsonToneAnalyserUsername;
    public $WatsonToneAnalyserPassword;
    public $WatsonToneAnalyserURL;

    public function getTextAnalysis($Text) {
        $JasonTextData = json_encode(array('text' => "$Text"));
        $CurlResult = $this->CurlSendData($JasonTextData);
        $EmotionResult = $this->getHigherEmotionValueOfTheText($CurlResult);
        $Decision = $this->CheckEmotions($EmotionResult);
        return $Decision;
    }

    private function CurlSendData($JasonTextData) {
        $Ch = curl_init();
        curl_setopt($Ch, CURLOPT_URL, $this->WatsonToneAnalyserURL);
        curl_setopt($Ch, CURLOPT_TIMEOUT, 30); 
        curl_setopt($Ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($Ch, CURLOPT_USERPWD, "$this->WatsonToneAnalyserUsername:$this->WatsonToneAnalyserPassword");
        curl_setopt($Ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($Ch, CURLOPT_POST, true);
        curl_setopt($Ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($Ch, CURLOPT_POSTFIELDS, $JasonTextData);

        $CurlResult = curl_exec($Ch);
        curl_close($Ch);
        return json_decode($CurlResult);
    }

    private function getHigherEmotionValueOfTheText($CurlResult) {
        $EmotionScore = array();
        $EmotionToneID = array();
        
        foreach ($CurlResult->document_tone->tone_categories as $result) {
            foreach ($result->tones as $tone) {
                $EmotionScore[] = $tone->score;
                $EmotionToneID[] = $tone->tone_id;
            }
        }

        $ArrayIndex = array_search(max($EmotionScore), $EmotionScore);
        $EmotionResult["EmotionScore"] = $EmotionToneID[$ArrayIndex]*100;
        $EmotionResult["EmotionToneID"] = $EmotionToneID[$ArrayIndex];
        
        
        return $EmotionResult;
    }

    private function CheckEmotions($EmotionResult) {
        $Decision = "";
        switch ($EmotionResult["EmotionToneID"]) {
            case "anger":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% anger in the reviews. We dont suggest purchasing this book.";
                break;
            case "disgust":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% disgust in the reviews. We dont suggest purchasing this book.";
                break;
            case "fear":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% fear in the reviews. We dont suggest purchasing this book.";
                break;
            case "joy":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% joy in the reviews. We suggest purchasing this book.";
                break;
            case "sadness":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% sadness in the reviews. We dont suggest purchasing this book.";
                break;
            case "analytical":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% analytical in the reviews. We dont suggest purchasing this book.";
                break;
            case "confident":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% confident in the reviews. We suggest purchasing this book.";
                break;
            case "tentative":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% tentative in the reviews. We dont suggest purchasing this book.";
                break;
            case "openness_big5":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% emotion in the reviews. We suggest purchasing this book.";
                break;
            case "conscientiousness_big5":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% emotion in the reviews. We suggest purchasing this book.";
                break;
            case "extraversion_big5":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% emotion in the reviews. We suggest purchasing this book.";
                break;
            case "agreeableness_big5":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% emotion in the reviews. We suggest purchasing this book.";
                break;
            case "emotional_range_big5":
                $Decision = "has ".$EmotionResult["EmotionScore"]."% emotional range in the reviews. We suggest purchasing this book.";
                break;
        }
        return $Decision;
    }

}
