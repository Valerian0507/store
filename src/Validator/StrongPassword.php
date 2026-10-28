<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Règle de mot de passe commune (inscription, mot de passe oublié, profil) :
 * au moins 8 caractères, dont une majuscule, un chiffre et un caractère spécial.
 * Les mêmes règles sont affichées en direct par assets/controllers/password_rules_controller.js.
 */
#[\Attribute]
class StrongPassword extends Compound
{
    public const MIN_LENGTH = 8;

    protected function getConstraints(array $options): array
    {
        return [
            new NotBlank(message: 'Veuillez saisir un mot de passe.'),
            new Length(
                min: self::MIN_LENGTH,
                // longueur maximale autorisée par Symfony pour des raisons de sécurité
                max: 4096,
                minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
            ),
            new Regex(pattern: '/\p{Lu}/u', message: 'Le mot de passe doit contenir au moins une lettre majuscule.'),
            new Regex(pattern: '/\d/', message: 'Le mot de passe doit contenir au moins un chiffre.'),
            new Regex(pattern: '/[^\p{L}\p{N}\s]/u', message: 'Le mot de passe doit contenir au moins un caractère spécial (! ? @ # $ % …).'),
        ];
    }
}
