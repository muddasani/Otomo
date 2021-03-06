<?php

namespace Cocorico\CoreBundle\Controller\Frontend;

use Cocorico\CoreBundle\Entity\Listing;
use Cocorico\CoreBundle\Form\Type\Frontend\ListingNewType;
use Cocorico\UserBundle\Entity\User;
use Doctrine\ORM\OptimisticLockException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Listing controller.
 *
 * @Route("/listing")
 */
class ListingController extends Controller
{
    /**
     * Creates a new Listing entity.
     *
     * @Route("/new", name="cocorico_listing_new", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @return RedirectResponse|Response
     * @throws OptimisticLockException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function newAction()
    {
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {

            /** @var User $user */
            $user = $this->getUser();
            $excludedAdmins = $this->getParameter('cocorico_excluded_admin');

            if(!in_array($user->getEmail(), $excludedAdmins)) {
                throw $this->createAccessDeniedException();
            }
        }

        $formHandler = $this->get('cocorico.form.handler.listing');

        $listing = $formHandler->init();
        $form = $this->createCreateForm($listing);
        $success = $formHandler->process($form);

        if ($success) {
            $cache = $this->get('cache.app');

            $cache->deleteItem('sitemap-id');
            $cache->deleteItem('sitemap-en');
            $this->get('cocorico.sitemap')->getSitemapXml('en');
            $this->get('cocorico.sitemap')->getSitemapXml('id');

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('listing.new.success', [], 'cocorico_listing')
            );

            return $this->redirectToRoute('cocorico_dashboard_listing_edit_presentation', ['id' => $listing->getId()]);
        }

        return $this->render('CocoricoCoreBundle:Frontend/Listing:new.html.twig',
            [
                'listing' => $listing,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Creates a form to create a Listing entity.
     *
     * @param Listing $listing The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Listing $listing)
    {
        $form = $this->get('form.factory')->createNamed(
            'listing',
            ListingNewType::class,
            $listing,
            [
                'method' => 'POST',
                'action' => $this->generateUrl('cocorico_listing_new'),
            ]
        );

        return $form;
    }

    /**
     * Finds and displays a Listing entity.
     *
     * @Route("/{slug}/show", name="cocorico_listing_show", requirements={
     *      "slug" = "[a-z0-9-]+$"
     * })
     * @Method("GET")
     * @Security("is_granted('view', listing)")
     * @ParamConverter("listing", class="Cocorico\CoreBundle\Entity\Listing", options={"repository_method" = "findOneBySlug"})
     *
     * @param Request $request
     * @param Listing $listing
     *
     * @return Response
     */
    public function showAction(Request $request, Listing $listing = null)
    {
        if ($redirect = $this->handleSlugChange($listing, $request->get('slug'))) {
            return $redirect;
        }
        $reviews = $this->get('cocorico.review.manager')->getListingReviews($listing);

        //Breadcrumbs
        $breadcrumbs = $this->get('cocorico.breadcrumbs_manager');
        $breadcrumbs->addListingShowItems($request, $listing);

        return $this->render('CocoricoCoreBundle:Frontend/Listing:show.html.twig', [
            'listing' => $listing,
            'reviews' => $reviews,
        ]);
    }

    /**
     * Handle listing slug change 301 redirection
     *
     * @param Listing $listing
     * @param         $slug
     * @return bool|RedirectResponse
     */
    private function handleSlugChange(Listing $listing, $slug)
    {
        if ($slug != $listing->getSlug()) {
            return $this->redirectToRoute('cocorico_listing_show', ['slug' => $listing->getSlug()], 301);
        }

        return false;
    }
}
