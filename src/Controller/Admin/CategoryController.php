<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/categories', name: 'admin_categories_')]
final class CategoryController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAllOrdered();

        return $this->render('admin/categories/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'La nouvelle catégorie a bien été ajoutée.');
            return $this->redirectToRoute('admin_categories_index');
        }

        return $this->render('admin/categories/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, EntityManagerInterface $em, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'La catégorie a bien été modifiée.');
            return $this->redirectToRoute('admin_categories_index');
        }

        return $this->render('admin/categories/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
            ]);
    }


    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, EntityManagerInterface $em, Category $category): Response
    {
        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete_category_'.$category->getId(), $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_categories_index');
        }

        if (!$category->getProducts()->isEmpty()) {
            $this->addFlash('danger', 'Impossible de supprimer une catégorie qui contient des produits.');

            return $this->redirectToRoute('admin_categories_index');
        }

        $em->remove($category);
        $em->flush();

        $this->addFlash('success', 'La catégorie a bien été supprimée.');

        return $this->redirectToRoute('admin_categories_index');
    }
}
