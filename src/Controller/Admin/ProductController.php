<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\CategoryRepository;
use App\Repository\OrderItemRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;


#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/products', name: 'admin_products_')]
class ProductController extends AbstractController
{
    private const PER_PAGE = 20;

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $categoryId = $request->query->filter('category', null, FILTER_VALIDATE_INT, [
            'flags' => FILTER_NULL_ON_FAILURE,
            'options' => ['min_range' => 1],
        ]);
        $q = trim((string) $request->query->get('q', ''));
        $q = $q !== '' ? mb_substr($q, 0, 80) : null;

        $page = max(1, $request->query->getInt('page', 1));
        $result = $productRepository->findForAdmin($page, self::PER_PAGE, $categoryId, $q);
        $pages = max(1, (int) ceil($result['total'] / self::PER_PAGE));

        // Requested page beyond the last one (e.g. after a deletion): go back to the last page
        if ($page > $pages) {
            $page = $pages;
            $result = $productRepository->findForAdmin($page, self::PER_PAGE, $categoryId, $q);
        }

        return $this->render('admin/products/index.html.twig', [
            'products' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => $pages,
            'categories' => $categoryRepository->findAllOrdered(),
            'category' => $categoryId,
            'q' => $q,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = $fileUploader->upload($imageFile, $product->getReference());
                $product->setImage($newFilename);
            }

            $addStock = (int) $form->get('stock')->getData();
            if ($addStock > 0) {
                $product->setStock($product->getStock() + $addStock);
            }



            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit créé.');
            return $this->redirectToRoute('admin_products_index');
        }

        return $this->render('admin/products/new.html.twig', [
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }


    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $em, FileUploader $fileUploader, OrderItemRepository $orderItems): Response
    {
        $oldImage = $product->getImage();

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();


            if ($imageFile) {
                $newFilename = $fileUploader->upload($imageFile, $product->getReference());
                $product->setImage($newFilename);

            }

            $addStock = (int) $form->get('stock')->getData();
            if ($addStock > 0) {
                $product->setStock($product->getStock() + $addStock);
            }

            $em->flush();

            if ($imageFile && $oldImage && $oldImage !== $product->getImage()) {
                $this->removeImageIfUnused($oldImage, $fileUploader, $orderItems);
            }

            $this->addFlash('success', 'Produit mis à jour.');
            return $this->redirectToRoute('admin_products_index');
        }
        // 422 when the form is invalid: Turbo only renders the errors with a 4xx status
        return $this->render('admin/products/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }


    #[Route('/{id}/delete-image', name: 'delete_image', methods: ['POST'], requirements: ['id' => '\d+'])]
        public function deleteImage(
            Product $product,
            Request $request,
            EntityManagerInterface $em,
            FileUploader $fileUploader,
            OrderItemRepository $orderItems,
        ): Response {
            $token = (string) $request->request->get('_token_delete_image');

            if (!$this->isCsrfTokenValid('delete_product_image_'.$product->getId(), $token)) {
                $this->addFlash('danger', 'Jeton de sécurité invalide, veuillez réessayer.');
                return $this->redirectToRoute('admin_products_edit', ['id' => $product->getId()]);
            }

            $image = $product->getImage();

            if ($image) {
                $product->setImage(null);
                $em->flush();
                $this->removeImageIfUnused($image, $fileUploader, $orderItems);

                $this->addFlash('success', 'Image supprimée.');
            }

            return $this->redirectToRoute('admin_products_edit', ['id' => $product->getId()]);
        }


    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Product $product, Request $request, EntityManagerInterface $em, FileUploader $fileUploader, OrderItemRepository $orderItems): Response
    {
        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete_product_'.$product->getId(), $token)) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, veuillez réessayer.');
            return $this->redirectToRoute('admin_products_index');
        }

        $image = $product->getImage();

        $em->remove($product);
        $em->flush();

        if ($image) {
            $this->removeImageIfUnused($image, $fileUploader, $orderItems);
        }

        $this->addFlash('success', 'Produit supprimé.');
        return $this->redirectToRoute('admin_products_index');
    }

    // Past orders keep the image name (order history, confirmation emails): the file is kept while an order uses it
    private function removeImageIfUnused(string $image, FileUploader $fileUploader, OrderItemRepository $orderItems): void
    {
        if ($orderItems->count(['productImage' => $image]) === 0) {
            $fileUploader->remove($image);
        }
    }
}
