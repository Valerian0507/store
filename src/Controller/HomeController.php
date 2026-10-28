<?php

namespace App\Controller;

use App\Form\NewsletterType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $products): Response
    {
        // Formulaire affiché ici, traité par NewsletterController
        $newsletterForm = $this->createForm(NewsletterType::class, null, [
            'action' => $this->generateUrl('app_newsletter_subscribe'),
        ]);

        // "Notre sélection" : un produit par catégorie
        return $this->render('home/index.html.twig', [
            'featured' => $products->findHomeSelection(4),
            'newsletterForm' => $newsletterForm,
        ]);
    }


    #[Route('mentions_legales', name:'app_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('home/mentions_legales.html.twig');
    }

    #[Route('cgv', name:'app_cgv')]
    public function cgv(): Response
    {
        return $this->render('home/cgv.html.twig');
    }

    #[Route('politique_confidentialite', name:'app_politique_confidentialite')]
    public function politiqueConfidentialite(): Response
    {
        return $this->render('home/politique_confidentialite.html.twig');
    }


}
