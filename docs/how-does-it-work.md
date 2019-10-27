# How does it work

## Serializer

This bundle uses JMS Serialiser to have a copy of the object in the JSON format to be stored in order to 
keep the modifications of the object as it evolves (example in the case of a workflow process).

We also use JMS events to add a mapping of the object to also restore bidirectional relationships 
during deserialization.

## With Doctrine

This module use doctrine for `prePersist` for link fake model to doctrine original entity if to be merged,
and `onFlush` event for detach original entity if not to be merged.

Also, store the different transition of the serialized object.
