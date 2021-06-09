<?php

namespace App\Repository;

use App\Entity\Membre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Membre|null find($id, $lockMode = null, $lockVersion = null)
 * @method Membre|null findOneBy(array $criteria, array $orderBy = null)
 * @method Membre[]    findAll()
 * @method Membre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MembreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membre::class);
    }

    // /**
    //  * @return Membre[] Returns an array of Membre objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Membre
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findMembreByProjet(String $projet)
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select('m')
            ->where('m.Projet = ?1')
            ->setParameter(1,$projet);
        return $qb->getQuery()->getResult();
    }

    public function findMembreByProjetAndUser(String $projet,String $user)
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select('m')
            ->where('m.Projet = ?1')
            ->andWhere('m.User = ?2')
            ->setParameter(1,$projet)
            ->setParameter(2,$user);
        return $qb->getQuery()->getResult();
    }
}
