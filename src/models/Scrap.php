<?php
namespace Arvid\Test\models;

use Spekulatius\PHPScraper\PHPScraper;

interface Subject
{
    public function attach(Observer $observer);
    public function detach(Observer $observer);
    public function notify($adDetails);
}

class Scrap implements Subject
{
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
        $web = new PHPScraper();

        $web->go('https://www.ss.com/lv/real-estate/flats/riga/all/');

        $rows = $web->filter("//tr[starts-with(@id, 'tr_')]")->each(function ($node) {
            // Ensure the node exists before trying to access it
            $linkNode = $node->filter("td.msg2 a.am");
            $link = $linkNode->count() > 0 ? 'https://www.ss.com' . $linkNode->attr('href') : null;

            return [
                'link' => $link
            ];
        });

        foreach ($rows as $row) {

            if ($row['link'] && !in_array($row['link'], $this->lastFetchedAds)) {
                $detailPage = new PHPScraper();
                $detailPage->go($row['link']);
                $detailInfo = $this->extractInfo($detailPage, $row['link']);

                if(isset($detailInfo['Cena']) && preg_match('/\d+\s?€\/mēn\./', $detailInfo['Cena'])){
                    $this->notify($detailInfo);

                    $this->lastFetchedAds[] = $row['link'];

                    echo "\n" . $row['link'];

                }
            }
        }
    }


    function extractInfo($detailPage, $link): array
    {
        if (!$detailPage) {
            return ['gae'];
        }

        try {
            $info = [];
            $detailPage->filter("//table[@class='options_list']//tr")->each(function ($node) use (&$info) {
                $label = $node->filter("td.ads_opt_name")->text();
                $value = $node->filter("td.ads_opt")->text();
                $info[trim($label, ":")] = trim($value);
            });

            $priceLabelNode = $detailPage->filter("//*[@class='ads_opt_name_big']");
            $priceNode = $detailPage->filter("//*[@class='ads_price']");
            if ($priceLabelNode->count() > 0 && $priceNode->count() > 0) {
                $priceLabel = $priceLabelNode->text();
                $price = $priceNode->text();

                $info[trim($priceLabel, ":")] = trim($price);
            }

            $descriptionNode = $detailPage->filter("//*[@id='msg_div_msg']");
            if ($descriptionNode->count() > 0) {
                $description = $descriptionNode->text();
                $info[trim("Description", ":")] = trim($description);
            }

            $detailPage->filter("//*[@class='ads_photo_label']")->each(function ($node) use (&$info) {
                $photoLinkNode = $node->filter("a");
                if ($photoLinkNode->count() > 0) {
                    $photo_link = $photoLinkNode->attr('href');
                    $info[trim("Photo_link", ":")] = trim($photo_link);
                }
            });

            $info[trim("LinkUrl", ":")] = $link;

            return $info;

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }

    }


}


?>
