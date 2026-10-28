<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PoolToolsController extends AbstractController
{
    #[Route('/outils/calculateur-volume', name: 'app_tools_volume', methods: ['GET'])]
    public function volume(): Response
    {
        return $this->render('tools/volume.html.twig');
    }

    #[Route('/outils/analyse-eau', name: 'app_tools_water', methods: ['GET'])]
    public function water(): Response
    {
        return $this->render('tools/water.html.twig');
    }
}
