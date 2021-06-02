<?php

namespace App\DataFixtures;

use App\Entity\Membre;
use App\Entity\Note;
use App\Entity\Projet;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Validator\Constraints\Date;

class AppFixtures extends Fixture
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
        $this->loadUser($manager);
        $this->loadProjet($manager);
        $this->loadNote($manager);
        $this->loadMembre($manager);

    }

    public function loadUser(ObjectManager $manager)
    {
        $users = [
            ['id' => 1, 'email' => 'admin@admin','pwd'=>'admin','role'=>(array)'ROLE_ADMIN'],
            ['id' => 2, 'email' => 'stud@stud','pwd'=>'stud','role'=>(array)'ROLE_USER'],
            ['id' => 3, 'email' => 'michou@michou','pwd'=>'stud','role'=>(array)'ROLE_USER'],
            ['id' => 4, 'email' => 'inox@inox','pwd'=>'stud','role'=>(array)'ROLE_USER'],
            ['id' => 5, 'email' => 'luc@luc','pwd'=>'stud','role'=>(array)'ROLE_USER'],
            ['id' => 6, 'email' => 'prof@prof','pwd'=>'prof','role'=>(array)'ROLE_PROF'],
            ['id' => 7, 'email' => 'dupont@dupont','pwd'=>'prof','role'=>(array)'ROLE_PROF'],
            ['id' => 8, 'email' => 'dupuit@dupuit','pwd'=>'prof','role'=>(array)'ROLE_PROF'],
            ['id' => 9, 'email' => 'bernard@bernard','pwd'=>'prof','role'=>(array)'ROLE_PROF'],
        ];
        foreach ($users as $user) {
            $new_user = new User();
            $new_user->setPassword($this->passwordEncoder->encodePassword($new_user, $user['pwd']))
                ->setEmail($user['email'])
                ->setRoles($user['role'])
                ->setIsActive(1)
                ->setTokenMail($this->token->generateToken());

            $manager->persist($new_user);
            $manager->flush();

        }
    }
    public function loadProjet(ObjectManager $manager)
    {
        $projets = [
            ['id' => 1, 'sujet' => 'Appli web', 'date' => '2021-06-01'],
            ['id' => 2, 'sujet' => 'Gestion BDD', 'date' => '2021-07-03'],
            ['id' => 3, 'sujet' => 'LEPROJETLA', 'date' => '2021-07-03'],

        ];

        foreach ($projets as $projet) {
            $new_projet = new Projet();

            $new_projet->setSujet($projet['sujet'])
                ->setDate(DateTime::createFromFormat('Y-m-d',$projet['date']));

            $manager->persist($new_projet);
            $manager->flush();
        }
    }

    public function loadNote(ObjectManager $manager)
    {
        $notes = [
            ['id' => 1, 'rapport' => '2', 'soutenance' => '12', 'technique' => '7', 'projet' => 'Appli web', 'user' => 'dupont@dupont'],
            ['id' => 2, 'rapport' => '5', 'soutenance' => '14', 'technique' => '9', 'projet' => 'Gestion BDD', 'user' => 'dupont@dupont'],
            ['id' => 3, 'rapport' => '8', 'soutenance' => '15', 'technique' => '11', 'projet' => 'Appli web', 'user' => 'bernard@bernard'],
            ['id' => 4, 'rapport' => '8', 'soutenance' => '15', 'technique' => '11', 'projet' => 'Gestion BDD', 'user' => 'dupuit@dupuit'],

        ];
        foreach ($notes as $note) {
            $new_note = new Note();
            $projet = $manager->getRepository(Projet::class)->findOneBy(["sujet"  =>  $note['projet']]);
            $user = $manager->getRepository(User::class)->findOneBy(["email"  =>  $note['user']]);

            $new_note->setRapport($note['rapport'])
                ->setSoutenance($note['soutenance'])
                ->setTechnique($note['technique'])
                ->setProjet($projet)
                ->setUser($user);

            $manager->persist($new_note);
            $manager->flush();

        }
    }
    public function loadMembre(ObjectManager $manager)
    {
        $membres = [
            ['id' => 1, 'chefProjet' => false, 'projet' => 'Appli web', 'user' => 'michou@michou'],
            ['id' => 2, 'chefProjet' => false, 'projet' => 'Appli web', 'user' => 'inox@inox'],


        ];
        foreach ($membres as $membre) {
            $new_membre = new Membre();
            $projet = $manager->getRepository(Projet::class)->findOneBy(["sujet"  =>  $membre['projet']]);
            $user = $manager->getRepository(User::class)->findOneBy(["email"  =>  $membre['user']]);

            $new_membre->setChefProjet($membre['chefProjet'])
                ->setProjet($projet)
                ->setUser($user);

            $manager->persist($new_membre);
            $manager->flush();
        }
    }
}
