[![SymfonyInsight](https://insight.symfony.com/projects/ece16dcb-410d-4051-ad20-08bb18368afb/mini.svg)](https://insight.symfony.com/projects/ece16dcb-410d-4051-ad20-08bb18368afb)

# Coosos/VersionWorkflowBundle

**/!\ This bundle is currently under development /!\\**

If you wish, you can contribute to the project :)

## This bundle required or used

| Package       | Version |
| ------------- | ------- |
| PHP           | ^7.2    |
| Symfony       | ^4.2    |
| JMS Serialier | ^3.4    |

## Description

This bundle uses the Symfony Workflow bundle to track transitions in another table. 

Once all transitions are complete, it merges the object into the original table. 

If an old entity yet exists the same identifier, it will be replaced while keeping the same identifier. 

**This can be very useful for avoiding a loss of SEO from search engines.**

## Navigation

* [How does it work](docs/how-does-it-work.md)
* [Installation](docs/install.md)
* [Configuration](docs/config.md)
* [Basic usage](docs/usage.md)
* [Serializer](https://github.com/schmittjoh/serializer) : I propose directly to you to see the documentation of JMS.
* [Use with doctrine](docs/doctrine.md)
