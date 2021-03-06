<?php

namespace Pwm\AdminBundle\Controller;

use Pwm\AdminBundle\Entity\Tarifaire;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\Controller\Annotations as Rest; // alias pour toutes les annotations
use FOS\RestBundle\View\View;
/**
 * Tarifaire controller.
 *
 */
class TarifaireController extends Controller
{
    const ZERO_PRICE_ID = 0;
  /**
   * @Security("is_granted('ROLE_PRICER')")
  */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $prices = $em->getRepository('AdminBundle:Tarifaire')->findAll();

        return $this->render('AdminBundle:price:index.html.twig', array(
            'prices' => $prices,
        ));
    }

  /**
   * @Security("is_granted('ROLE_PRICER')")
  */
    public function newAction(Request $request)
    {
        $price = new Tarifaire();
        $form = $this->createForm('Pwm\AdminBundle\Form\PriceType', $price);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($price);
            $em->flush();
           $this->addFlash('success', 'Enrégistrement effectué');
            return $this->redirectToRoute('price_show', array('id' => $price->getId()));
        }elseif($form->isSubmitted())
               $this->addFlash('error', 'Certains champs ne sont pas corrects.');

        return $this->render('AdminBundle:price:new.html.twig', array(
            'price' => $price,
            'form' => $form->createView(),
        ));
    }

  /**
   * @Security("is_granted('ROLE_PRICER')")
  */
    public function showAction(Tarifaire $price)
    {
        $deleteForm = $this->createDeleteForm($price);

        return $this->render('AdminBundle:price:show.html.twig', array(
            'price' => $price,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Lists all Produit entities.
     *@Rest\View(serializerGroups={"price"})
     */
    public function showJsonAction(Tarifaire $price=null)
    {
    if($price==null){
     $em = $this->getDoctrine()->getManager();
     $price = $em->getRepository('AdminBundle:Tarifaire')->find(self::ZERO_PRICE_ID);
        return $price;
    }
      return $price;
    }

  /**
   * @Security("is_granted('ROLE_PRICER')")
  */
    public function editAction(Request $request, Tarifaire $price)
    {
        $deleteForm = $this->createDeleteForm($price);
        $editForm = $this->createForm('Pwm\AdminBundle\Form\PriceType', $price);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();
             $this->addFlash('success', 'Modifications  enrégistrées avec succès.');
            return $this->redirectToRoute('price_edit', array('id' => $price->getId()));
        }elseif($editForm->isSubmitted())
               $this->addFlash('error', 'Certains champs ne sont pas corrects.');

        return $this->render('AdminBundle:price:edit.html.twig', array(
            'price' => $price,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

  /**
   * @Security("is_granted('ROLE_PRICER')")
  */
    public function deleteAction(Request $request, Tarifaire $price)
    {
        $form = $this->createDeleteForm($price);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($price);
            $em->flush();
            $this->addFlash('success', 'Supprimé.');
        }

        return $this->redirectToRoute('price_index');
    }

    /**
     * Creates a form to delete a price entity.
     *
     * @param Tarifaire $price The price entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Tarifaire $price)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('price_delete', array('id' => $price->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
