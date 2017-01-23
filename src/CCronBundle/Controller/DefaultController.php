<?php

namespace CCronBundle\Controller;

use CCronBundle\Entity\Job;
use CCronBundle\Entity\JobRun;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller {
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $query = $em->getRepository(Job::class)->createNamedQuery("jobs.all");
        /** @var Job[] $jobs */
        $jobs = $query->execute();
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'jobs' => $jobs
        ]);
    }

    /**
     * @Route("/editjob/{id}", name="editjob")
     */
    public function editJob(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();
        $job = $em->find(Job::class, $id);
        $form = $this->createForm(JobForm::class, $job);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $job = $form->getData();
            $em->persist($job);
            $em->flush();
            return $this->redirectToRoute('homepage');
        }
        return $this->render('default/editjob.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'job' => $job,
            'jobform' => $form->createView()
        ]);
    }

    /**
     * @Route("/addjob", name="addjob")
     */
    public function addJob(Request $request) {
        $form = $this->createForm(JobForm::class, new Job());
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
     * @Route("/viewbuilds/{id}", name="viewbuilds")
     */
    public function viewBuilds(Request $request, $id) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $query = $em->getRepository(JobRun::class)->createNamedQuery("findForJob");
        $job = $em->find(Job::class, $id);
        $builds = $query->execute(["job" => $job]);
        return $this->render('default/builds.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'builds' => $builds,
            'job' => $job
        ]);
    }

    /**
     * @Route("/viewconsole/{id}", name="viewconsole")
     */
    public function viewConsole(Request $request,$id) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $run = $em->find(JobRun::class, $id);
        $res = new Response('plain/text');
        $res->setContent($run->getOutput());
        return $res;
    }
}
