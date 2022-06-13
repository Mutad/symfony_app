<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    /**
     * Create a new category
     *
     * @Route("/categories/new", name="categories")
     * @return Response
     */
    public function new (Request $request, CategoryRepository $categoryRepository)
    {
        $category = new Category();

        $form = $this->createFormBuilder($category)
            ->add('name', TextType::class)
            ->add('save', SubmitType::class, ['label' => 'Create'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Category $category */
            $category = $form->getData();
            $categoryRepository->add($category);

            return $this->redirectToRoute('categories');
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    /**
     * Show single category from database
     *
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


    /**
     * Function to show all categories from database in navigation bar
     *
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    public function indexNavigation(CategoryRepository $categoryRepository)
    {
        return $this->render('category/nav.html.twig', [
            'categories' => $categoryRepository->findSortedByViews(0, 10),
        ]);
    }
}
