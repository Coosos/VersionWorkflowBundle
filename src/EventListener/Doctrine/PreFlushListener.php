<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Doctrine;

use Coosos\VersionWorkflowBundle\Entity\VersionWorkflow;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Doctrine\ORM\Event\PreFlushEventArgs;

/**
 * Class PreFlushListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Doctrine
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class PreFlushListener
{
    /**
     * @var VersionWorkflowConfiguration
     */
    private $versionWorkflowConfiguration;

    /**
     * PreFlushListener constructor.
     *
     * @param VersionWorkflowConfiguration $versionWorkflowConfiguration
     */
    public function __construct(VersionWorkflowConfiguration $versionWorkflowConfiguration)
    {
        $this->versionWorkflowConfiguration = $versionWorkflowConfiguration;
    }

    /**
     * @param PreFlushEventArgs $args
     * @throws \Doctrine\ORM\ORMException
     */
    public function preFlush(PreFlushEventArgs $args)
    {
        $inserts = $args->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions();
        foreach ($inserts as $insert) {
            if (!$insert instanceof VersionWorkflow) {
                continue;
            }

            if ($this->versionWorkflowConfiguration->isAutoMerge($insert->getWorkflowName(), $insert->getMarking())) {
                $args->getEntityManager()->persist($insert->getOriginalObject());
            }
        }
    }
}
