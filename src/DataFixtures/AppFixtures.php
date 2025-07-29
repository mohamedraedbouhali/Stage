<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Factory\UserFactory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Only create RH1 user
        \App\Factory\UserFactory::new()->create([
            'email' => 'RH1@gmail.com',
            'roles' => ['ROLE_RH'],
            'password' => password_hash('rh', PASSWORD_BCRYPT),
            'name' => 'RH1 User',
        ]);
        $manager->flush();
    }
}
