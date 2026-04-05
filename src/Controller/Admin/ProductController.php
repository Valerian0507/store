<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
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
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('admin/products/index.html.twig', [
            'products' => $productRepository->findBy([], ['id' => 'DESC']),
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
//            Код c удаления и заменой картинки в папке
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = $fileUploader->upload($imageFile, $product->getReference());
                $product->setImage($newFilename);
            }



            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit créé.');
            return $this->redirectToRoute('admin_products_index');
        }

        return $this->render('admin/products/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        $oldImage = $product->getImage(); // запоминаем до обработки формы

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();
            // $removeImage = $form->has('removeImage') ? (bool) $form->get('removeImage')->getData() : false;

            if ($imageFile) {
                $newFilename = $fileUploader->upload($imageFile, $product->getReference());
                $product->setImage($newFilename);

                // удаляем старый файл только если реально загрузили новый
                if ($oldImage) {
                    $oldPath = $fileUploader->getTargetDirectory() . DIRECTORY_SEPARATOR . $oldImage;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            }

                $em->flush();

                $this->addFlash('success', 'Produit mis à jour.');
                return $this->redirectToRoute('admin_products_index');
        }
        return $this->render('admin/products/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id}/delete-image', name: 'delete_image', methods: ['POST'], requirements: ['id' => '\d+'])]
        public function deleteImage(
            Product $product,
            Request $request,
            EntityManagerInterface $em,
            FileUploader $fileUploader
        ): Response {
            $token = (string) $request->request->get('_token_delete_image');

            if (!$this->isCsrfTokenValid('delete_product_image_'.$product->getId(), $token)) {
                $this->addFlash('danger', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_products_edit', ['id' => $product->getId()]);
            }

            $image = $product->getImage();

            if ($image) {
                $path = $fileUploader->getTargetDirectory() . DIRECTORY_SEPARATOR . $image;

                if (is_file($path)) {
                    unlink($path);
                }

                $product->setImage(null);
                $em->flush();

                $this->addFlash('success', 'Image supprimée.');
            }

            return $this->redirectToRoute('admin_products_edit', ['id' => $product->getId()]);
        }


    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Product $product, Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete_product_'.$product->getId(), $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_products_index');
        }

        // удаляем файл только после валидного CSRF
        $image = $product->getImage();
        if ($image) {
            $path = $fileUploader->getTargetDirectory() . DIRECTORY_SEPARATOR . $image;
            // if (is_file($path)) {
            //     @unlink($path);
            // }
            if (is_file($path) && is_writable($path)) {
               unlink($path);
            }
        }

        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'Produit supprimé');
        return $this->redirectToRoute('admin_products_index');
    }
}
