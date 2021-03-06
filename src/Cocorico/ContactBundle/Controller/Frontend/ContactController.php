<?php

namespace Cocorico\ContactBundle\Controller\Frontend;

use Cocorico\ContactBundle\Entity\Contact;
use Cocorico\ContactBundle\Form\Type\Frontend\ContactNewType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Booking controller.
 *
 * @Route("/contact")
 */
class ContactController extends Controller
{
    /**
     * Creates a new Contact entity.
     *
     * @Route("/new", name="cocorico_contact_new")
     *
     * @Method({"GET", "POST"})
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $contact = new Contact();
        $form = $this->createCreateForm($contact);

        $googleReCaptchaIsValid = true;
        if ($request->isMethod('POST') && $request->request->has('g-recaptcha-response') && $request->request->has('g-recaptcha-response')) {
            // Google reCAPTCHA API secret key
            $secretKey = $this->getParameter('google_recaptcha_secret_key');

            // Verify the reCAPTCHA response
            $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secretKey . '&response=' . $request->request->get('g-recaptcha-response'));

            // Decode json data
            $responseData = json_decode($verifyResponse);

            if ($responseData->success) {
                $submitted = $this->get('cocorico_contact.form.handler.contact')->process($form);
                if ($submitted !== false) {
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans('contact.new.success', [], 'cocorico_contact')
                    );

                    return $this->redirect($this->generateUrl('cocorico_contact_new'));
                }
            }
            else {
                $form->handleRequest($request);
                $googleReCaptchaIsValid = false;
            }
        }

        return $this->render(
            'CocoricoContactBundle:Frontend:index.html.twig',
            [
                'form' => $form->createView(),
                'googleReCaptchaIsValid' => $googleReCaptchaIsValid,
            ]
        );
    }

    /**
     * Creates a form to create a contact entity.
     *
     * @param Contact $contact The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Contact $contact)
    {
        $form = $this->get('form.factory')->createNamed(
            '',
            ContactNewType::class,
            $contact,
            [
                'method' => 'POST',
                'action' => $this->generateUrl('cocorico_contact_new'),
            ]
        );

        return $form;
    }
}
