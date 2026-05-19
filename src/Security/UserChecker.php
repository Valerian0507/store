<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        // Pas de vérifications pre-auth pour ce projet.
        // La vérification de l'e-mail est effectuée dans checkPostAuth()
        // afin de ne pas divulguer l'existence d'un compte non vérifié.
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if(!$user instanceof User){
            return;
        }

        if(!$user->isVerified()){
            throw new CustomUserMessageAccountStatusException('error.email_not_verified');
        }
    }
}



