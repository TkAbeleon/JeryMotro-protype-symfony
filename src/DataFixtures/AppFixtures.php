<?php
// src/DataFixtures/AppFixtures.php
// Optionnel — charge des utilisateurs de test.
// Utilisation : php bin/console doctrine:fixtures:load
//
// Prérequis : composer require --dev doctrine/doctrine-fixtures-bundle

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $users = [
            ['Admin JeryMotro',     'admin@jeryMotro.mg',           '+261340000001'],
            ['Ranger Menabe',       'ranger.menabe@eaux-forets.mg', '+261340000002'],
            ['Responsable Boeny',   'alertes.boeny@eaux-forets.mg', '+261340000003'],
            ['Équipe Sécheresse',   'secheresse@meteo.mg',          null],
        ];

        foreach ($users as [$name, $email, $phone]) {
            // Éviter les doublons si fixtures relancées
            $existing = $manager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing) {
                continue;
            }

            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setPhone($phone);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
