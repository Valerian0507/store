<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Cart\CartManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class CartLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CartManager $cartManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $this->cartManager->mergeSessionCartIntoUserCart($user);
    }
}

