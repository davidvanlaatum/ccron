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

class DefaultController extends Controller {
    /**
     * @Route("/", name="homepage")
     * @return Response
     */
    public function indexAction() {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $jobs = $em->getRepository(Job::class)->findAll();
        $builds = $em->getRepository(JobRun::class)->getRecent();
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'jobs' => $jobs,
            'builds' => $builds
        ]);
    }

    /**
     * @Route("/recentbuilds", name="recentbuilds")
     * @return Response
     */
    public function recentBuilds() {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $builds = $em->getRepository(JobRun::class)->getRecent();
        return $this->render('default/recentbuilds.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'builds' => $builds
        ]);
    }

    /**
     * @Route("/job/{id}/edit", name="editjob")
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function editJob(Request $request, $id) {
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
    public function addJob(Request $request) {
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

    /**
     * @Route("/job/{id}/builds", name="viewbuilds")
     * @param $id
     * @return Response
     */
    public function viewBuilds($id) {
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
     * @param $id
     * @return Response
     */
    public function viewConsole($job, $id) {
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
        return $response;
    }
}
