<?php

namespace App\Controller;

use App\Entity\CarePlan;
use App\Entity\User;
use App\Repository\CarePlanRepository;
use App\Repository\ProductRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CarePlanController extends AbstractController
{
    #[Route('/profile/abonnements', name: 'app_care_plans', methods: ['GET'])]
    public function index(CarePlanRepository $plans): Response
    {
        return $this->render('profile/care_plans.html.twig', ['plans' => $plans->forUser($this->currentUser())]);
    }

    #[Route('/products/{id}/planning', name: 'app_care_plan_create', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function create(int $id, Request $request, ProductRepository $products, CarePlanRepository $plans, EntityManagerInterface $em): Response
    {
        $this->checkToken($request, 'care_plan_create_' . $id);
        $product = $products->find($id);
        if ($product === null || !$product->isReplenishable()) throw $this->createNotFoundException('This product has no replenishment plan.');
        if ($product->getStock() < 1) {
            $this->addFlash('warning', 'Ce produit est actuellement indisponible.');
            return $this->redirectToRoute('app_products_show', ['id' => $id], Response::HTTP_SEE_OTHER);
        }
        try {
            [$quantity, $interval] = $this->parameters($request);
            $user = $this->currentUser();
            $plan = $plans->findOneBy(['user' => $user, 'product' => $product]);
            if ($plan !== null) $plan->restart($quantity, $interval);
            else {
                $plan = new CarePlan($user, $product, $quantity, $interval);
                $em->persist($plan);
            }
            $em->flush();
            $this->addFlash('success', 'Planning de démonstration enregistré. Aucun paiement ni envoi ne sera déclenché.');
        } catch (\InvalidArgumentException $error) {
            $this->addFlash('warning', $error->getMessage());
            return $this->redirectToRoute('app_products_show', ['id' => $id], Response::HTTP_SEE_OTHER);
        } catch (UniqueConstraintViolationException) {
            $this->addFlash('info', 'Ce planning existe déjà dans votre compte.');
        }
        return $this->redirectToRoute('app_care_plans', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/profile/abonnements/{id}/{operation}', name: 'app_care_plan_update', requirements: ['id' => '\d+', 'operation' => 'edit|pause|resume|cancel'], methods: ['POST'])]
    public function update(int $id, string $operation, Request $request, CarePlanRepository $plans, EntityManagerInterface $em): Response
    {
        $plan = $plans->findOneBy(['id' => $id, 'user' => $this->currentUser()]);
        if ($plan === null) throw $this->createNotFoundException('Planning introuvable.');
        $this->checkToken($request, 'care_plan_' . $operation . '_' . $id);
        try {
            if ($operation === 'cancel') $plan->cancel();
            elseif ($operation === 'pause') $plan->pause();
            else {
                if (!$plan->getProduct()->isReplenishable()) throw new \DomainException('Ce produit ne propose plus de planning. Vous pouvez l’annuler.');
                if ($operation === 'resume') $plan->resume();
                else {
                    [$quantity, $interval] = $this->parameters($request);
                    $plan->configure($quantity, $interval);
                }
            }
            $em->flush();
            $this->addFlash('success', 'Planning mis à jour.');
        } catch (\InvalidArgumentException|\DomainException $error) { $this->addFlash('warning', $error->getMessage()); }
        return $this->redirectToRoute('app_care_plans', [], Response::HTTP_SEE_OTHER);
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) throw $this->createAccessDeniedException();
        return $user;
    }

    private function checkToken(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, $request->request->getString('_token'))) throw $this->createAccessDeniedException('Jeton CSRF invalide.');
    }

    /** @return array{int, int} */
    private function parameters(Request $request): array
    {
        $quantity = $request->request->filter('quantity', null, FILTER_VALIDATE_INT);
        $interval = $request->request->filter('interval', null, FILTER_VALIDATE_INT);
        if (!is_int($quantity) || !is_int($interval) || $quantity < 1 || $quantity > 20 || !in_array($interval, CarePlan::INTERVALS, true)) {
            throw new \InvalidArgumentException('Choisissez 1 à 20 unités et un intervalle de 4, 6, 8 ou 12 semaines.');
        }
        return [$quantity, $interval];
    }
}
