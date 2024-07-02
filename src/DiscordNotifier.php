<?php

class DiscordNotifier implements Observer{
    private $webhookUrl;

    public function __construct($webhookUrl) {
        $this->webhookUrl = $webhookUrl;
    }

    public function update($adDetails)
    {
        //TODO
    }

    private function sendDiscordMessage($adDetails) {
        $data = [
            "content" => "New Apartment Ad:\nPrice: {$adDetails['Price']}\nPlace: {$adDetails['Place']}\nIela: {$adDetails['Iela']}\nDescription: {$adDetails['Description']}\nLink: {$adDetails['link']}"
        ];

        $jsonData = json_encode($data);

        $ch = curl_init($this->webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        $response = curl_exec($ch);
        curl_close($ch);
    }

}

?>