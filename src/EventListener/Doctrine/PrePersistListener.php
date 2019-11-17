<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Doctrine;

use Coosos\VersionWorkflowBundle\Entity\VersionWorkflow;
use Coosos\VersionWorkflowBundle\LinkEntity\LinkEntityDoctrine;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\ORMException;

/**
 * Class PrePersistListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Doctrine
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class PrePersistListener
{
    /**
     * @var VersionWorkflowConfiguration
     */
    protected $versionWorkflowConfiguration;

    /**
     * @var LinkEntityDoctrine
     */
    protected $linkEntityDoctrine;

    /**
     * PreFlushListener constructor.
     *
     * @param VersionWorkflowConfiguration $versionWorkflowConfiguration
     * @param LinkEntityDoctrine           $linkEntityDoctrine
     */
    public function __construct(
        VersionWorkflowConfiguration $versionWorkflowConfiguration,
        LinkEntityDoctrine $linkEntityDoctrine
    ) {
        $this->versionWorkflowConfiguration = $versionWorkflowConfiguration;
        $this->linkEntityDoctrine = $linkEntityDoctrine;
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @return $this
     * @throws ORMException
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $insert = $args->getEntity();

        if (!$insert instanceof VersionWorkflow) {
            return $this;
        }

        if ($this->versionWorkflowConfiguration->isAutoMerge($insert->getWorkflowName(), $insert->getMarking())) {
            /** @var VersionWorkflowTrait $originalObject */
            $originalObject = $insert->getOriginalObject();
            if ($originalObject->isVersionWorkflowFakeEntity()) {
                $original = $this->linkEntityDoctrine->linkFakeEntityWithOriginalEntity($originalObject);
                $original->setVersionWorkflow($insert);
                $original->setVersionWorkflowFakeEntity(false);

                $args->getEntityManager()->persist($original);
            } else {
                $args->getEntityManager()->persist($insert->getOriginalObject());
            }
        }

        $insert->setOriginalObject(null);

        return $this;
    }
}
