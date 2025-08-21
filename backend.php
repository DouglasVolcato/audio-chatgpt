<?php
header('Content-Type: application/json');

// Verifica se veio um arquivo
if (!isset($_FILES['file'])) {
    echo json_encode(["error" => "Nenhum arquivo recebido"]);
    exit;
}

$audioFile = $_FILES['file']['tmp_name'];

// Configuração da API
$apiKey = "API_KEY";

// 1. Transcreve o áudio com Whisper
$ch = curl_init("https://api.openai.com/v1/audio/transcriptions");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    "file" => new CURLFile($audioFile, "audio/webm", "recording.webm"),
    "model" => "whisper-1"
]);

$transcription = curl_exec($ch);
curl_close($ch);

$transData = json_decode($transcription, true);

if (isset($transData['error'])) {
    echo json_encode(["error" => "Transcription failed", "detail" => $transcription]);
    exit;
}

$userText = $transData['text'];

// 2. Envia a transcrição para o ChatGPT
$chatCh = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($chatCh, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $apiKey"
]);
curl_setopt($chatCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chatCh, CURLOPT_POST, true);
curl_setopt($chatCh, CURLOPT_POSTFIELDS, json_encode([
    "model" => "gpt-4o-mini",
    "messages" => [
        ["role" => "user", "content" => $userText]
    ]
]));

$chatResponse = curl_exec($chatCh);
curl_close($chatCh);

$chatData = json_decode($chatResponse, true);
$reply = $chatData['choices'][0]['message']['content'] ?? "Erro ao gerar resposta";

echo json_encode([
    "transcript" => $userText,
    "reply" => $reply
]);
