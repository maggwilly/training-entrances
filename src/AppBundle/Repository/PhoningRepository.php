<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * PhoningRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PhoningRepository extends EntityRepository
{
    public function findList($region=null, $startDate=null, $endDate=null){
         $qb = $this->createQueryBuilder('p')->join('p.client','c');
        if($region!=null){
           $qb->where('c.ville=:ville')
          ->setParameter('ville', $region);
          }
      if($startDate!=null){
           $qb->andWhere('p.date>=:startDate')
          ->setParameter('startDate', new \DateTime($startDate));
          }
          if($endDate!=null){
           $qb->andWhere('p.date<=:endDate')
          ->setParameter('endDate',new \DateTime($endDate));
          }          
          return $qb->getQuery()->getArrayResult();
  }   
  

     public function findGroupByIssu($region=null, $startDate=null, $endDate=null){
    $em = $this->_em;
    $RAW_QUERY =($region!=null) ?'select g.issu, count(*) as nombre from (select *, p.issu from (select p.client_id,p.user_id, max(p.date) as date, max(p.heure) as heure, count(p.id) as nombre from phoning p where p.date>=:startDate and p.date<=:endDate group by p.client_id,p.user_id)lp join phoning p on (lp.client_id=p.client_id and lp.date=p.date and lp.heure=p.heure)  join client c on (lp.client_id=c.id and c.ville=:region) join user_account u on (lp.user_id=u.id)) g group by g.issu ;':'select p.issu, count(*) as nombre from (select *,p.issu from (select p.client_id,p.user_id, max(p.date) as date, max(p.heure) as heure, count(p.id) as nombre from phoning p where p.date>=:startDate and p.date<=:endDate group by p.client_id,p.user_id)lp join phoning p on (lp.client_id=p.client_id and lp.date=p.date and lp.heure=p.heure)  join client c on (lp.client_id=c.id) join user_account u on (lp.user_id=u.id)) g group by g.issu ;';
    $statement = $em->getConnection()->prepare($RAW_QUERY);
    if($region!=null){
    $statement->bindValue('region', $region);
          }
    $startDate=new \DateTime($startDate);
    $endDate=new \DateTime($endDate);
    $statement->bindValue('startDate', $startDate->format('Y-m-d'));
    $statement->bindValue('endDate',  $endDate->format('Y-m-d'));
    $statement->execute();
      return  $result = $statement->fetchAll();
  }   
  

  public function findGroupByClient ($region=null, $startDate=null, $endDate=null){
    $em = $this->_em;
    $RAW_QUERY =($region!=null) ?'select *, c.nom as client, c.telephone,p.issu,u.nom as commercial from (select p.client_id,p.user_id, max(p.date) as date, max(p.heure) as heure, count(p.id) as nombre from phoning p where p.date>=:startDate and p.date<=:endDate group by p.client_id,p.user_id)lp join phoning p on (lp.client_id=p.client_id and lp.date=p.date and lp.heure=p.heure)  join client c on (lp.client_id=c.id and c.ville=:region) join user_account u on (lp.user_id=u.id);':'select *, c.nom as client, c.telephone,p.issu,u.nom as commercial from (select p.client_id,p.user_id, max(p.date) as date, max(p.heure) as heure, count(p.id) as nombre from phoning p where p.date>=:startDate and p.date<=:endDate group by p.client_id,p.user_id)lp join phoning p on (lp.client_id=p.client_id and lp.date=p.date and lp.heure=p.heure)  join client c on (lp.client_id=c.id) join user_account u on (lp.user_id=u.id);';
    $statement = $em->getConnection()->prepare($RAW_QUERY);
    if($region!=null){
    $statement->bindValue('region', $region);
          }
    $startDate=new \DateTime($startDate);
    $endDate=new \DateTime($endDate);
    $statement->bindValue('startDate', $startDate->format('Y-m-d'));
    $statement->bindValue('endDate',  $endDate->format('Y-m-d'));
    $statement->execute();
      return  $result = $statement->fetchAll();
  } 
      
}
