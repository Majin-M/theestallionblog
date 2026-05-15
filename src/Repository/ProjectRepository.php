<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }
    /**
     * Recherche dans name, genre et description (insensible à la casse).
     *
     * @return Project[]
     */
    public function searchByQuery(string $query): array
    {
        $q = '%' . $query . '%';

        return $this->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE LOWER(:q)')
            ->orWhere('LOWER(p.genre) LIKE LOWER(:q)')
            ->orWhere('LOWER(p.description) LIKE LOWER(:q)')
            ->setParameter('q', $q)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return Project[] Returns an array of Project objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Project
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
