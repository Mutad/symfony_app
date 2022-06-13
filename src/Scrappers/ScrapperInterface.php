<?php

namespace App\Scrappers;

interface ScrapperInterface
{
    /**
     * @return string
     */
    public function getBodySelector(): string;

    /**
     * @return string
     */
    public function getTitleSelector(): string;

    /**
     * @return string
     */
    public function getImageSelector(): string;

    /**
     * @return array
     */
    public function parsePage(\Symfony\Component\DomCrawler\Crawler $crawler): array;
}