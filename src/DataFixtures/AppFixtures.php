<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Tests\Factory\UserFixtureFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager): void
    {
        UserFixtureFactory::new()->asUser()->afterInstantiate(function(User $object) {
            $password = $this->passwordEncoder->encodePassword($object, 'kitten');
            $object->setPassword($password);
        })->createMany(90);

        UserFixtureFactory::new()->asAdmin()->afterInstantiate(function(User $object) {
            $password = $this->passwordEncoder->encodePassword($object, 'kitten');
            $object->setPassword($password);
        })->createMany(10);
    }
}
