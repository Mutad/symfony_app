<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{

    /**
     * @param CategoryRepository $categoryRepository
     * @param $slug
     * @Route("/categories/{category_id}", name="category_show")
     * @return Response
     */
    public function show(ManagerRegistry $doctrine, $category_id): Response
    {
        $entityManager = $doctrine->getManager();
        $category = $entityManager->getRepository(Category::class)->find($category_id);
        $category->setViews($category->getViews() + 1);
        $entityManager->flush();

        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    public function indexNavigation(CategoryRepository $categoryRepository)
    {
        return $this->render('category/nav.html.twig', [
            'categories' => $categoryRepository->findSortedByViews(0, 10),
        ]);
    }

}
