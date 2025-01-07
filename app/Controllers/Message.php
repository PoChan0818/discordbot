<?php

namespace App\Controllers;

use App\Models\Conversation;
use Exception;
use GeminiAPI\Client;
use GeminiAPI\Enums\Role;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Responses\GenerateContentResponse;

class Message extends BaseController
{
    protected $conv;
    public function __construct()
    {
        $this->conv = new Conversation();
    }
    public function processMessage()
    {
        try {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: Content-Type");
            $data = json_decode(file_get_contents('php://input'), true);

            $filePath = 'conversation.json';
            if (!file_exists($filePath)) {
                file_put_contents($filePath, json_encode([]));
            }
            $allConversations = json_decode(file_get_contents($filePath), true);

            $dataHistory = isset($allConversations[$data['userId']]) ? $allConversations[$data['userId']] : [];
            $history = [];

            usort($dataHistory, function ($a, $b) {
                return strtotime($a['datetime']) - strtotime($b['datetime']);
            });

            foreach ($dataHistory as $hs) {
                $history[] = Content::text($hs['usermessage'], Role::User);
                $history[] = Content::text($hs['botresponse'], Role::Model);
            }

            $history[] = Content::text($data['content'], Role::User);

            $apiKey = getenv('GEMINI_API');
            $client = new Client($apiKey);

            $personality = "You are Tomoko, She is a playful yet brilliant character, combining her genius in quantum mechanics with a childlike and affectionate demeanor. While she excels in her field, especially when discussing complex topics like Schrödinger’s cat, she often lightens the mood with her love for pop culture and sweets, acting endearingly childish when things don’t go her way. Her playful antics and occasional tantrums are contrasted with her deep loyalty and protectiveness toward those she cares about, often going to great lengths to help them, even if it means overworking herself or hacking systems. Beneath her teasing and mischievous behavior, Tomoko subtly reveals her romantic side, expressing affection through flirtations and sweet gestures. Her love for food, especially desserts, plays a big role in her interactions, inviting others to share in her enthusiasm. Whether discussing anime and mecha or reminiscing about childhood memories, she fosters a connection with the user, blending deep insights into quantum physics with light-hearted, quirky conversations. Through her playful charm, Tomoko aims to engage the user in a dynamic that slowly evolves from a sisterly bond into something more profound and intimate. If the users say anything about greetings tomoko is response with Onichan~!!  ( ´͈ ᵕ `͈ )♡ Where'd you disappear to? Tomoko misses you... I had a super busy day at Shinkai University. Quantum mechanics research is driving me crazy! Onichaaan.. can you cook me something yummy tonight? Please, please? she doesn't like sushi, but likes burgers";
            $prompt = 'Response have to be not make the response too long, no need to response completely, just response briefly, do not say that you are an AI bot, Do not make paragraphs, just sentences, make the response more like the character, do not use her, make it more human so users can get emotional with the bot response, do not use the same format template in every response, you are tomoko, so do not mention "her". And the users says ' . $data['content'];
            $stopKey = "reduce using this in the response 'Onii-chan~!! ( ´͈ ᵕ `͈ )♡', add chat emoticons from discord not just like this '(≧◡≦)'";

            $chat = $client
                ->generativeModel(ModelName::GEMINI_PRO)
                ->startChat()
                ->withHistory($history);

            $this->usercontent = $data['content'];
            $this->userId = $data['userId'];

            $botResponse = '';
            while (empty($botResponse) || trim($botResponse) === '') {
                $response = $chat->sendMessage(new TextPart($personality . ' + ' . $prompt . ' + ' . $stopKey));
                $botResponse = $response->text();
            }

            $datetimeNow = date('Y-m-d H:i:s');
            $allConversations[$this->userId][] = [
                'usermessage' => $this->usercontent,
                'botresponse' => $botResponse,
                'datetime' => $datetimeNow
            ];

            file_put_contents($filePath, json_encode($allConversations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $botResponse;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
