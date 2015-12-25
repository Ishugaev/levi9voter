<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Admin;

use AppBundle\Enum\FlashbagTypeEnum;
use AppBundle\Event\PostEvent;
use AppBundle\Form\PostType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Post;
use AppBundle\Security\Authorization\Voter\PostVoter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Controller used to manage blog contents in the backend.
 *
 * Please note that the application backend is developed manually for learning
 * purposes. However, in your real Symfony application you should use any of the
 * existing bundles that let you generate ready-to-use backends without effort.
 * See http://knpbundles.com/keyword/admin
 *
 * @Route("/admin/post")
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class BlogController extends Controller
{
    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';

    /**
     * Lists all Post entities.
     *
     * This controller responds to two different routes with the same URL:
     *   * 'admin_post_index' is the route with a name that follows the same
     *     structure as the rest of the controllers of this class.
     *   * 'admin_index' is a nice shortcut to the backend homepage. This allows
     *     to create simpler links in the templates. Moreover, in the future we
     *     could move this annotation to any other controller while maintaining
     *     the route name and therefore, without breaking any existing link.
     *
     * @Route("/", name="admin_index")
     * @Route("/", name="admin_post_index")
     * @Method("GET")
     * @Security("has_role('ROLE_USER')")
     */
    public function indexAction()
    {
        // todo: we need to have user from database in session
        $user = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->findOneByEmail($this->getUser()->getEmail());

        $em = $this->getDoctrine()->getManager();
        $posts = $em->getRepository('AppBundle:Post')->findAccessible($user);

        return $this->render('admin/blog/index.html.twig', array('posts' => $posts));
    }

    /**
     * Creates a new Post entity.
     *
     * @Route("/new", name="admin_post_new")
     * @Method({"GET", "POST"})
     *
     * NOTE: the Method annotation is optional, but it's a recommended practice
     * to constraint the HTTP methods each controller responds to (by default
     * it responds to all methods).
     * @Security("has_role('ROLE_USER')")
     */
    public function newAction(Request $request)
    {
        $post = new Post();
        $form = $this->createForm(new PostType(), $post);

        $form->handleRequest($request);

        // the isSubmitted() method is completely optional because the other
        // isValid() method already checks whether the form is submitted.
        // However, we explicitly add it to improve code readability.
        // See http://symfony.com/doc/current/best_practices/forms.html#handling-form-submits
        if ($form->isSubmitted() && $form->isValid()) {
            // todo: we need to have user from database in session
            $user = $this->getDoctrine()
                ->getRepository('AppBundle:User')
                ->findOneByEmail($this->getUser()->getEmail());

            $post->setAuthor($user);
            $post->setSlug($this->get('slugger')->slugify($post->getTitle()));
            $post->setPublishedAt(new \DateTime());
            $this->attachImage($request, $post);
            $this->changePostState($request, $post);

            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($post);
                $em->flush();

                return $this->redirectToRoute('admin_post_index');
            } catch (UniqueConstraintViolationException $e ) {
                $this->addFlash(
                    FlashbagTypeEnum::ERROR,
                    $this->getTranslator()->trans('flash.vote.error.slug.duplicate')
                );
            }
        }

        return $this->render('admin/blog/new.html.twig', array(
            'post' => $post,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Post entity.
     *
     * @Route("/{id}", requirements={"id" = "\d+"}, name="admin_post_show")
     * @Method("GET")
     * @Security("has_role('ROLE_USER')")
     */
    public function showAction(Post $post)
    {
        $this->denyAccessUnlessGranted(PostVoter::VIEW, $post);

        $deleteForm = $this->createDeleteForm($post);

        return $this->render('admin/blog/show.html.twig', array(
            'post'        => $post,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Post entity.
     *
     * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="admin_post_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_USER')")
     */
    public function editAction(Post $post, Request $request)
    {
        if (!$this->isGranted(PostVoter::EDIT, $post)) {
            return $this->redirectToRoute('admin_post_show', array('id' => $post->getId()));
        }

        $em = $this->getDoctrine()->getManager();

        $editForm = $this->createForm(new PostType(), $post);
        $deleteForm = $this->createDeleteForm($post);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $post->setSlug($this->get('slugger')->slugify($post->getTitle()));

            $this->attachImage($request, $post);
            $this->changePostState($request, $post);

            $em->flush();

            return $this->redirectToRoute('admin_post_edit', array('id' => $post->getId()));
        }

        return $this->render('admin/blog/edit.html.twig', array(
            'post'        => $post,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * @Route("/{id}/publish", name="admin_post_publish")
     * @Method({"POST"})
     * @Security("has_role('ROLE_USER')")
     *
     * @param Post $post
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function publishAction(Post $post, Request $request)
    {
        $this->denyAccessUnlessGranted(PostVoter::PUBLISH, $post);

        $post->publish();

        $em = $this->getDoctrine()->getManager();
        $em->persist($post);
        $em->flush();

        $this->onChangePostState($post);

        $this->addFlash(FlashbagTypeEnum::SUCCESS, $this->getTranslator()->trans('flash.vote.published'));

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('admin_index'));
    }

    /**
     * Deletes a Post entity.
     *
     * @Route("/{id}", name="admin_post_delete")
     * @Method("DELETE")
     * @Security("has_role('ROLE_USER')")
     *
     * The Security annotation value is an expression (if it evaluates to false,
     * the authorization mechanism will prevent the user accessing this resource).
     * The isAuthor() method is defined in the AppBundle\Entity\Post entity.
     */
    public function deleteAction(Request $request, Post $post)
    {
        $this->denyAccessUnlessGranted(PostVoter::DELETE, $post);

        $form = $this->createDeleteForm($post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->remove($post);
            $em->flush();
        }

        //todo: post deleted notification

        return $this->redirectToRoute('admin_post_index');
    }

    /**
     * @Route("/{id}/close/{state}", name="admin_post_close")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_USER')")
     */
    public function approveAction(Request $request, Post $post, $state)
    {
        $this->denyAccessUnlessGranted(PostVoter::CLOSE, $post);

        switch ($state) {
            case self::ACTION_APPROVE:
                $state = Post::STATUS_APPROVED;
                break;
            case self::ACTION_REJECT:
                $state = Post::STATUS_REJECTED;
                break;
            default:
                throw new BadRequestHttpException();
        }

        $post->setState($state);
        $em = $this->getDoctrine()->getManager();

        $em->persist($post);
        $em->flush();

        $this->onChangePostState($post);

        return $this->redirect(
            $request->headers->get('referer', $this->generateUrl('admin_post_show', ['id' => $post->getId()]))
        );
    }

    /**
     * Creates a form to delete a Post entity by id.
     *
     * This is necessary because browsers don't support HTTP methods different
     * from GET and POST. Since the controller that removes the blog posts expects
     * a DELETE method, the trick is to create a simple form that *fakes* the
     * HTTP DELETE method.
     * See http://symfony.com/doc/current/cookbook/routing/method_parameters.html.
     *
     * @param Post $post The post object
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Post $post)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_post_delete', array('id' => $post->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

    /**
     * @param Request $request
     * @param Post $post
     */
    private function changePostState(Request $request, Post $post)
    {
        if ($request->request->has('review')) {
            $post->setState(Post::STATUS_REVIEW);
        } elseif ($request->request->has('publish') && $this->isGranted('ROLE_ADMIN')) {
            $post->setState(Post::STATUS_VOTING);
        }

        $this->onChangePostState($post);
    }

    /**
     * @param Request $request
     * @param Post $post
     */
    private function attachImage(Request $request, Post $post)
    {
        if ($id = $request->request->get('fileid')) {
            $em = $this->getDoctrine()->getManager();
            if ($image = $em->getRepository('AppBundle:Image')->findOneBy(['id' => $id])) {
                $post->addImage($image);
            }
        }
    }

    /**
     * @param Post $post
     */
    private function onChangePostState(Post $post)
    {
        $this->getDispatcher()->dispatch('app.post.on_status_change', new PostEvent($post));
    }

    /**
     * @return \Symfony\Component\Translation\DataCollectorTranslator
     */
    private function getTranslator()
    {
        return $this->get('translator');
    }

    /**
     * @return \Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher
     */
    private function getDispatcher()
    {
        return $this->get('event_dispatcher');
    }
}
