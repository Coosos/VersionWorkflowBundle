# Install

## Step 1 : Install bundle

Use composer for install bundle in your project :

    $ composer req coosos/version-workflow-bundle

## Step 2 : Enable bundle

    // config/bundles.php

    return [
        // ...
        Coosos\VersionWorkflowBundle\CoososVersionWorkflowBundle::class => ['all' => true],
    ];

## Step 3 : Install new table in your database (is use doctrine)

    $ php bin/console doctrine:schema:update -f
