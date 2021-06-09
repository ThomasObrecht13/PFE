<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function getSameEmail(String $email)
    {
        $qb=$this->createQueryBuilder('u');
        $qb->select('u')
            ->where('u.email LIKE ?1')
            ->setParameter(1,$email);

        return $qb->getQuery()->getResult();
    }

    public function getSameEmailOnChange(String $email, String $id)
    {
        $qb=$this->createQueryBuilder('u');
        $qb->select('u')
            ->where('u.email LIKE ?1')
            ->andWhere('u.id NOT LIKE ?2')
            ->setParameter(1,$email)
            ->setParameter(2,$id);


        return $qb->getQuery()->getResult();
    }
    public function findByUser(String $user)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.isActive')
            ->where('u.email = ?1')
            ->setParameter(1,$user);
        return $qb->getQuery()->getResult();
    }
    public function findToken(String $token)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->where('u.tokenMail = ?1')
            ->setParameter(1, $token);
        return $qb->getQuery()->getResult();
    }
    public function findByMail(String $mail)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.id')
            ->where('u.email = ?1')
            ->setParameter(1,$mail);
        return $qb->getQuery()->getResult();
    }

    public function findProfByProjet(String $projet)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->join( 'App:NOTE', 'n')
            ->where('u.id = n.User')
            ->andWhere('n.Projet = ?1')
            ->setParameter(1,$projet);
        return $qb->getQuery()->getResult();
    }

    public function findStudByProjet(String $projet)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->join( 'App:MEMBRE', 'm')
            ->where('u.id = m.User')
            ->andWhere('m.Projet = ?1')
            ->setParameter(1,$projet);
        return $qb->getQuery()->getResult();
    }
    public function findStudByNotProjet($role)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->where('u.roles LIKE ?1')
            ->setParameter(1,'ROLE_%"' . $role . '"%');
        return $qb->getQuery()->getResult();
    }

}
