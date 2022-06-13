<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Message\PageParseMessage;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Scrapper;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;


class PostController extends AbstractController
{

    /**
     * @Route("/posts/test", name="post_test")
     */
    public function test(PostRepository $postRepository, MessageBusInterface $messageBus, LoggerInterface $logger, Scrapper $scrapper)
    {
        dd($this->getUser());

        $messageBus->dispatch(new PageParseMessage('https://apnews.com/article/russia-ukraine-europe-war-crimes-d413e4dd5612045d73ce37b898c2c735', $postRepository->find(18)));
//        $logger->info('test');
//        $messageBus->dispatch(new PageParseMessage());

//        $scrapper->parseAdditionalLinks($postRepository->find(18));
//        dd(Scrapper::parsePage('https://apnews.com/f81540846bd0163f5820206f2722369e'), 'test');
        return new Response('OK');
//        $client = new Client();
//        $text = '';
//        $crawler = $client->request('GET', 'https://apnews.com/b681938a1d8b382bd1fe76094d6c5333');
//        dd($client->getResponse());
//        $client->getResponse()->isRedirect();
//        $crawler = $client->followRedirect();
//        $crawler->filter('*')->each(function ($node) use ($text) {
////            $text .= $node->text() . "\n";
//            echo $node->text() . "<br><br>";
//        });
//        return $this->json(['text' => $text]);
    }

    /**
     * @Route("/posts/new", name="app_post_new")
     */
    public function new(Request $request, CategoryRepository $categoryRepository, PostRepository $postRepository, MessageBusInterface $messageBus, Scrapper $scrapper): Response
    {
        $post = new Post();

        $form = $this->createFormBuilder($post)
            ->add('title', TextType::class, [
                'attr' => [
                    'placeholder' => 'Article title',
                ]
            ])
            ->add('lead', TextType::class,
                [
                    'attr' => [
                        'placeholder' => 'Lead',
                    ]
                ])
            ->add('body', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Body',
                ]
            ])
            ->add('image_url', TextType::class, [
                'attr' => [
                    'placeholder' => 'Image URL',
                ]
            ])
            ->add('category', ChoiceType::class, [
                'choices' => $categoryRepository->findAll(),
                'choice_label' => 'name',
                'choice_value' => 'id',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Create Post', 'attr' => [
                    'class' => 'btn btn-primary rounded p-3'
                ]
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Post $post */
            $post = $form->getData();
            try {
                if (filter_var($post->getBody(), FILTER_VALIDATE_URL)) {
                    $data = $scrapper->parsePage($post->getBody());
                    $post->setTitle($data['title']);
                    $post->setLead($data['lead'] ?? $data['title']);
                    $post->setBody($data['body']);
                    $post->setImageUrl($data['image_url']);
                    $post->setOriginalUid($data['original_uid']);
                    $post->setOriginalUrl($data['original_url']);
                }
                else{
                    $post->setOriginalUrl('');
                    $post->setOriginalUid('');
                }

                $postRepository->add($post);
                $scrapper->parseAdditionalLinks($post);
            } catch (\Exception $e) {
                dd($e);
                return $this->redirectToRoute('app_post_new');
            }

//            $messageBus->dispatch(new PageParseMessage($post));

            return $this->redirectToRoute('app_post_show', ['post_id' => $post->getId()]);
        }

        return $this->renderForm('posts/new.html.twig', ['form' => $form]);
    }

    /**
     * @Route("/posts", name="app_post_index")
     */
    public function index(CategoryRepository $categoryRepository, PostRepository $postRepository): Response
    {
        return $this->render('posts/index.html.twig', [
            'controller_name' => 'PostController',
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    /**
     * @Route("/posts/{post_id}", name="app_post_show")
     */
    public function show(Request $request, $post_id, ManagerRegistry $doctrine, CommentRepository $commentRepository): Response
    {
        $entityManager = $doctrine->getManager();
        $post = $entityManager->getRepository(Post::class)->find($post_id);
        if (!$post) {
            throw $this->createNotFoundException(
                'No product found for id ' . $post_id
            );
        }
        $category = $post->getCategory();
        $category->setViews($category->getViews() + 1);
        $post->setViews($post->getViews() + 1);
        $entityManager->flush();


        $comment = new Comment();
        $form = $this->createFormBuilder($comment)
            ->add('content', TextareaType::class, [
                'label' => 'Write your comment',
                'attr' => [
                    'placeholder' => 'Content',
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Post Comment', 'attr' => [
                    'class' => 'btn btn-primary rounded p-3'
                ]
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
            /** @var Comment $comment */
            $comment = $form->getData();
            $comment->setUser($this->getUser());
            $comment->setPost($post);

            $commentRepository->add($comment);
        }


        return $this->renderForm('posts/show.html.twig', [
            'controller_name' => 'PostController',
            'post' => $post,
            'form' => $form,
        ]);


//        return $this->redirectToRoute('app_post_index');
//            throw $this->createNotFoundException('Post not found');

    }

    /**
     * @Route("/posts/{post_id}/parse", name="app_post_parse")
     * @param $post_id
     * @return Response
     */
    public function parseLinks($post_id, Scrapper $scrapper, PostRepository $postRepository): Response
    {
        $post = $postRepository->find($post_id);
        $scrapper->parseAdditionalLinks($post, 20);
        return $this->redirectToRoute('app_post_show', ['post_id' => $post_id]);
    }


    public function featuredCards(PostRepository $postRepository, $category = null)
    {
        return $this->render('posts/featured_cards.html.twig', [
            'posts' => $postRepository->findSortedByViews(0, 3, $category)
        ]);
    }


}


/*
 * tasks
 * 1. 2 entities: Post, Category. one-to-many relationship
 * 2. Symfony security bundle. User auth/registration
 * 3. Comments for posts
 */