<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdviceController extends AbstractController
{
    private const TEMPLATES = [
        'choisir-sa-piscine' => 'advice/pool.html.twig',
        'creer-son-espace-detente' => 'advice/spa.html.twig',
        'entretenir-son-eau' => 'advice/water.html.twig',
    ];

    #[Route('/conseils/{slug}', name: 'app_advice_show', methods: ['GET'])]
    public function show(string $slug): Response
    {
        if (!isset(self::TEMPLATES[$slug])) {
            throw $this->createNotFoundException('Advice article not found.');
        }

        return $this->render(self::TEMPLATES[$slug]);
    }
}
