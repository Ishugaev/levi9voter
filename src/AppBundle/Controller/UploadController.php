<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Image;
use AppBundle\Form\UploadType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class UploadController
 */
class UploadController extends Controller
{
    /**
     * @Route("/upload/image", name="image_upload")
     * @Method("POST")
     * @Security("has_role('ROLE_USER')")
     */
    public function uploadImageAction(Request $request)
    {
        $form = $this->createForm(new UploadType());
        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var Image $image */
            $image = $form->getData();
            $repository = $this->getDoctrine()->getRepository('AppBundle:User');
            $user = $repository->findOneBy(['id' => $this->getUser()->getId()]);

            $image->setUser($user);


            $em = $this->getDoctrine()->getManager();
            $em->persist($image);
            $em->flush();

            return new JsonResponse(array(
                'id' => $image->getId(),
                'url' => $image->getWebPath(),
                'deleteUrl' => $this->generateUrl('image_remove', ['id' => $image->getId()]),
                'name' => $image->getName(),
            ));
        }

        return new JsonResponse('An error has occurred', 400);
    }

    /**
     * @Route("/image/{id}", name="image_remove")
     * @Method("DELETE")
     * @Security("has_role('ROLE_USER')")
     *
     * @ParamConverter("post", class="AppBundle:Image")
     */
    public function removeImageAction(Image $image)
    {
        if ($image && $image->getUser()->getId() == $this->getUser()->getId()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($image);
            $em->flush();

            return new JsonResponse([]);
        } else {
            throw new BadRequestHttpException();
        }
    }
}
