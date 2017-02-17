<?php

namespace CCronBundle\Controller;

use CCronBundle\Entity\Job;
use CCronBundle\Entity\JobRun;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @Route("/builds/recent", name="builds_recent")
     * @param Request $request
     * @return Response
     */
    public function recentBuildsAction(Request $request) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $builds = $em->getRepository(JobRun::class)->getRecent();
        $date = null;
        foreach ($builds as $build) {
            if ($date < $build->getTime()) {
                $date = $build->getTime();
            }
        }
        $response = new Response();
        $response->setCache([
            'last_modified' => $date,
            'max_age' => 60,
            'public' => true
        ])->headers->addCacheControlDirective('must-revalidate', true);
        if (!$response->isNotModified($request)) {
            $this->render('default/recentbuilds.html.twig', [
                'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
                'builds' => $builds
            ], $response);
        }
        return $response;
    }
}
