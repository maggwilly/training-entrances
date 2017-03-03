<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

/**
 * PointVenteRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PointVenteRepository extends EntityRepository
{

/**
Nombre de point de vente recencés
 */
	public function nombrePointVente ($region=null){

        $qb = $this->createQueryBuilder('p');

   try {
		 $qb->select('count(p.id) as nombrePointVente');
         return $qb->getQuery()->getSingleScalarResult();  
   } catch (NoResultException $e) {
        return 0;
     }
  }

/**
Nombre de point de vente visités
 */
  public function pointVentes($region=null, $startDate=null, $endDate=null){

        $qb = $this->createQueryBuilder('p');
        if($region!=null){
           $qb->where('p.ville=:ville')
          ->setParameter('ville', $region);
          }
  
         return $qb->getQuery()->getArrayResult();  
   
  }

/**
Nombre de point de vente visités
 */
  public function nombrePointVenteVisite($region=null, $startDate=null, $endDate=null){

        $qb = $this->createQueryBuilder('p')->join('p.visites','v');
        if($region!=null){
           $qb->where('p.ville=:ville')
          ->setParameter('ville', $region);
          }
    if($startDate!=null){
           $qb->andWhere('v.date>=:startDate')
          ->setParameter('startDate', new \DateTime($startDate));
          }
          if($endDate!=null){
           $qb->andWhere('v.date<=:endDate')
          ->setParameter('endDate',new \DateTime($endDate));
          } 

   try {
     $qb->select('count( distinct p.id) as nombrePointVenteVisite');

         return $qb->getQuery()->getSingleScalarResult();  
   } catch (NoResultException $e) {
        return 0;
     }
  }



  /**
  *Repartition des visites effectuees par semaine 
  */
  public function visitesParSemaine ($region=null, $startDate=null, $endDate=null){

       $qb = $this->createQueryBuilder('pv')->leftJoin('pv.visites','v');
        if($region!=null){
           $qb->where('pv.ville=:ville')
          ->setParameter('ville', $region);
          }
          if($startDate!=null){
           $qb->andWhere('v.date>=:startDate')->setParameter('startDate', new \DateTime($startDate));
          }
          if($endDate!=null){
           $qb->andWhere('v.date<=:endDate')->setParameter('endDate',new \DateTime($endDate));
          }
          $qb->select('v.weekText'); 
          $qb->addGroupBy('v.weekText');
          $qb->addSelect('count(v.id) as nombre'); 


          return $qb->getQuery()->getArrayResult();
     
  }

  /**
  *Repartition des visites effectuees par semaine 
  */
  public function eligibles ($region=null, $startDate=null, $endDate=null){
       $em = $this->_em;
       $RAW_QUERY =($region!=null) ?'select *,(case when stock>=30 and (fks>=20 and fmt>=8 and fkm+fkl>=2)  then  (case when map then (case when exc then 20 else 15 end) else (case when exc then 15 else 10 end) end) else  (case when map then (case when exc then 10 else 5 end) else (case when exc then 5 else 0 end) end) end) as note from (select id, nom,map,exc,matricule, sum(stock) as stock ,sum((case when produit=\'FKS\' then stock else 0 end)) as fks,sum((case when produit=\'FKL\' then stock else 0 end)) as fkl,sum((case when produit=\'FMT\' then stock else 0 end)) as fmt,sum((case when produit=\'FKM\' then stock else 0 end)) as fkm   from (select u.id,u.nom,v.map,v.exc,u.matricule, s.produit_id as produit, sum(s.stock) as stock from (select pv.id, pv.nom as nom ,pv.matricule, max(v.date) as date from point_vente pv join visite v  on pv.id=v.point_vente_id and v.date>=:startDate and v.date<=:endDate  and pv.ville=:region  group by  pv.id,pv.nom,pv.matricule ) as u  join  visite v on (u.id=v.point_vente_id and u.date=v.date) join situation s on s.visite_id=v.id join produit p on (s.produit_id=p.id and p.dossier=\'produit\') group by u.id,u.nom,s.produit_id,v.map,v.exc,u.matricule) eligibles group by id, nom,map,exc,matricule) el;
':'select *,(case when stock>=30 and (fks>=20 and fmt>=8 and fkm+fkl>=2)  then  (case when map then (case when exc then 20 else 15 end) else (case when exc then 15 else 10 end) end) else  (case when map then (case when exc then 10 else 5 end) else (case when exc then 5 else 0 end) end) end) as note from (select id, nom,map,exc,matricule, sum(stock) as stock ,sum((case when produit=\'FKS\' then stock else 0 end)) as fks,sum((case when produit=\'FKL\' then stock else 0 end)) as fkl,sum((case when produit=\'FMT\' then stock else 0 end)) as fmt,sum((case when produit=\'FKM\' then stock else 0 end)) as fkm   from (select u.id,u.nom,v.map,v.exc,u.matricule, s.produit_id as produit, sum(s.stock) as stock from (select pv.id, pv.nom as nom ,pv.matricule, max(v.date) as date from point_vente pv join visite v  on pv.id=v.point_vente_id and v.date>=:startDate and v.date<=:endDate   group by  pv.id,pv.nom,pv.matricule ) as u  join  visite v on (u.id=v.point_vente_id and u.date=v.date) join situation s on s.visite_id=v.id join produit p on (s.produit_id=p.id and p.dossier=\'produit\') group by u.id,u.nom,s.produit_id,v.map,v.exc,u.matricule) eligibles group by id, nom,map,exc,matricule) el;
';
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
  /**
  *Repartition des visites effectuees par semaine 
  */
  public function StockParSemaine ($region=null, $startDate=null, $endDate=null){

       $qb = $this->createQueryBuilder('pv')->leftJoin('pv.visites','v');
        if($region!=null){
           $qb->where('pv.ville=:ville')
          ->setParameter('ville', $region);
          }
          if($startDate!=null){
           $qb->andWhere('v.date>=:startDate')->setParameter('startDate', new \DateTime($startDate));
          }
          if($endDate!=null){
           $qb->andWhere('v.date<=:endDate')->setParameter('endDate',new \DateTime($endDate));
          }
          $qb->select('v.weekText'); 
          $qb->addGroupBy('v.weekText');
          $qb->addSelect('count(v.id) as nombre'); 
          return $qb->getQuery()->getArrayResult();  
  }

  


/*
for mobile
*/
  public function pdvs ($region=null){
  $em = $this->_em; //and pv.ville=:region
  $RAW_QUERY ='select u.id,u.nom, u.date as lastvisitedate, nombre ,u.ville,u.secteur_id, type,u.quartier,u.matricule from (select distinct pv.id ,pv.nom,pv.type,pv.quartier,pv.matricule, max(v.date) as date, count(v.id) as nombre,pv.secteur_id,pv.ville from point_vente pv left join visite v  on pv.id=v.point_vente_id  group by  pv.id, pv.nom,pv.type ,pv.ville ,pv.quartier,pv.matricule,pv.secteur_id order by date asc) u where u.ville=:region;';
      $statement = $em->getConnection()->prepare($RAW_QUERY);
   $statement->bindValue('region', $region);

    $statement->execute();
      return  $result = $statement->fetchAll();
  }
}
