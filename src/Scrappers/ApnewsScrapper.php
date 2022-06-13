<?php

namespace App\Scrappers;

class ApnewsScrapper implements ScrapperInterface
{
    private static string $bodySelector = '#root > div > main > div.Body > div > div.Article > p';
#root > div > main > div.Body > div > div.Article
#root > div > main > div.Body > div > article > p

    private static string $titleSelector = '#root > div > main > div.Body > div > div.CardHeadline h1';

    private static string $imageSelector = '#root > div > main > div.Body > div > .LeadFeature';

    #root > div > main > div.Body > div > a > div.placeholder-0-2-57.undefined > img

    public function getBodySelector(): string
    {
        return self::$bodySelector;
    }

    public function getTitleSelector(): string
    {
        return self::$titleSelector;
    }

    public function getImageSelector(): string
    {
        return self::$imageSelector;
    }

    public function parsePage(\Symfony\Component\DomCrawler\Crawler $crawler): array
    {
        $purl = parse_url($crawler->getUri());
//        dd($purl['host'] === 'apnews.com', explode('/', $purl['path'])[1] == 'article', explode('/', $purl['path']));
        if ($purl['host'] === 'apnews.com' && explode('/', $purl['path'])[1] == 'article') {
            $ret = [];
            $arr = $crawler->filter('script')->each(function ($node) use (&$ret) {
                // find text in string
                $text = $node->text();
                if (strpos($text, 'mainEntityOfPage') !== false) {
                    $ret['original_url'] = json_decode($text, true)['mainEntityOfPage']['@id'];
                }
                if (str_starts_with($node->text(), "window['titanium-config']")) {
                    // just a big string to explode later
                    $json = str_replace(["window['titanium-config'] = ", "window['titanium-state'] = ", "window['titanium-cacheConfig'] = "], '\n\n\n\n\n\nexploding key :D\n\n\n\n\n\n\n', $node->text());
                    $json = str_replace(';', '', $json);
                    $json = explode('\n\n\n\n\n\nexploding key :D\n\n\n\n\n\n\n', $json);
                    $data = json_decode($json[2], true);
                    $data = $data['content']['data'];
                    $id = array_key_first($data);
                    if ($data[$id]['media']) {
                        $image = $data[$id]['media'][0];
                        $ret['image_url'] = $image['gcsBaseUrl'] . $image['imageRenderedSizes'][0] . $image['imageFileExtension'];
                    } else {
                        // no image found
                        $ret['image_url'] = '';
                    }
                    $ret['title'] = $data[$id]['headline'];
                    $ret['body'] = $data[$id]['storyHTML'];
                    $ret['lead'] = $data[$id]['flattenedFirstWords'];
                    $ret['original_uid'] = $data[$id]['shortId'];
                }
                return null;
            });
            return $ret;
            $arr = array_filter($arr);
            return $arr[array_key_first($arr)];
        }
//        dd('not apnews ', $purl);
        throw new \Exception('Not an APNews article');
    }
//    public function getBodySelector(): string
//    {
//        // TODO: Implement getBodySelector() method.
//    }
//
//    public function getTitleSelector(): string
//    {
//        // TODO: Implement getTitleSelector() method.
//    }
//
//    public function getImageSelector(): string
//    {
//        // TODO: Implement getImageSelector() method.
//    }
//
//    public function parsePage(\Symfony\Component\DomCrawler\Crawler $crawler): array
//    {
//        // TODO: Implement parsePage() method.
//    }
}