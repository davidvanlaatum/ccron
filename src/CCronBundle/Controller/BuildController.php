<?php

namespace CCronBundle\Controller;

use CCronBundle\Entity\Job;
use CCronBundle\Entity\JobRun;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BuildController extends Controller {

    /**
     * @Route("/job/{id}/builds", name="viewbuilds")
     * @param $id
     * @return Response
     */
    public function viewBuildsAction($id) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $job = $em->find(Job::class, $id);
        $builds = $em->getRepository(JobRun::class)->forJob($job);
        return $this->render('default/builds.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'builds' => $builds,
            'job' => $job
        ]);
    }

    /**
     * @Route("/job/{job}/console/{id}", name="viewconsole")
     * @param Request $request
     * @param $job
     * @param $id
     * @return Response
     */
    public function viewConsoleAction(Request $request, $job, $id) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $run = $em->find(JobRun::class, $id);
        if (!$run) {
            throw new NotFoundHttpException();
        } else if ($run->getJob()->getId() != $job) {
            throw new NotFoundHttpException();
        }
        $response = new Response($run->getOutput()->getOutput(), 200, ['Content-Type' => 'text/plain']);
        $response->setLastModified($run->getTime());
        $response->setClientTtl(3600);
        $response->setTtl(3600);
        $response->isNotModified($request);
        return $response;
    }

}
