<?php

namespace App\Tests\Validator;

use App\Validator\StrongPassword;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * Password rule (registration, forgotten password, profile):
 * 8 characters minimum, an uppercase letter, a digit and a special character.
 */
class StrongPasswordTest extends TestCase
{
    public function testStrongPasswordIsAccepted(): void
    {
        self::assertSame([], $this->violations('Motdepasse1!'));
        self::assertSame([], $this->violations('Éété2026#'), 'Accented uppercase letter accepted');
    }

    #[DataProvider('weakPasswords')]
    public function testWeakPasswordIsRejected(string $password, string $expectedMessage): void
    {
        $messages = $this->violations($password);

        self::assertNotEmpty($messages);
        self::assertStringContainsString($expectedMessage, implode(' | ', $messages));
    }

    /** @return iterable<string, array{string, string}> */
    public static function weakPasswords(): iterable
    {
        yield 'vide' => ['', 'Veuillez saisir un mot de passe'];
        yield 'trop court' => ['Abc1!', 'au moins 8 caractères'];
        yield 'sans majuscule' => ['motdepasse1!', 'une lettre majuscule'];
        yield 'sans chiffre' => ['Motdepasse!', 'au moins un chiffre'];
        yield 'sans caractère spécial' => ['Motdepasse1', 'caractère spécial'];
    }

    /** @return list<string> */
    private function violations(string $password): array
    {
        $violations = Validation::createValidator()->validate($password, new StrongPassword());

        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = (string) $violation->getMessage();
        }

        return $messages;
    }
}
