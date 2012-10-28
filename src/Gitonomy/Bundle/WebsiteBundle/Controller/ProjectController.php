<?php

namespace Gitonomy\Bundle\WebsiteBundle\Controller;

use Gitonomy\Bundle\CoreBundle\Entity\Project;
use Gitonomy\Component\Pagination\Pager;
use Gitonomy\Component\Pagination\Adapter\GitLogAdapter;
use Gitonomy\Git\Reference;

class ProjectController extends Controller
{
    public function listAction()
    {
        $this->assertGranted('IS_AUTHENTICATED_REMEMBERED');

        $pool = $this->get('gitonomy_core.git.repository_pool');

        $entities = $this->getRepository('GitonomyCoreBundle:Project')->findByUser($this->getUser());
        $projects = array();
        foreach ($entities as $entity) {
            $projects[] = array($entity, $pool->getGitRepository($entity));
        }

        return $this->render('GitonomyWebsiteBundle:Project:list.html.twig', array(
            'projects' => $projects
        ));
    }

    public function createAction()
    {
        $this->assertGranted('ROLE_PROJECT_CREATE');

        $project = new Project();
        $form    = $this->createForm('project', $project);
        $request = $this->getRequest();

        if ('GET' === $request->getMethod()) {
            return $this->render('GitonomyWebsiteBundle:Project:create.html.twig', array(
                'form' => $form->createView()
            ));
        }

        $form->bind($request);

        if ($form->isValid()) {
            $this->persistEntity($project);
            $this->setFlash('success', $this->trans('notice.success', array(), 'project_create'));

            return $this->redirect($this->generateUrl('project_newsfeed', array('slug' => $project->getSlug())));
        }

        $this->setFlash('error', $this->trans('error.form_invalid', array(), 'register'));

        return $this->render('GitonomyWebsiteBundle:Project:create.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function newsfeedAction($slug)
    {
        return $this->render('GitonomyWebsiteBundle:Project:newsfeed.html.twig');
    }

    public function historyAction($slug, $reference = null, $path = null)
    {
        $project    = $this->getProject($slug);
        $repository = $this->getGitRepository($project);
        $request    = $this->getRequest();
        $reference  = $request->query->get('reference');
        $log        = $repository->getLog($reference);

        $pager = new Pager(new GitLogAdapter($log));
        $pager->setPerPage(50);
        $pager->setPage($page = $request->query->get('page', 1));

        $project    = $this->getProject($slug);
        $repository = $this->getGitRepository($project);

        $references = $repository->getReferences();
        $referenceName = function (Reference $reference) {
            return $reference->getName();
        };

        $convert = function ($commit) use ($references, $referenceName) {
            return array(
                'hash'          => $commit->getHash(),
                'short_message' => $commit->getShortMessage(),
                'parents'       => $commit->getParentHashes(),
                'tags'          => array_map($referenceName, $references->resolveTags($commit)),
                'branches'      => array_map($referenceName, $references->resolveBranches($commit)),
            );
        };

        return $this->render('GitonomyWebsiteBundle:Project:history.html.twig', array(
            'project'    => $project,
            'reference'  => $reference,
            'repository' => $repository,
            'pager'      => $pager,
            'data'       => array_map($convert, (array) $pager->getResults()),
            'parent_path'   => $path === '' ? null : substr($path, 0, strrpos($path, '/')),
            'path'          => $path,
            'path_exploded' => explode('/', $path),
        ));
    }

    /**
     * Displays a commit.
     */
    public function commitAction($slug, $hash)
    {
        $project    = $this->getProject($slug);
        $repository = $this->getGitRepository($project);
        $commit     = $repository->getCommit($hash);

        return $this->render('GitonomyWebsiteBundle:Project:commit.html.twig', array(
            'project'    => $project,
            'repository' => $repository,
            'reference'  => $project->getDefaultBranch(),
            'commit'     => $commit
        ));
    }

    public function sourceAction($slug)
    {
        return $this->render('GitonomyWebsiteBundle:Project:source.html.twig');
    }

    public function branchesAction($slug)
    {
        return $this->render('GitonomyWebsiteBundle:Project:branches.html.twig');
    }

    public function tagsAction($slug)
    {
        return $this->render('GitonomyWebsiteBundle:Project:tags.html.twig');
    }

    /**
     * @return Project
     */
    protected function getProject($slug)
    {
        $user = $this->getUser();

        $project = $this->getDoctrine()->getRepository('GitonomyCoreBundle:Project')->findOneBySlug($slug);
        if (null === $project) {
            throw $this->createNotFoundException(sprintf('Project with slug "%s" not found', $slug));
        }

        if (!$this->get('security.context')->isGranted('PROJECT_CONTRIBUTE', $project)) {
            throw $this->createAccessDeniedException('You are not contributor of the project');
        }

        return $project;
    }
}
