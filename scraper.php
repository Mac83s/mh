<?php

//++++++++++++++++++++++
//++
//+++ Screaper
//++
//++++++++++++++++++++++

function scrapeURL($url, $class) {
	require_once "support/web_browser.php";
	require_once "support/tag_filter.php";
    $htmloptions = TagFilter::GetHTMLOptions();

    $web = new WebBrowser();
    $result = $web->Process($url);

    if (!$result["success"]) {
        echo "Error retrieving URL.  " . $result["error"] . "\n";
        exit();
    }

    if ($result["response"]["code"] != 200) {
        echo "Error retrieving URL.  Server returned:  " . $result["response"]["code"] . " " . $result["response"]["meaning"] . "\n";
        exit();
    }

    $baseurl = $result["url"];
    $html = TagFilter::Explode($result["body"], $htmloptions);
    $root = $html->Get();
    $rows = $root->Find("$class");

    $combinedText = "";
    foreach ($rows as $row) {
        $text = $row->GetOuterHTML();
        $combinedText .= $text;
    }

    return $combinedText;
}





//++++++++++++++++++++++
//++
//+++ Screaper
//++
//++++++++++++++++++++++

function scrapescreendex($address, $blockchain, $class) {
	// Inicjalizacja biblioteki cURL
    $ch = curl_init();

    // Ustawienie opcji cURL
    curl_setopt($ch, CURLOPT_URL, "https://dexscreener.com/$blockchain/$address?embed=1&theme=dark&trades=0&info=1");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Wykonanie żądania i pobranie zawartości strony
    $html = curl_exec($ch);

    // Zamknięcie sesji cURL
    curl_close($ch);

    // Utworzenie obiektu DOM dla pobranej zawartości HTML
    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    // Utworzenie obiektu XPath
    $xpath = new DOMXPath($dom);

    // Znalezienie elementu klasy "custom-1baulvz" i pobranie jego zawartości
    $elements = $xpath->query('//div[contains(@class,"'.$class.'")]');
    if ($elements->length > 0) {
        $value = trim($elements->item(0)->nodeValue);
        return $value;
    }

}




