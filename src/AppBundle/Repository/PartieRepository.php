<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\Session;
use AppBundle\Entity\Partie;
/**
 * PartieRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PartieRepository extends EntityRepository
{


    function findAvalability($partie,$session){
       $qb =$this->createQueryBuilder('a')
       ->where('a.id=:partie')->setParameter('partie', $partie)->leftJoin('a.sessions', 's');
        return   $qb->andWhere('s.id=:sesion')->setParameter('sesion', $sesion)->getQuery()->getResult();
    }


    function findPartieBy(Session $session){
       $qb =$this->createQueryBuilder('a')->join('a.matiere','m')->where('m.programme=:programme')
       ->setParameter('programme',$session->getPreparation()->getId());
        return   $qb->getQuery()->getResult();
    }   
}
