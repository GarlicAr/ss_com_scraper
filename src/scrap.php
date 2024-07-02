<?php
require_once __DIR__ . '/../vendor/autoload.php';


use Spekulatius\PHPScraper\PHPScraper;
use Arvid\Test\models\Observer;

class Scrap implements Subject {
    private $observers = [];
    private $lastFetchedAds = [];


    public function attach(Observer $observer)
    {
        $this->observers[] = $observer;
    }

    public function detach(Observer $observer)
    {
        $this->observers = array_filter($this->observers, function ($o) use ($observer) {
            return $o !== $observer;
        });
    }

    public function notify($adDetails)
    {
        foreach ($this->observers as $observer) {
            $observer->update($adDetails);
        }
    }

    public function fetchAds()
    {
        $web = new \Spekulatius\PHPScraper\PHPScraper;

        $web->go('https://www.ss.com/lv/real-estate/flats/riga/all/');

        $headers = $web->filter("//*[@id='head_line']//td")->each(function($node) {
            return trim($node->text());
        });

        // Extract table rows
        $rows = $web->filter("//tr[starts-with(@id, 'tr_')]")->each(function($node) {
            // Ensure the node exists before trying to access it
            $linkNode = $node->filter("td.msg2 a.am");
            $link = $linkNode->count() > 0 ? 'https://www.ss.com' . $linkNode->attr('href') : null;

            return [
                'link' => $link
            ];
        });

        echo "Rows with Links:\n";
        foreach ($rows as $row) {
            echo "Link: " . $row['link'] . "\n";

            if ($row['link']) {
                $detailPage = new PHPScraper();
                $detailPage->go($row['link']);

                $detailInfo = Scrap::extractInfo($detailPage);

                print_r($detailInfo);

                $this->notify($detailInfo);

                $this->lastFetchedAds[] = $row['link'];

                break;
            }
        }
    }


    function extractInfo($detailPage)
    {
        if(!$detailPage){
            return null;
        }

        try {
            $info = [];
            $detailPage->filter("//table[@class='options_list']//tr")->each(function($node) use (&$info) {
                $label = $node->filter("td.ads_opt_name")->text();
                $value = $node->filter("td.ads_opt")->text();
                $info[trim($label, ":")] = trim($value);
            });

            $priceLabel = $detailPage->filter("//*[@class='ads_opt_name_big']")->text();
            $price = $detailPage->filter("//*[@class='ads_price']")->text();
            $info[trim($priceLabel, ":")] = trim($price);

            $description = $detailPage->filter("//*[@id='msg_div_msg']")->text();
            $info[trim("Description", ":")] = trim($description);

            $detailPage->filter("//*[@class='ads_photo_label']")->each(function($node) use (&$info){
                $photo_link= $node->filter("a")->attr('href');
                $info[trim("Photo_link", ":")] = trim($photo_link);
            });

            return $info;

        }catch (Exception $e) {
            return $e->getMessage();
        }

    }


}


?>
