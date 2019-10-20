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
