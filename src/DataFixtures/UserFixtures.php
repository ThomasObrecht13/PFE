<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }
    public function load(ObjectManager $manager)
    {
        // $product = new Product();

        $user = new User();
        $user->setPassword($this->passwordEncoder->encodePassword($user, 'admin'));
        $user->setEmail("admin@admin");
        $user->setRoles((array)'ROLE_ADMIN');
        $manager->persist($user);

        $user = new User();
        $user->setPassword($this->passwordEncoder->encodePassword($user, 'stud'));
        $user->setEmail("stud@stud");
        $user->setRoles((array)'ROLE_USER');
        $manager->persist($user);  $user = new User();

        $user->setPassword($this->passwordEncoder->encodePassword($user, 'prof'));
        $user->setEmail("prof@prof");
        $user->setRoles((array)'ROLE_PROF');
        $manager->persist($user);
        $manager->flush();

    }
}
