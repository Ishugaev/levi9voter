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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UploadController
 *
 * @Route("/upload")
 */
class UploadController extends Controller
{
    /**
     * @Route("/image", name="image_upload")
     * @Method("POST")
     * @Security("has_role('ROLE_USER')")
     */
    public function uploadImageAction(Request $request)
    {
        $image = new Image();
        $form = $this->createForm(new UploadType(), $image);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($image);
            $em->flush();

            return new JsonResponse(array(
                'id' => $image->getId(),
                'image' => $image->getWebPath()
            ));
        }

        return new JsonResponse('An error has occurred', 400);
    }
}
