<?php

namespace Pwm\MessagerBundle\Controller;

use Pwm\MessagerBundle\Entity\Sending;
use Pwm\MessagerBundle\Entity\Registration;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest; // alias pour toutes les annotations
use FOS\RestBundle\View\View; 
use AppBundle\Event\RegistrationEvent;
/**
 * Sending controller.
 *
 */
class SendingController extends Controller
{
    /**
     * Lists all sending entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $sendings = $em->getRepository('MessagerBundle:Sending')->findAll();
        return $this->render('MessagerBundle:sending:index.html.twig', array(
            'sendings' => $sendings,
        ));
    }

    /**
     * Lists all Produit entities.
     *@Rest\View(serializerGroups={"sending"})
     */
    public function jsonIndexAction(Request $request)
    {
        $start=$request->query->get('start');
        $registrationId=$request->query->get('registrationId');
        $uid=$request->query->get('uid');
        $em = $this->getDoctrine()->getManager();
        $messages = $em->getRepository('MessagerBundle:Sending')->findList($registrationId,$uid,$start);
        $count = $em->getRepository('MessagerBundle:Sending')->findCount($registrationId,$uid);
        return array('messages' => $messages,'count' => $count ) ;
    }


        /**
     * Lists all Produit entities.
     *@Rest\View(serializerGroups={"sending"})
     */
    public function editJsonAction(Request $request, Sending $sending=null){
            $em = $this->getDoctrine()->getManager();
                 $notificationId=$request->query->get('notificationId');
                 $registrationId=$request->query->get('registrationId');
              $sendings = $em->getRepository('MessagerBundle:Sending')->findByNotInfo($notificationId,$registrationId);
              foreach ($sendings as  $sending) {
                 $sending->setReaded(true);
                }
                if(!is_null($sending))
                  $sending->setReaded(true);
            $em->flush();
            return true;
    }


     /**
     * Lists all Produit entities.
     *@Rest\View()
     */
    public function newJsonAction(Request $request, Registration $registration=null)
    {  
          $em = $this->getDoctrine()->getManager();
           if(!is_null($registration))
              return $this->editRegistrationJsonAction($request,$registration);
            $registration = new Registration();
            $form = $this->createForm('Pwm\MessagerBundle\Form\RegistrationType', $registration);
            $form->submit($request->request->all(),false);
          if ($form->isValid()) {
              $registration ->setUserAgent($request->headers->get('User-Agent'));
              $em->persist($registration);
              $em->flush();
              $event= new RegistrationEvent($registration);
              $this->get('event_dispatcher')->dispatch('user.registration', $event);
            return array('success'=>true);
          }
        return $form;
    }
     /**
     * Lists all Produit entities.
     *@Rest\View()
     */
    public function editRegistrationJsonAction(Request $request,Registration $registration)
    {
               $form = $this->createForm('Pwm\MessagerBundle\Form\RegistrationType', $registration);
               $form->submit($request->request->all(),false);
              if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $registration
               ->setLatLoginDate(new \DateTime())
               ->setIsFake(null)
               ->setUserAgent($request->headers->get('User-Agent'));                
                 $em->flush();
                return array('success'=>true);
                  }
            return $form;
    }
    /**
     * Creates a new sending entity.
     *
     */
    public function newAction(Request $request)
    {
        $sending = new Sending();
        $form = $this->createForm('Pwm\MessagerBundle\Form\SendingType', $sending);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $em = $this->getDoctrine()->getManager();
            $em->persist($sending);
            $em->flush();  
            return $this->redirectToRoute('sending_show', array('id' => $sending->getId()));
        }
        return $this->render('MessagerBundle:sending:new.html.twig', array(
            'sending' => $sending,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a sending entity.
     *
     */
    public function showAction(Sending $sending)
    {
        $deleteForm = $this->createDeleteForm($sending);
        return $this->render('MessagerBundle:sending:show.html.twig', array(
            'sending' => $sending,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing sending entity.
     *
     */
    public function editAction(Request $request, Sending $sending)
    {
        $deleteForm = $this->createDeleteForm($sending);
        $editForm = $this->createForm('Pwm\MessagerBundle\Form\SendingType', $sending);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('sending_edit', array('id' => $sending->getId()));
        }

        return $this->render('MessagerBundle:sending:edit.html.twig', array(
            'sending' => $sending,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a sending entity.
     *
     */
    public function deleteAction(Request $request, Sending $sending)
    {
        $form = $this->createDeleteForm($sending);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($sending);
            $em->flush();
        }

        return $this->redirectToRoute('sending_index');
    }

    /**
     * Creates a form to delete a sending entity.
     *
     * @param Sending $sending The sending entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Sending $sending)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('sending_delete', array('id' => $sending->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
