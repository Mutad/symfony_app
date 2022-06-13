<?php

namespace App\Message;

use App\Entity\Post;

final class PageParseMessage
{
    /*
     * Add whatever properties & methods you need to hold the
     * data for this message class.
     */

    private string $pageUrl;
    private ?Post $foundInPost;
    private int $level;

    /**
     * @param $pageUrl url of the page to be parsed
     * @param Post|null $foundInPost add backward links to this post
     * @param integer $level specify level to perform recursive parsing
     */
    public function __construct($pageUrl, Post $foundInPost = null, $level = 0)
    {
        $this->pageUrl = $pageUrl;
        $this->foundInPost = $foundInPost;
        $this->level = $level;
    }

    public function getUrl(): string
    {
        return $this->pageUrl;
    }

    public function getFoundInPost(): ?Post
    {
        return $this->foundInPost;
    }

    public function getLevel(): int
    {
        return $this->level;
    }
}
