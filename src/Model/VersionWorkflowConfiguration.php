<?php

namespace Coosos\VersionWorkflowBundle\Model;

/**
 * Class VersionWorkflowConfiguration
 *
 * @package Coosos\VersionWorkflowBundle\Model
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class VersionWorkflowConfiguration
{
    /**
     * @var array
     */
    protected $autoMerge;

    /**
     * VersionWorkflowConfiguration constructor.
     *
     * @param array $configurations
     */
    public function __construct(array $configurations)
    {
        $this->autoMerge = [];

        if (isset($configurations['workflows'])) {
            foreach ($configurations['workflows'] as $workflowName => $workflowConf) {
                $this->autoMerge[$workflowName] = $workflowConf['auto_merge'];
            }
        }
    }

    /**
     * Is auto merge
     *
     * @param string $workflowName
     * @param string $currentPlace
     *
     * @return bool
     */
    public function isAutoMerge(string $workflowName, string $currentPlace)
    {
        if (isset($this->autoMerge[$workflowName])) {
            return in_array($currentPlace, $this->autoMerge[$workflowName]);
        }

        return false;
    }
}
