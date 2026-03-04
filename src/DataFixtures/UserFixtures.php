<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();

        // ✅ Identifiant (adapte selon ton entité)
        if (method_exists($user, 'setEmail')) {
            $user->setEmail('admin@hopic.local');
        }
        if (method_exists($user, 'setUsername')) {
            $user->setUsername('admin');
        }
        if (method_exists($user, 'setUserIdentifier')) {
            // Certains projets ont un setter custom (rare)
            $user->setUserIdentifier('admin@hopic.local');
        }

        // ✅ Rôles
        if (method_exists($user, 'setRoles')) {
            $user->setRoles(['ROLE_ADMIN']);
        }

        // ✅ Activation / statut si tu as ces champs
        if (method_exists($user, 'setStatut')) {
            $user->setStatut(true);
        }
        if (method_exists($user, 'setIsVerified')) {
            $user->setIsVerified(true);
        }
        if (method_exists($user, 'setActive')) {
            $user->setActive(true);
        }

        // ✅ Mot de passe hashé
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'admin123')
        );

        $manager->persist($user);
        $manager->flush();
    }
}