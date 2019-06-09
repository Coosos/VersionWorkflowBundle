# Usage

## Create Version Workflow Model

    class DefaultController
    {
        public function __construct(Coosos\VersionWorkflowBundle\Service\VersionWorkflowService $versionWorkflowService)
        {
            $this->versionWorkflowService = $versionWorkflowService;
        }
    
        public function add()
        {
            $news = new News();
            $news->setTitle('Hello world');
    
            $entityManager->persist($news); // Use persist for execute doctrine event if necessary
    
            $this->versionWorkflowService->applyTransition($news, 'news', null); // For initial place
            $versionWorkflowModel = $this->versionWorkflowService->transformToVersionWorkflowModel($news, 'news');
    
            $entityManager->persist($versionWorkflowModel);
    
            $entityManager->flush();
        }
    }

