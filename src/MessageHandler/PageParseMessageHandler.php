<?php

namespace App\MessageHandler;

use App\Entity\Category;
use App\Entity\Post;
use App\Message\PageParseMessage;
use App\Scrapper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

//use Doctrine\ORM\EntityManagerInterface;
//use Psr\Log\LoggerAwareInterface;
//use Psr\Log\LoggerInterface;

//use Symfony\Component\Messenger\MessageBusInterface;

final class PageParseMessageHandler implements MessageHandlerInterface
{
    private LoggerInterface $logger;
    private MessageBusInterface $eventBus;
    private EntityManagerInterface $em;
    private Scrapper $scrapper;

    public function __construct(MessageBusInterface $eventBus, EntityManagerInterface $em, LoggerInterface $scrapperLogger, Scrapper $scrapper)
    {
        $this->eventBus = $eventBus;
        $this->em = $em;
        $this->logger = $scrapperLogger;
        $this->scrapper = $scrapper;
    }

    public function __invoke(PageParseMessage $message)
    {

        try {
            // parse page from url
            $data = $this->scrapper->parsePage($message->getUrl());
            $post = new Post();
            $post->setTitle($data['title']);
            $post->setLead($data['lead']);
            $post->setBody($data['body']);
            $post->setImageUrl($data['image_url']);
            $post->setOriginalUid($data['original_uid']);
            $post->setOriginalUrl($data['original_url']);
            $post->setCategory($this->em->getRepository(Category::class)->find(1));
            if (!$this->em->isOpen()) {
                $this->em = $this->em->create(
                    $this->em->getConnection(),
                    $this->em->getConfiguration()
                );
            }
            $this->em->persist($post);
            $this->em->flush();
        } catch (\PageExistsException $e) {
            $post = $this->em->getRepository(Post::class)->findOneBy(['original_url' => $message->getUrl()]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return;
        }
        $this->logger->info('Page parsed or found');
        //if url is found in some post, then update url of that post to new one
        if ($foundIn = $this->em->getRepository(Post::class)->find($message->getFoundInPost()->getId())) {
            $this->logger->info('Replacing links');

            $foundIn->setBody(str_replace($message->getUrl(), '/posts/' . $post->getId(), $foundIn->getBody()));
            $this->logger->info('Body set');
//            if (!$this->em->isOpen()) {
//                $this->em = $this->em->create(
//                    $this->em->getConnection(),
//                    $this->em->getConfiguration()
//                );
//            }
//            $this->em->persist($foundIn);
//            $this->em->merge($foundIn);
            $this->em->flush();
//            $this->em->getRepository(Post::class)->
            $this->logger->info('saving to db');

            $this->logger->info('Links replaced for post: ' . $foundIn->getId());
        }

        if ($message->getLevel() > 0) {
            $this->logger->info('Sending message to next level');

            $this->logger->info('Parsing links for page ' . $message->getUrl());
            $this->scrapper->parseAdditionalLinks($post, $message->getLevel() - 1);

            $this->logger->info('Message sent to next level');
        }
    }
}
