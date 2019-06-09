# Config

In `config/packages` forlder, create `version_workflow.yml` config file.

## Example

    coosos_version_workflow:
      workflows:
        blog_publishing: # Workflow name
          auto_merge: # Auto merge by place
            - published
