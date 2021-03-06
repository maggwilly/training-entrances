<?php

namespace Pwm\AdminBundle\Repository;
use AppBundle\Entity\SessionConcours;
use AppBundle\Entity\Matiere;
use AppBundle\Entity\Partie;
use Doctrine\ORM\NoResultException;
use Pwm\AdminBundle\Entity\UserAccount;
/**
 * AnalyseRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AnalyseRepository extends \Doctrine\ORM\EntityRepository
{
		 /**
  *Nombre de synchro effectue par utilisateur 
  */
  public function findOneOrNull($studentId, $session, Matiere $matiere=null, Partie $partie=null){
         $qb = $this->createQueryBuilder('a')
         ->where('a.studentId=:studentId')->setParameter('studentId',$studentId)
            ->andWhere('a.session=:session')->setParameter('session',$session);
         if($matiere!=null)
           $qb ->andWhere('a.matiere=:matiere')->setParameter('matiere',$matiere);
         else
           $qb ->andWhere('a.matiere is null');
           if($partie!=null)
           $qb ->andWhere('a.partie=:partie')->setParameter('partie',$partie);
           else
             $qb ->andWhere('a.partie is null');
          return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
  }


    public function findOllFor(UserAccount $studentId, SessionConcours $session, Matiere $matiere=null){
         $qb = $this->createQueryBuilder('a')
         ->where('a.studentId=:studentId')->setParameter('studentId',$studentId)
            ->andWhere('a.session=:session')->setParameter('session',$session)->andWhere('a.matiere is not null');
         if($matiere!=null)
           $qb ->andWhere('a.matiere=:matiere')->setParameter('matiere',$matiere)->andWhere('a.partie is not null');
          return $qb->getQuery()->getResult();
  }

  
  /**
  *Nombre de synchro effectue par utilisateur 
  */
  public function getIndex(SessionConcours $session, Matiere $matiere=null, Partie $partie=null){
         $qb = $this->createQueryBuilder('a')
          ->where('a.session=:session')->setParameter('session',$session);
         if($matiere!=null)
           $qb ->andWhere('a.matiere=:matiere')->setParameter('matiere',$matiere);
         else
           $qb ->andWhere('a.matiere is null');
           if($partie!=null)
           $qb ->andWhere('a.partie=:partie')->setParameter('partie',$partie);
           else
             $qb ->andWhere('a.partie is null');
            $qb->select('a.note')->addSelect('count(a.id) as dememe')
           ->groupBy('a.note')->orderBy('a.note','desc');
          return $qb->getQuery()->getArrayResult();
  }



  public function noteSuperieur10 (SessionConcours $session, Matiere $matiere=null, Partie $partie=null){
          $qb = $this->createQueryBuilder('a')
          ->where('a.session=:session')->setParameter('session',$session);
         if($matiere!=null)
            $qb ->andWhere('a.matiere=:matiere')->setParameter('matiere',$matiere);
         else
           $qb ->andWhere('a.matiere is null');
           if($partie!=null)
           $qb ->andWhere('a.partie=:partie')->setParameter('partie',$partie);
           else
             $qb ->andWhere('a.partie is null');
         try {
          $qb->select('sum(CASE WHEN a.note>=10 THEN 1 ELSE 0 END) as sup10')->addSelect('count(a.id) as nombre');
           return $qb->getQuery()->getArrayResult();  
   } catch (NoResultException $e) {
         return array( array('sup10'=>0,'nombre'=>0));
     }
  }

}
