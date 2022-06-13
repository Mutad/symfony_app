<?php

namespace App;

use App\Message\PageParseMessage;
use App\Repository\PostRepository;
use App\Scrappers\ScrapperInterface;
use Goutte\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Scrapper
{
    private static Serializer $serializer;
    private MessageBusInterface $messageBus;
    private LoggerInterface $logger;
    private PostRepository $postRepository;

    public function __construct(MessageBusInterface $messageBus, LoggerInterface $scrapperLogger, PostRepository $postRepository)
    {
        $this->messageBus = $messageBus;
        $this->logger = $scrapperLogger;
        $this->postRepository = $postRepository;
        self::$serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
    }

    /**
     * @param string $url
     * @return string body of the page
     */
    public function getBody(string $url): string
    {
        $scrapper = ($this->getScrapper($url));
        if ($scrapper) {
            $client = new Client();
            $crawler = $client->request('GET', $url);
            return implode('<br>',
                $crawler->filter($scrapper->getBodySelector())->each(function ($node) {
                    return $node->text();
                })
            );
        }
        return '';
    }

    public function getScrapper(string $url): ?ScrapperInterface
    {
        $scrapper = null;
        $url = parse_url($url);
        if (isset($url['host'])) {
            $host = $url['host'];
            $host = str_replace(' ', '', ucwords(
                    implode(
                        ' ',
                        array_slice(explode('.', $host), 0, -1)
                    )
                )
            );
            $class = 'App\Scrappers\\' . ucfirst($host) . 'Scrapper';
            if (class_exists($class)) {
                $scrapper = new $class();
            }
        }
        return $scrapper;
    }

    public function parseAdditionalLinks(Entity\Post $post, int $level = 0)
    {
        $this->logger->info('Parsing additional links');
        $links = self::findLinks($post->getBody());
//        dd($links);

        $this->logger->info(self::$serializer->serialize($links, 'json'));

        foreach ($links as $link) {
            $this->logger->info('Dispatching message for link ' . $link);
            $this->messageBus->dispatch(new PageParseMessage($link, $post, $level));
//            $pages[] = self::parsePage($link);
        }

        return;
//        return $scrapper->parseAdditionalLinks($crawler, $getPost);
    }

    private function findLinks(string $text): array
    {
        preg_match_all("#<a\s[^>]*href\s*=\s*[\'\"]??\s*?(?'path'[^\'\"\s]+?)[\'\"\s]{1}[^>]*>(?'name'[^>]*)<#simU", $text, $matches);
        return array_unique($matches['path']);
    }

    public function parsePage(string $url): array
    {
        if ($found = $this->postRepository->findOneBy(['original_url' => $url])) {
            $this->logger->info('Page already parsed');
            throw new \PageExistsException('Page already parsed');
        }
        $this->logger->info('Parsing page ' . $url);
        $scrapper = ($this->getScrapper($url));
        if ($scrapper) {
            $client = new Client();
            $crawler = $client->request('GET', $url);

            $page = $scrapper->parsePage($crawler);
            $this->logger->info('Page parsed, ', $page);
            return $page;
        }
        throw new \Exception('Scrapper for this url not found');
    }
}