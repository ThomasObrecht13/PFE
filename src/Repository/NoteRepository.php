<?php

namespace App\Repository;

use App\Entity\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Note|null find($id, $lockMode = null, $lockVersion = null)
 * @method Note|null findOneBy(array $criteria, array $orderBy = null)
 * @method Note[]    findAll()
 * @method Note[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    // /**
    //  * @return Note[] Returns an array of Note objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Note
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findNoteByTuteur(String $projet,String $user)
    {
        $qb = $this->createQueryBuilder('n');
        $qb->select('n.id')
            ->where('n.Projet = ?1')
            ->andWhere('n.User = ?2')
            ->setParameter(1,$projet)
            ->setParameter(2,$user);

        return $qb->getQuery()->getResult();
    }

    public function findNoteByProjet(String $projet)
    {
        $qb = $this->createQueryBuilder('n');
        $qb->select('n.id')
            ->where('n.Projet = ?1')
            ->setParameter(1,$projet);
        return $qb->getQuery()->getResult();
    }

    public function findNoteMoyenneByProjet(String $projet)
    {
        $qb = $this->createQueryBuilder('n');
        $qb->select('AVG(n.soutenance) as soutenance, AVG(n.rapport) as rapport, AVG(n.technique) as technique')
            ->where('n.Projet = ?1')
            ->setParameter(1,$projet);
        return $qb->getQuery()->getResult();
    }

    public function findNoteByProjetAndUser(String $projet, String $user)
    {
        $qb = $this->createQueryBuilder('n');
        $qb->select('n')
            ->where('n.Projet = ?1')
            ->andWhere('n.User = ?2')
            ->setParameter(1,$projet)
            ->setParameter(2,$user);

        return $qb->getQuery()->getResult();
    }
}
