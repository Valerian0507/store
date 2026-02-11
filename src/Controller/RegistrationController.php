<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private EmailVerifier $emailVerifier,
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
    )
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@store.com', 'Store'))
                    ->to((string) $user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository, TranslatorInterface $translator): Response
    {
        $id = $request->query->get('id');

        // Verify the user id exists and is not null
        if(null === $id){
            return $this->redirectToRoute('app_home');
        }
        $user = $userRepository->find($id);

        // Ensure the user exists in persistence
        if (null === $user) {
            return $this->redirectToRoute('app_home');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Votre adresse e-mail a été vérifiée. Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/verify/resend', name: 'app_resend_verify_email')]
    public function resendVerifyEmail(Request $request, UserRepository $userRepository): Response
    {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir votre adresse e-mail.']),
                    new Email(['message' => 'Adresse e-mail non valide.']),
                    ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $email = $form['email']->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if($user && !$user->isVerified()){
                $this->sendVerificationEmail($user); // on fait appel à la fonction qui envoie le mail de renvoie
                // d'un lien de confirmation uniquement si l'user n'a pas de compte vérifié
            }

            $this->addFlash('success', 'Si cette adresse e-mail est associé à un compte non vérifié, un nouveau lien de confirmation a été envoyé');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/resend_verify_email.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function sendVerificationEmail(User $user): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()] // Permet la validation pour un utilisateur non connecté
        );

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@store.com', 'Store'))
            ->to($user->getEmail())
            ->subject('Merci de confirmer votre adresse email : nouveau lien de vérification')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
                'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
            ]);

        $this->mailer->send($email); // C'est cette ligne qui envoie le mail
    }
}
