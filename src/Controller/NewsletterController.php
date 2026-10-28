<?php

namespace App\Controller;

use App\Entity\NewsletterSubscriber;
use App\Entity\User;
use App\Form\NewsletterType;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/newsletter', name: 'app_newsletter_')]
class NewsletterController extends AbstractController
{
    #[Route('/inscription', name: 'subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        NewsletterSubscriberRepository $subscribers,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        $form = $this->createForm(NewsletterType::class);
        $form->handleRequest($request);

        $backToForm = $this->generateUrl('app_home') . '#newsletter';

        if (!$form->isSubmitted()) {
            return $this->redirect($backToForm);
        }

        if (!$form->isValid()) {
            // Premier message d'erreur du formulaire (e-mail vide, invalide, jeton CSRF…)
            $message = 'Inscription impossible.';
            foreach ($form->getErrors(true) as $error) {
                $message = $error->getMessage();
                break;
            }
            $this->addFlash('danger', $message);

            return $this->redirect($backToForm);
        }

        $email = (string) $form->get('email')->getData();

        // Adresse déjà inscrite : même message, pour ne pas révéler qui est abonné
        if ($subscribers->findOneByEmail($email) === null) {
            $this->createSubscriber($email, $em, $mailer, $logger);
        }

        $this->addFlash('success', 'Merci ! Votre inscription à la newsletter est bien enregistrée.');

        return $this->redirect($backToForm);
    }

    // Depuis "Mon compte" : inscription / désinscription de l'adresse du compte connecté
    #[Route('/compte', name: 'account', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function account(
        Request $request,
        NewsletterSubscriberRepository $subscribers,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('newsletter_account', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, veuillez réessayer.');

            return $this->redirectToRoute('app_profile_index');
        }

        /** @var User $user */
        $user = $this->getUser();
        $subscriber = $subscribers->findOneByEmail($user->getEmail());

        if ($request->request->get('action') === 'unsubscribe') {
            if ($subscriber !== null) {
                $em->remove($subscriber);
                $em->flush();
            }
            $this->addFlash('success', 'Vous êtes désinscrit(e) de la newsletter.');
        } else {
            if ($subscriber === null) {
                $this->createSubscriber($user->getEmail(), $em, $mailer, $logger);
            }
            $this->addFlash('success', 'Vous êtes inscrit(e) à la newsletter.');
        }

        return $this->redirectToRoute('app_profile_index');
    }

    #[Route('/desinscription/{token}', name: 'unsubscribe', methods: ['GET', 'POST'], requirements: ['token' => '[a-f0-9]{64}'])]
    public function unsubscribe(
        string $token,
        Request $request,
        NewsletterSubscriberRepository $subscribers,
        EntityManagerInterface $em,
    ): Response {
        $subscriber = $subscribers->findOneBy(['unsubscribeToken' => $token]);

        // GET : page de confirmation (un simple clic ou un aperçu de lien ne désinscrit pas)
        if ($subscriber !== null && $request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('newsletter_unsubscribe', (string) $request->request->get('_token'))) {
                $this->addFlash('danger', 'Jeton de sécurité invalide, veuillez réessayer.');

                return $this->redirectToRoute('app_newsletter_unsubscribe', ['token' => $token]);
            }

            $em->remove($subscriber);
            $em->flush();

            $this->addFlash('success', 'Vous êtes désinscrit(e) de la newsletter.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('newsletter/unsubscribe.html.twig', [
            'subscriber' => $subscriber,
        ], new Response(status: $subscriber === null ? Response::HTTP_NOT_FOUND : Response::HTTP_OK));
    }

    private function createSubscriber(string $email, EntityManagerInterface $em, MailerInterface $mailer, LoggerInterface $logger): void
    {
        $subscriber = new NewsletterSubscriber($email);
        $em->persist($subscriber);
        $em->flush();

        $unsubscribeUrl = $this->generateUrl(
            'app_newsletter_unsubscribe',
            ['token' => $subscriber->getUnsubscribeToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $message = (new TemplatedEmail())
            ->from(new Address('no-reply@store.com', 'AquaSphère'))
            ->to($subscriber->getEmail())
            ->subject('Bienvenue dans la newsletter AquaSphère')
            ->htmlTemplate('email/newsletter_welcome.html.twig')
            ->context(['unsubscribeUrl' => $unsubscribeUrl]);
        $message->getHeaders()->addTextHeader('List-Unsubscribe', '<' . $unsubscribeUrl . '>');

        // L'inscription reste valable même si l'e-mail de bienvenue ne part pas
        try {
            $mailer->send($message);
        } catch (TransportExceptionInterface $e) {
            $logger->error('Newsletter : envoi de l\'e-mail de bienvenue impossible.', ['exception' => $e]);
        }
    }
}
