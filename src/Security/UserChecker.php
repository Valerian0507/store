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
        // No pre-auth checks in this project.
        // The email verification is done in checkPostAuth()
        // so as not to disclose that an unverified account exists.
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



