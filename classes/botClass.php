<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of botClass
 *
 * @author jeral_000
 */
require_once 'WatsonToneAnalyser.php';


class botClass {
    //-----------------------------
    public $VerifyToken = null;
    public $AccessToken = null;
    public $URL = null;
    public $GoodReadsAPIKey;
    public $WatsonToneAnalyserUsername;
    public $WatsonToneAnalyserPassword;
    public $WatsonToneAnalyserURL; 
    
    //-----------------------------
    private $HubVerifyToken = null;
    private $DetailsGraphURL = null;
    private $Message;
    private $Sender;
    private $UserFirstName;
    private $UserLastName;
    private $UserFullName;
    private $PostBack;
    private $WatsonToneAnalyser;
    //-----------------------------
    function __construct() {
        $this->WatsonToneAnalyser = new WatsonToneAnalyser();
        $this->WatsonToneAnalyser->WatsonToneAnalyserUsername = $this->WatsonToneAnalyserUsername;
        $this->WatsonToneAnalyser->WatsonToneAnalyserPassword = $this->WatsonToneAnalyserPassword;
        $this->WatsonToneAnalyser->WatsonToneAnalyserURL = $this->WatsonToneAnalyserURL;
    }

    private function Initial() {
        if (isset($_REQUEST['hub_mode']) && $_REQUEST['hub_mode'] == 'subscribe') {
            $Challenge = $_REQUEST['hub_challenge'];
            $this->HubVerifyToken = $_REQUEST['hub_verify_token'];
            if ($this->HubVerifyToken === $this->VerifyToken) {
                header('HTTP/1.1 200 OK');
                echo $Challenge;
                die;
            }
        }

        $Input = json_decode(file_get_contents('php://input'), true);
        $this->Message = strtolower(isset($Input['entry'][0]['messaging'][0]['message']['text']) ? $Input['entry'][0]['messaging'][0]['message']['text'] : ''); // get User message in LowerCase
        $this->Sender = $Input['entry'][0]['messaging'][0]['sender']['id'];
        $this->DetailsGraphURL = "https://graph.facebook.com/" . $this->Sender . "?fields=first_name,last_name&access_token=" . $this->AccessToken; // Optiones fields=first_name,last_name,profile_pic,id
        $DetailsArray = json_decode(file_get_contents($this->DetailsGraphURL), true);
        $this->UserFirstName = $DetailsArray["first_name"];
        $this->UserLastName = $DetailsArray["last_name"];
        $this->UserFullName = $this->UserFirstName . " " . $this->UserLastName;
        $this->PostBack = $Input['entry'][0]['messaging'][0]['postback']; // get User responce by PostBack
        if (!empty($this->PostBack)) {
            $this->PostBack();
        }


        return false;
    }

    private function FormatTextToSend($ReplyMessage) {
        $Data = '{
                    "recipient":{
                        "id":"' . $this->Sender . '"
                    },
                    "message":{
                        "text":"' . $ReplyMessage . '"
                    }
                }';
        return $Data;
    }

    private function Replies() {
        $ReplyMessage = "";
        if ($this->Message == "hi" || $this->Message == "hey" || $this->Message == "hello" || $this->Message == "yo" || $this->Message == "hey man" ||
                $this->Message == "hi man" || $this->Message == "hello man" || $this->Message == "you there" || $this->Message == "what's up" || $this->Message == "whats up" ||
                $this->Message == "what's up man" || $this->Message == "whats up man" || $this->Message == "what up" || $this->Message == "what up man") { //The Usual types of greethings
            $ReplyMessage = "Hello " . $this->UserFullName . ". Do you want to search books by name or by ID (Goodreads ID)?";
            $JsonData = $this->FormatTextToSend($ReplyMessage);
        } else if ($this->Message == "by name" || $this->Message == "name") {
            $ReplyMessage = "Please give me the book name.";
            $JsonData = $this->FormatTextToSend($ReplyMessage);
        } else if ($this->Message == "by id" || $this->Message == "id") {
            $ReplyMessage = "Please give me the book ID.";
            $JsonData = $this->FormatTextToSend($ReplyMessage);
        } else {
            $JsonData = $this->GoodReadsSearch();
        }

        $this->SendMessage($JsonData);
    }

    public function Send() {
        $this->Initial();

        if ($this->Message) {
            $this->Replies();
        }
        return false;
    }

    private function SendMessage($JsonData) {
        $DataMarkSeen = $this->SendActionMarkSeen();
        $this->CurlSend($DataMarkSeen);

        $DataTypingOn = $this->SendActionTypingOn();
        $this->CurlSend($DataTypingOn);
        sleep(3);

        $DataTypingOff = $this->SendActionTypingOff();
        $this->CurlSend($DataTypingOff);

        $this->CurlSend($JsonData);

        return false;
    }

    private function SendActionMarkSeen() {
        $Data = '{
                    "recipient":{
                        "id":"' . $this->Sender . '"
                    },
                    "sender_action":"mark_seen"
                }';
        return $Data;
    }

    private function SendActionTypingOn() {
        $Data = '{
                    "recipient":{
                        "id":"' . $this->Sender . '"
                    },
                    "sender_action":"typing_on"
                }';
        return $Data;
    }

    private function SendActionTypingOff() {
        $Data = '{
                    "recipient":{
                        "id":"' . $this->Sender . '"
                    },
                    "sender_action":"typing_off"
                }';
        return $Data;
    }

    private function CurlSend($JsonData) {
        $Ch = curl_init($this->URL);
        curl_setopt($Ch, CURLOPT_POST, 1);
        curl_setopt($Ch, CURLOPT_POSTFIELDS, $JsonData);
        curl_setopt($Ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($Ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($Ch);
        curl_close ($Ch);
    }

    private function PostBack() {
        $PostBackDataID = $this->PostBack['payload'];
        $PostBackDataAction = $this->PostBack['title'];
        
        if ($PostBackDataAction == "Get Reviews") {
            $this->GoodReadsGetReviewByID($PostBackDataID);
        } else if ($PostBackDataAction == "Suggestion to buy?") {
            $this->GoodReadsGetReviewForBuySuggestion($PostBackDataID);
        }
    }

    private function GoodReadsGetReviewForBuySuggestion($GoodReadersID) {
        $ReplyMessage = "";
        $GoodReadsGetReviewsByIdURL = "https://www.goodreads.com/book/show/" . $GoodReadersID . ".xml?key=" . $this->GoodReadsAPIKey;
        $XmlData = file_get_contents($GoodReadsGetReviewsByIdURL);
        $GoodReadsGetReviewsByIdURL_XML = new SimpleXMLElement($XmlData);
        if (strlen($GoodReadsGetReviewsByIdURL_XML->book->title) > 2) {
            $ReviewsTextArray = $this->GetSrcWidgetOfReviews($GoodReadsGetReviewsByIdURL_XML->book->reviews_widget);

            $ReplyMessage = $this->getBookReviewsForSuggestion($ReviewsTextArray, $GoodReadsGetReviewsByIdURL_XML->book->title);
            
            return;
        } else {
            $ReplyMessage = "Sorry Couldn't find any Review on " . $GoodReadsGetReviewsByIdURL_XML->book->title . " to analysis for buy suggestion.";
            $JsonData = $this->FormatTextToSend($ReplyMessage);
            $this->SendMessage($JsonData);
            return;
        }
    }

    private function getBookReviewsForSuggestion($ReviewsTextArray, $BookTitle) { /// handle tone analyser for buy suggestion
        $Text = "";
        for ($i = 0; $i < 3; $i++) {
            $ReviewsTextArray[$i] = $this->TrimAllSpaces($ReviewsTextArray[$i]);
            $Text = $Text." ".$ReviewsTextArray[$i];
        }
        
        $Decision = $this->WatsonToneAnalyser->getTextAnalysis($Text);
        
        $ReplyMessage = "Based on the performed Tone Analysis on the top 5 recent reviews, the '" . $BookTitle . "' ".$Decision;
        $JsonData = $this->FormatTextToSend($ReplyMessage);
        $this->SendMessage($JsonData);
        return;
    }

    private function GoodReadsGetReviewByID($GoodReadersID) {
        $ReplyMessage = "";
        $GoodReadsGetReviewsByIdURL = "https://www.goodreads.com/book/show/" . $GoodReadersID . ".xml?key=" . $this->GoodReadsAPIKey;
        $XmlData = file_get_contents($GoodReadsGetReviewsByIdURL);
        $GoodReadsGetReviewsByIdURL_XML = new SimpleXMLElement($XmlData);
        if (strlen($GoodReadsGetReviewsByIdURL_XML->book->title) > 2) {
            $ReviewsTextArray = $this->GetSrcWidgetOfReviews($GoodReadsGetReviewsByIdURL_XML->book->reviews_widget);

            $this->getBookReviews($ReviewsTextArray, $GoodReadsGetReviewsByIdURL_XML->book->title);
            return;
        } else {
            $ReplyMessage = "Sorry Couldn't find any Review on " . $GoodReadsGetReviewsByIdURL_XML->book->title . ".";
            $JsonData = $this->FormatTextToSend($ReplyMessage);
            $this->SendMessage($JsonData);
            return;
        }
    }

    private function GetSrcWidgetOfReviews($Widget) {
        $ReviewsText = array();
        $DOM = new DOMDocument();
        $DOM->loadHTML($Widget);
        $xPath = new DOMXPath($DOM);
        $IframeSRC = $xPath->evaluate("string(//iframe/@src)");
        $IframeSrcHtml = file_get_contents($IframeSRC);
        $DOM->loadHTML($IframeSrcHtml);
        $xPathIframe = new DOMXpath($DOM);
        $LinksElements = $xPathIframe->query('//div[contains(@class, "gr_review_text")]/text()');

        foreach ($LinksElements as $link) {
            $Trim = trim($link->nodeValue);
            if (!empty($Trim)) {
                $ReviewsText[] = $link->nodeValue;
            }
        }

        return $ReviewsText;
    }

    private function GoodReadsSearch() {

        if (is_numeric($this->Message)) {
            $GoodReadsGetTitleBasedOnIdURL = "https://www.goodreads.com/book/show/" . $this->Message . ".xml?key=" . $this->GoodReadsAPIKey;
            $XmlData = file_get_contents($GoodReadsGetTitleBasedOnIdURL);
            $GoodReadsGetTitleBasedOnIdURL_XML = new SimpleXMLElement($XmlData);
            if (strlen($GoodReadsGetTitleBasedOnIdURL_XML->book->title) > 2) {
                $GoodReadsTitleSearchURL = "https://www.goodreads.com/search.xml?key=" . $this->GoodReadsAPIKey . "&q=" . $GoodReadsGetTitleBasedOnIdURL_XML->book->title;
                $XmlData = file_get_contents($GoodReadsTitleSearchURL);
                $GoodReadsTitleSearchURL_XML = new SimpleXMLElement($XmlData);

                $JsonData = $this->getBookByID($GoodReadsTitleSearchURL_XML);
                return $JsonData;
            } else {
                $ReplyMessage = "Sorry Couldn't find it! Please give me the book name.";
                $JsonData = $this->FormatTextToSend($ReplyMessage);
                return $JsonData;
            }
        } else {
            $GoodReadsTitleSearchURL = "https://www.goodreads.com/search.xml?key=" . $this->GoodReadsAPIKey . "&q=" . $this->Message;
            $XmlData = file_get_contents($GoodReadsTitleSearchURL);
            $GoodReadsTitleSearchURL_XML = new SimpleXMLElement($XmlData);
            if (!empty($GoodReadsTitleSearchURL_XML->search->results->work[0]->best_book->title)) {
                $JsonData = $this->getBooksItem($GoodReadsTitleSearchURL_XML);
                return $JsonData;
            } else {
                $ReplyMessage = "Sorry " . $this->UserFirstName . ", Couldn't find any book with that name. Could you please give me the book ID (Goodreads ID) ?";
                $JsonData = $this->FormatTextToSend($ReplyMessage);
                return $JsonData;
            }
        }
    }

    private function getBookReviews($ReviewsTextArray, $BookTitle) {
        $ReplyMessage = "Here are top Reviws for " . $BookTitle . ": \\n ";
        $JsonData = $this->FormatTextToSend($ReplyMessage);
        $this->SendMessage($JsonData);
        $Count = 1;
        for ($i = 0; $i < 5; $i++) {
            $ReviewsTextArray[$i] = $this->TrimAllSpaces($ReviewsTextArray[$i]);
            $ReplyMessage = "Review " . $Count . ": " . $ReviewsTextArray[$i] . " \\n ";
            $JsonData = $this->FormatTextToSend($ReplyMessage);
            $this->SendMessage($JsonData);
            $Count++;
        }

        return;
    }

    private function TrimAllSpaces($ReviewText) {
        for ($i = 0; $i < 20; $i++) {
            $ReviewText = trim($ReviewText);
        }
        return $ReviewText;
    }

    private function getBooksItem($GoodReadsTitleSearchURL_XML) {
        $Items = array();
        for ($i = 0; $i < 5; $i++) {
            $Items[] = array(
                'title' => "" . $GoodReadsTitleSearchURL_XML->search->results->work[$i]->best_book->title,
                'item_url' => "https://www.goodreads.com/book/show/" . $GoodReadsTitleSearchURL_XML->search->results->work[$i]->best_book->id . "-" . $GoodReadsTitleSearchURL_XML->search->results->work[$i]->best_book->title,
                'image_url' => "" . $GoodReadsTitleSearchURL_XML->search->results->work[$i]->best_book->image_url,
                'subtitle' => "Rating: " . $GoodReadsTitleSearchURL_XML->search->results->work[$i]->average_rating . "\nBy: " . $GoodReadsTitleSearchURL_XML->search->results->work[$i]->best_book->author->name,
                'buttons' => array(
                    array(
                        'type' => "web_url",
                        'url' => "https://www.goodreads.com/book/show/" . $GoodReadsTitleSearchURL_XML->search->results->work[$i]->best_book->id . "-" . $GoodReadsTitleSearchURL_XML->search->results->work[$i]->best_book->title,
                        'title' => "Show it on GoodReads"
                    ),
                    array(
                        'type' => "postback",
                        'title' => "Get Reviews",
                        'payload' => $GoodReadsTitleSearchURL_XML->search->results->work[$i]->best_book->id . ""
                    ),
                    array(
                        'type' => "postback",
                        'title' => "Suggestion to buy?",
                        'payload' => $GoodReadsTitleSearchURL_XML->search->results->work[$i]->best_book->id . ""
                    )
                )
            );
        }
        $ItemJson = json_encode($Items);
        $JsonData = '{
                        "recipient":{
                            "id":"' . $this->Sender . '"
                        },
                        "message":{
                            "attachment":{
                                "type":"template",
                                "payload":{
                                    "template_type":"generic",
                                    "elements":' . $ItemJson . '
                                }
                            }
                        }
                    }';
        return $JsonData;
    }

    private function getBookByID($GoodReadsTitleSearchURL_XML) {

        $Item[] = array(
            'title' => "" . $GoodReadsTitleSearchURL_XML->search->results->work[0]->best_book->title,
            'item_url' => "https://www.goodreads.com/book/show/" . $GoodReadsTitleSearchURL_XML->search->results->work[0]->best_book->id . "-" . $GoodReadsTitleSearchURL_XML->search->results->work[0]->best_book->title,
            'image_url' => "" . $GoodReadsTitleSearchURL_XML->search->results->work[0]->best_book->image_url,
            'subtitle' => "Rating: " . $GoodReadsTitleSearchURL_XML->search->results->work[0]->average_rating . "\nBy: " . $GoodReadsTitleSearchURL_XML->search->results->work[0]->best_book->author->name,
            'buttons' => array(
                array(
                    'type' => "web_url",
                    'url' => "https://www.goodreads.com/book/show/" . $GoodReadsTitleSearchURL_XML->search->results->work[0]->best_book->id . "-" . $GoodReadsTitleSearchURL_XML->search->results->work[0]->best_book->title,
                    'title' => "Show it on GoodReads"
                ),
                array(
                    'type' => "postback",
                    'title' => "Get Reviews",
                    'payload' => $GoodReadsTitleSearchURL_XML->search->results->work[0]->best_book->id . ""
                ),
                array(
                    'type' => "postback",
                    'title' => "Suggestion to buy?",
                    'payload' => $GoodReadsTitleSearchURL_XML->search->results->work[0]->best_book->id . ""
                )
            )
        );

        $ItemJson = json_encode($Item);
        $JsonData = '{
                        "recipient":{
                            "id":"' . $this->Sender . '"
                        },
                        "message":{
                            "attachment":{
                                "type":"template",
                                "payload":{
                                    "template_type":"generic",
                                    "elements":' . $ItemJson . '
                                }
                            }
                        }
                    }';
        return $JsonData;
    }
}
