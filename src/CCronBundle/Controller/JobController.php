<?php

namespace CCronBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CCronBundle\Entity\Job;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JobController extends Controller {

    /**
     * @Route("/job/{id}/edit", name="editjob")
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function editJobAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();
        $job = $em->find(Job::class, $id);
        $form = $this->createForm(JobForm::class, $job);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $job = $form->getData();
            if ($form->getClickedButton()->getName() == 'delete') {
                $em->remove($job);
                $em->flush();
                return $this->redirectToRoute('homepage');
            } else if ($form->isValid()) {
                $em->persist($job);
                $em->flush();
                return $this->redirectToRoute('homepage');
            }
        }
        return $this->render('default/editjob.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'job' => $job,
            'jobform' => $form->createView()
        ]);
    }

    /**
     * @Route("/job/add", name="addjob")
     * @param Request $request
     * @return Response
     */
    public function addJobAction(Request $request) {
        $form = $this->createForm(JobForm::class, new Job());
        $form->remove('delete');
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $job = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($job);
            $em->flush();
            return $this->redirectToRoute('homepage');
        }
        return $this->render('default/editjob.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'jobform' => $form->createView()
        ]);
    }
}
