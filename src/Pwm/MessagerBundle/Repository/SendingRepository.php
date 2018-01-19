<?php

namespace Pwm\MessagerBundle\Repository;
use Pwm\MessagerBundle\Entity\Notification;
use Pwm\MessagerBundle\Entity\Registration;
/**
 * SendingRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SendingRepository extends \Doctrine\ORM\EntityRepository
{

	  /**
  *Nombre de synchro effectue par utilisateur 
  */
  public function findList($registration,$uid,$start){
        //connected and registed
       $qb = $this->createQueryBuilder('a')->join('a.registration','r');
       $qb->where('a.registration=:registration or r.info=:uid')
      ->setParameter('registration',$registration)->setParameter('uid',$uid);
       $qb->andWhere('a.sendDate<=:sendDate') ->setParameter('sendDate',new \DateTime())->orderBy('a.id', 'desc'); 
         $query=$qb->getQuery();
         $query->setFirstResult($start)->setMaxResults(20);
          return $query->getResult();
  }

      /**
  *Nombre de synchro effectue par utilisateur 
  */
  public function findByNotInfo(Notification $notification,Registration $registration){
         $qb = $this->createQueryBuilder('a')->join('a.registration','r')
          ->where('r.info=:info')
          ->setParameter('info',$registration->getInfo())
          ->andWhere('a.notification=:notification')
          ->setParameter('notification',$notification);
          return $qb->getQuery()->getResult();
  }

  	  /**
  *Nombre de synchro effectue par utilisateur 
  */
  public function findCount($registration,$uid){
          //connected and registed
       $qb = $this->createQueryBuilder('a')->join('a.registration','r');
       $qb->where('a.registration=:registration or r.info=:uid')
      ->setParameter('registration',$registration)->setParameter('uid',$uid);
       $qb->andWhere('a.readed is NULL')->select('count(a.id)');
        return $qb->getQuery()->getSingleScalarResult();
  }
}
