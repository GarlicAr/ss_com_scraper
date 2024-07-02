<?php

namespace Arvid\Test\models;
use Arvid\Test\models\Observer;

class DiscordNotifier implements Observer
{
    private $webhookUrl;

    public function __construct($webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
    }

    public function update($adDetails)
    {
        $this->sendDiscordMessage($adDetails);
    }

    private function sendDiscordMessage($adDetails)
    {
        try {
            $requiredKeys = ['Cena', 'Pilsēta', 'Iela', 'Description', 'Photo_link'];
            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $adDetails)) {
                    throw new \Exception("Missing key: $key in adDetails");
                }
            }

            $adDetails = array_map('htmlspecialchars', $adDetails);
            $adDetails['Description'] = substr($adDetails['Description'], 0, 100);

            if(!filter_var($adDetails['Photo_link'], FILTER_VALIDATE_URL)) {
                throw new \Exception("Invalid URL: {$adDetails['Photo_link']}");
            }

            $data = [
                "content" => "=================================================\n\n
                **Cena**: {$adDetails['Cena']}\n
                **Vieta**: {$adDetails['Pilsēta']}\n
                **Iela**: {$adDetails['Iela']}\n
                **Rajons**: {$adDetails['Rajons']}\n
                **Description**: {$adDetails['Description']}\n
                **Link**: {$adDetails['LinkUrl']}\n
                {$adDetails['Photo_link']}\n\n"
            ];

            $jsonData = json_encode($data);

            error_log('DiscordNotifier Webhook URL: ' . $this->webhookUrl);

            $ch = curl_init($this->webhookUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);

            if ($response === false) {
                throw new \Exception('cURL error: ' . curl_error($ch));
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode !== 204) {
                // Log unexpected HTTP response code
                throw new \Exception("Unexpected HTTP code: $httpCode. Response: $response");
            }

            curl_close($ch);

            return $response;

        } catch (\Exception $e) {
            // Log the exception message
            error_log('DiscordNotifier Error: ' . $e->getMessage());
            print 'Error: ' . $e->getMessage();
        }
    }
}
?>
