<?php

namespace App\Repository;

use App\Entity\Projet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Projet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Projet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Projet[]    findAll()
 * @method Projet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projet::class);
    }

    // /**
    //  * @return Projet[] Returns an array of Projet objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Projet
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findProjetsForProf(String $user)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p.id, p.sujet, p.description, p.date')
            ->join( 'App:NOTE', 'n')
            ->where('p.id = n.Projet')
            ->andWhere('n.User = ?1')
            ->setParameter(1,$user);
        return $qb->getQuery()->getResult();
    }

    public function findProjetsForStud(String $user)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p.id, p.sujet, p.description, p.date')
            ->join( 'App:MEMBRE', 'm')
            ->where('p.id = m.Projet')
            ->andWhere('m.User = ?1')
            ->setParameter(1,$user);
        return $qb->getQuery()->getResult();
    }

    public function findSearch($search)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p');

        if(!empty($search->sujet)){
            $qb = $qb
                ->andWhere('p.sujet LIKE :sujet')
                ->setParameter('sujet',"%{$search->sujet}%");
        }
        if(!empty($search->nomEtudiant)){
            $qb = $qb
                ->join( 'App:MEMBRE', 'm')
                ->andWhere('p.id = m.Projet')
                ->join( 'App:USER', 'u')
                ->andWhere('u.id = m.User')
                ->andWhere('u.email LIKE :nomEtudiant')
                ->setParameter('nomEtudiant',"%{$search->nomEtudiant}%");
        }
        if(!empty($search->nomTuteur)){
            $qb = $qb
                ->join( 'App:NOTE', 'n')
                ->andWhere('p.id = n.Projet')
                ->join( 'App:USER', 'u')
                ->andWhere('u.id = n.User')
                ->andWhere('u.email LIKE :nomTuteur')
                ->setParameter('nomTuteur',"%{$search->nomTuteur}%");
        }
        if(!empty($search->date)){
            $dateDebut = $search->date.'-01-01 00:00:00';
            $dateFin = $search->date.'-12-31 00:00:00';

            $qb = $qb
                ->andWhere('p.date < :dateFin')
                ->andWhere('p.date >= :dateDebut')
                ->setParameter('dateFin',$dateFin)
                ->setParameter('dateDebut',$dateDebut);
        }

        return $qb->getQuery()->getResult();

    }
}
