<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('product', name: 'app_product')]
    public function product(): Response
    {

        $products = [
            [
              "id" => 1,
              "reference" => "P10-1236",
              "category" => "piscines",
              "title" => "Piscine gonflable 366x91 cm",
              "volume_m3" => 0.047,
              "weight_kg" => 12.27,
              "price_eur" => 130.07,
              "image" => "P10-1236.jpg"
        ],];

        return $this->render('product/index.html.twig', [
            'products' => $products
        ]);
    }
}
