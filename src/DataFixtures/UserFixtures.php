<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class UserFixtures extends Fixture
{
    private $passwordEncoder;
    private $token;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, TokenGeneratorInterface $token)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->token = $token;
    }

    public function load(ObjectManager $manager)
    {
        // $product = new Product();

        $user = new User();
        $user->setPassword($this->passwordEncoder->encodePassword($user, 'admin'))
            ->setEmail("admin@admin")
            ->setRoles((array)'ROLE_ADMIN')
            ->setIsActive(1)
            ->setTokenMail($this->token->generateToken());

        $manager->persist($user);

        $user = new User();
        $user->setPassword($this->passwordEncoder->encodePassword($user, 'stud'))
            ->setEmail("stud@stud")
            ->setRoles((array)'ROLE_USER')
            ->setIsActive(1)
            ->setTokenMail($this->token->generateToken());

        $manager->persist($user);

        $user = new User();
        $user->setPassword($this->passwordEncoder->encodePassword($user, 'prof'))
            ->setEmail("prof@prof")
            ->setRoles((array)'ROLE_PROF')
            ->setIsActive(1)
            ->setTokenMail($this->token->generateToken());

        $manager->persist($user);

        $manager->flush();

    }
}
