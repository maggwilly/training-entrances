<?php

namespace Pwm\MessagerBundle\Controller;

use Pwm\MessagerBundle\Entity\Sending;
use Pwm\MessagerBundle\Entity\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;

// alias pour toutes les annotations
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Event\ResultEvent;
use Pwm\AdminBundle\Entity\Groupe;
use Pwm\AdminBundle\Entity\UserAccount;
use AppBundle\Event\NotificationEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * Notification controller.
 *
 */
class NotificationController extends Controller
{
    /**
     * Lists all notification entities.
     *
     */
    private $registrationIds = array();
    const HEADERS = array(
        "Authorization: key=AAAAJiQu4xo:APA91bH63R7-CeJ7jEgGtb2TNVkCx0TDWAYbu32mO1_4baLtrrFidNrbNy98Qngb6G67efbuJ8BpInpJiCeoTp-p5mt2706P2hXbXqrTXOWlaJFTDHza2QVWSlwsbF27eBhD2PZVJKuu",
        "content-type: application/json"
    );
    const FCM_URL = "https://fcm.googleapis.com/fcm/send";

    /**
     * @Security("is_granted('ROLE_MESSAGER')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $notifications = $em->getRepository('MessagerBundle:Notification')->findList();
        return $this->render('MessagerBundle:notification:index.html.twig', array(
            'notifications' => $notifications,
        ));
    }


    public function renderTemplate(\Pwm\AdminBundle\Entity\Commande $commande = null)
    {
        return $this->renderView(
            'MessagerBundle:notification:confirmation.html.twig',
            array(
                'commande' => $commande
            )
        );
    }

    /**
     * Creates a new notification entity.
     *
     */
    public function newAction(Request $request, Groupe $groupe = null)
    {
        $notification = new Notification();
        $notification->setGroupe($groupe);
        $em = $this->getDoctrine()->getManager();

        $form = $this->createConditionalForm($notification);//$this->createForm('Pwm\MessagerBundle\Form\NotificationType', $notification);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $notification->setUser($this->getUser()) ->setIncludeMail(true);
            $em->persist($notification);
            $em->flush();
            if ($notification->getSendNow()) {
                return $this->redirectToRoute('notification_send', array('id' => $notification->getId()));
            }
            $this->addFlash('success', 'Enrégistrement effectué');
            return $this->redirectToRoute('notification_show', array('id' => $notification->getId()));
        } elseif ($form->isSubmitted())
            $this->addFlash('error', 'Certains champs ne sont pas corrects.');
        return $this->render('MessagerBundle:notification:new.html.twig', array(
            'notification' => $notification,
            'form' => $form->createView() ));
    }

    /**
     * Finds and displays a article entity.
     *
     */
    public function getRateAction(Notification $notification)
    {
        $url = "https://us-central1-trainings-fa73e.cloudfunctions.net/getRate?article=" . $notification->getId();
        $renderTemplate = $this->get('fmc_manager')->sendOrGetData($url, null, 'GET');
        return new Response($renderTemplate);
    }

    /**
     * Finds and displays a article entity.
     *
     */
    public function getReadingAction(Notification $notification)
    {
        $em = $this->getDoctrine()->getManager();
        $readed = $em->getRepository('MessagerBundle:Sending')->findReading($notification);
        $envois = $notification->getSendings();
        $reading = count($envois) > 0 ? $readed * 100 / count($envois) : '--';
        return new Response("" . $reading);
    }

    public function getDestNumberAction(Notification $notification)
    {
        $em = $this->getDoctrine()->getManager();
        $nuberDesc = count($em->getRepository('MessagerBundle:Registration')->findAll());
        $groupe = $notification->getGroupe();
        if (!is_null($groupe)) {
            $numberDestTmp = 0;
            foreach ($groupe->getSession()->getInfos() as $info) {
                foreach ($info->getRegistrations() as $registration) {
                    if (!$registration->getIsFake()) {
                        $numberDestTmp++;
                    }
                }
            }
            $nuberDesc = $numberDestTmp;
        }
        return new Response("" . $nuberDesc);
    }

    /**
     * Lists all Produit entities.
     * @Rest\View(serializerGroups={"sending"})
     */
    public function showJsonAction(Request $request, Notification $notification)
    {
        $em = $this->getDoctrine()->getManager();
        $registrationId = $request->query->get('registrationId');
        $sendings = $em->getRepository('MessagerBundle:Sending')
            ->findByNotInfo($notification->getId(), $registrationId);
        foreach ($sendings as $sending) {
            $sending->setReaded(true);
        }
        $em->flush();
        return $notification;
    }

    /**
     * Finds and displays a notification entity.
     *
     */
    public function showAction(Notification $notification)
    {
        $em = $this->getDoctrine()->getManager();
        $deleteForm = $this->createDeleteForm($notification);
        $sendForm = $this->createSendForm($notification);
        return $this->render('MessagerBundle:notification:show.html.twig', array(
            'notification' => $notification,
            'send_form' => $sendForm->createView(),
            'delete_form' => $deleteForm->createView()
        ));
    }

    /**
     * @Security("is_granted('ROLE_MESSAGER')")
     */
    public function resentAction(Notification $notification = null)
    {
        $em = $this->getDoctrine()->getManager();
        $sendings = $em->getRepository('MessagerBundle:Sending')->findNotRead($notification);
        $registrationIds = array_unique(array_column($sendings, 'registrationId'));
        $registrations = $em->getRepository('MessagerBundle:Registration')->findByRegistrationIds($registrationIds);
        if (!is_null($notification)) {
            $data = array('page' => 'notification', 'notification_id' => $notification->getId());
            $notification->setIncludeMail(false);
            $em->flush();
            $event = new NotificationEvent($registrations, $notification, $data);
            $this->get('event_dispatcher')->dispatch('notification.shedule.to.send', $event);
            $this->addFlash('success', 'Rappel envoyé à . ' . count($registrations) . ' contacts');
            return $this->redirectToRoute('notification_show', array('id' => $notification->getId()));
        }

        $notification = new Notification();
        $notification->setTitre('Messages non lus')->setSousTitre("Vous avez de nombreux messages non consultés. Si vous aspirez à un concours, vous devez être attentif à toutes les annonces. ");
        $notification->setIncludeMail(false);
        $data = array('page' => 'rappel');
        $event = new NotificationEvent($registrations, $notification, $data);
        $this->get('event_dispatcher')->dispatch('notification.shedule.to.send', $event);
        $this->addFlash('success', 'Rappel envoyé à . ' . count($registrations) . ' contacts');
        return $this->redirectToRoute('notification_index');
    }


    public function resentFroCronJobAction(Notification $notification = null)
    {
        $em = $this->getDoctrine()->getManager();
        $sendings = $em->getRepository('MessagerBundle:Sending')->findNotRead($notification);
        $registrationIds = array_unique(array_column($sendings, 'registrationId'));
        $registrations = $em->getRepository('MessagerBundle:Registration')->findByRegistrationIds($registrationIds);
        $notification = new Notification();
        $notification
            ->setTitre('Messages non lus')
            ->setSousTitre("Vous avez de nombreux messages non consultés. Si vous aspirez à un concours, vous devez être attentif à toutes les annonces. ");
        $notification->setIncludeMail(false)->setText($notification->getSousTitre())->setType("private");
        $data = array('page' => 'notification', 'notification_id' => $notification->getId());
        $event = new NotificationEvent($registrations, $notification, $data);
        $this->get('event_dispatcher')->dispatch('notification.shedule.to.send', $event);
        $this->addFlash('success', 'Rappel envoyé à . ' . count($registrations) . ' contacts');
        return new Response('Ok');
    }


    /**
     * @Security("is_granted('ROLE_MESSAGER')")
     */
    public function sendAction(Notification $notification)
    {
        $em = $this->getDoctrine()->getManager();
        $groupe=$notification->getGroupe();
        $registrations=array();
        $topic="centor-public";
         if(is_null($groupe)){
            $registrations = $em->getRepository('MessagerBundle:Registration')->findAll();
         }elseif(!is_null($groupe->getSession())){
             $topic="centor-group-".$groupe->getSession()->getId();
           $infos= $groupe->getSession()->getInfos();
             foreach ($infos as $info) {
                 foreach ( $info->getRegistrations() as $registration) {
                     if(!$registration->getIsFake())
                         $registrations[]=$registration;
             }
           }
         }else{
             $infos= $groupe->getInfos();
             foreach ($infos as $info) {
                 foreach ( $info->getRegistrations() as $registration) {
                     if(!$registration->getIsFake())
                         $registrations[]=$registration;
                 }
             }
         }
        $data = array('page' => 'notification', 'id' => $notification->getId());
        $event = new NotificationEvent($registrations, $notification, $data);
        $event->setTopic($topic);
        $em->flush();
        $this->get('event_dispatcher')->dispatch('notification.shedule.to.send', $event);
        $this->addFlash('success', 'Envoyé à . ' . count($registrations) . ' contacts');
        return $this->redirectToRoute('notification_show', array('id' => $notification->getId()));
    }




    public function findRegistrations($destinations)
    {
        $registrations = array();
        foreach ($destinations as $info) {
            foreach ($info->getRegistrations() as $registration) {
                if (is_null($registration->getIsFake()))
                    $registrations[] = $registration;
            }
        }
        return $registrations;

    }


    /**
     * Displays a form to edit an existing notification entity.
     *
     */
    public function editAction(Request $request, Notification $notification)
    {
        $deleteForm = $this->createDeleteForm($notification);
        $sendForm = $this->createSendForm($notification);
        $editForm = $this->createConditionalForm($notification);//createForm('Pwm\MessagerBundle\Form\NotificationType', $notification);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Modifications  enrégistrées avec succès.');
            return $this->redirectToRoute('notification_edit', array('id' => $notification->getId()));
        } elseif ($editForm->isSubmitted())
            $this->addFlash('error', 'Certains champs ne sont pas corrects.');
        return $this->render('MessagerBundle:notification:edit.html.twig', array(
            'notification' => $notification,
            'edit_form' => $editForm->createView(),
            'send_form' => $sendForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a notification entity.
     *
     */
    public function deleteAction(Request $request, Notification $notification)
    {
        $form = $this->createDeleteForm($notification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($notification);
            $em->flush();
            $this->addFlash('success', 'Supprimé.');
        }

        return $this->redirectToRoute('notification_index');
    }

    /**
     * Creates a form to delete a notification entity.
     *
     * @param Notification $notification The notification entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Notification $notification)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('notification_delete', array('id' => $notification->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

    private function createSendForm(Notification $notification)
    {
        return $this->createFormBuilder($notification)
            ->add('groupe', EntityType::class,
                array('class' => 'AdminBundle:Groupe',
                    'choice_label' => 'getNom',
                    'placeholder' => 'Tout le monde',
                    'empty_data' => null,
                    'required' => false,
                    'label' => 'Destinataires',
                    'attr' => array('data-rel' => 'chosen'))
            )
            ->setAction($this->generateUrl('notification_send', array('id' => $notification->getId())))
            ->setMethod('POST')
            ->getForm();

    }

    private function createConditionalForm(Notification $notification)
    {
        $formBuilder = $this->createFormBuilder($notification)
            ->add('titre', 'text', array('label' => "Titre"))
            ->add('sousTitre', 'textarea', array('label' => "Texte simple de moin de  132 caractères à afficher sur l'ecran veroullé"))
            ->add('text', 'textarea', array('label' => 'Corps du message en texte riche contenant imqges et média', 'attr' => array('class' => 'ckeditor')))
            ->add('format', ChoiceType::class, array(
                'choices' => array(
                    'ios-mail' => 'Message', 'notifications' => 'Notifications', 'paper' => 'Annonce', 'alarm' => 'Alerte temps', 'ios-bulb' => 'Astuce'),
            ));
        if ($this->get('security.authorization_checker')->isGranted('ROLE_MESSAGER'))
            $formBuilder->add('sendNow', 'checkbox', array('label' => 'Envoyer maintenant', 'required' => false))
                ->add('groupe', EntityType::class,
                    array('class' => 'AdminBundle:Groupe',
                        'choice_label' => 'getNom',
                        'placeholder' => 'Tout le monde',
                        'empty_data' => null,
                        'required' => false,
                        'label' => 'Destinataires',
                        'attr' => array('data-rel' => 'chosen'))

                );
        return $formBuilder->setMethod('POST')->getForm();

    }


    /*load secteurs from excel*/
    public function sendSMSAction(Request $request)
    {
        $data = array();
        $form = $this->createFormBuilder($data)
            ->add('msg', 'textarea', array('label' => 'Message'))
            ->add('contacts', FileType::class, array('label' => 'contacts (.xls|.xlsx file)', 'required' => false))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /* $file=$form->getData()['contacts'];
              $fileName = 'contacts.'.$file->guessExtension();
              $file->move(
                      $this->getUploadRootDir().'/'.$fileName,
                      $fileName
                  );
          $path = $this->getUploadRootDir().'/'.$fileName;*/
            $path = $this->get('kernel')->getRootDir() . "/../web/import/contacts.xlsx";
            $objPHPExcel = $this->get('phpexcel')->createPHPExcelObject($path);
            $secteurs = $objPHPExcel->getSheet(0);
            $highestRow = $secteurs->getHighestRow();
            $contacts = '%2B237694210203';
            for ($row = 0; $row <= $highestRow; ++$row) {
                $numeroCell = $secteurs->getCellByColumnAndRow(0, $row)->getFormattedValue();
                $numero = '%2B237' . $numeroCell;
                $contacts = $contacts . '%2C' . $numero;
            }
            $msg = $form->getData()['msg'];
            $url = 'https://api-public.mtarget.fr/api-sms.json?username=omegatelecombuilding&password=79sawbfF&msisdn=' . $contacts . '&sender=Concours&msg=' . $msg;
            $res = $this->get('fmc_manager')->sendOrGetData($url, null, 'GET');
            $this->addFlash('success', $url);
            return $this->redirectToRoute('sms_send');
        }
        return $this->render('MessagerBundle:notification:sms.html.twig', array(
            'send_form' => $form->createView(),
        ));
    }


    public function getUploadDir()
    {

        return 'uploads/contacts';
    }

    protected function getUploadRootDir()
    {

        return __DIR__ . '/../../../web/' . $this->getUploadDir();
    }

}
