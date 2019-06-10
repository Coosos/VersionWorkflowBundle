# Usage

# Prepare your model

For your model to work with bundle, is necessary to use VersionWorkflowTrait.

    class News
    {
        use \Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
    }

_Note: With doctrine, is necessary to update schema_

## Apply transition and transform model

    public function __construct(Coosos\VersionWorkflowBundle\Service\VersionWorkflowService $versionWorkflowService)
    {
        $this->versionWorkflowService = $versionWorkflowService;
    }

    public function add()
    {
        $news = ....
        $news->setTitle('Hello world');

        $workflowName = 'news_process';
        $transition = 'to_review'; // Use null for use initialized place
        
        $versionWorkflowModel = $this->versionWorkflowService->applyTransitionAndTransformToVersionWorkflow(
            $news,
            $workflowName,
            $transition
        );
    }

## Transform Version Workflow to original entity

_Note : If use with doctrine, this entity getting is fake object, but is transform for doctrine in prePersist event_


    public function get($id)
    {
        $versionWorkflowModel = ...
        $news = $this->versionWorkflowService->transformToObject($versionWorkflowModel);
        
        $news->getTitle();
        
        $transition = 'publish';
        
        // Update and transform to version workflow
        $versionWorkflowModel = $this->versionWorkflowService->applyTransitionAndTransformToVersionWorkflow(
            $news,
            $workflowName,
            $transition
        );
    }
