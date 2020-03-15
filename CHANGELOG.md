# 0.1.5

* Move mapping serializer to own package (``coosos/jms-serializer-bidirectional-relation``)
  * Fix array keys who an auto reset
* Fix array link entity to original
* DetachEntity (based on doctrine 2.7) class for recursive detach directly in properties of UnitOfWork
* Reorganize / Optimize code

# 0.1.4

* Update test
  * Add deserializer context
* Fix map subscriber error if field not exist in map array
* Moving the entity link procedure
* Fix if key is changed in collection relation

# 0.1.3

* Fix dependency injection for ``Coosos\VersionWorkflowBundle\EventListener\Doctrine\OnFlushListener``
* Add exclusion strategy to deserializer context

# 0.1.2

* Add Exclusion Strategy for exclude fields from VersionWorkflowTrait

# 0.1.1

* Change JMS requirements to ^2.4||^3.0

# 0.1.0

* Update PHP required to ``^7.2``
* Use JMS Serializer instead of Symfony Serializer
    * Create a ``MapSubscriber`` class to create a mapping of the object to
      restore the relationships of a deserialized object
    * Add test for testing MapSubscriber
* Remove old / unused code
* Add test for ``VersionWorkflowService``
* Remove ``SerializerService`` and inject directly JMS Serializer Interface
* Add edgedesign/phpqa with phpunit, phpmd, phpcs and security-checker

# 0.0.1

* First version
