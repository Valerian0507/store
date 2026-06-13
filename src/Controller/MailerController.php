<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

class MailerController extends AbstractController
{
    #[Route('/send-mail-html', name: 'app_send_mail_html')]
    public function sendMailHtml(MailerInterface $mailer): Response
    {
        $username = 'John Doe';

        $email = (new TemplatedEmail())
            ->from('admin@store.com')
            ->to('client@store.com')
            ->subject('Confirmation de création de votre compte Store !')
            ->htmlTemplate('email/new_mail.html.twig')
            ->context([
                'username' => $username
            ]);

        $mailer->send($email);

        return new Response('Email HTML envoyé avec succès !');
    }


    #[Route('/send-order-confirmation', name: 'app_send_order_confirmation')]
    public function sendOrderConfirmation(MailerInterface $mailer): Response
    {
        $username = 'John Doe';
// ПОДКОРРЕКТИРОВАТЬ ЭТОТ МОМЕНТ ЧТО БЫ ДАННЫЕ БРАЛИСЬ ИЗ КОРЗИНЫ И САМИ ВЫДАВАЛИ ЧТО ЗАКАЗЫВАЮТ НУЖНО СВЯЗАТЬ С КОРЗИНОЙ
        $order = [
            'number' => 'CMD-2026-045',
            'date' => new \DateTimeImmutable(),
            'total' => 899.99,
            'items' => [
                ['name' => 'iPhone 15', 'quantity' => 1, 'price' => 799.99],
                ['name' => 'Coque silicone', 'quantity' => 1, 'price' => 100.00],
            ],
        ];

        $email = (new TemplatedEmail())
            ->from('commande@store.com')
            ->to('client@store.com')
            ->subject('Confirmation de votre commande Store !')
            ->htmlTemplate('email/order_confirmation.html.twig')
            ->context([
                'username' => $username,
                'order' => $order,
            ]);

        $mailer->send($email);

        return new Response('Mail de confirmation de commande envoyé avec succès !');
    }

}
