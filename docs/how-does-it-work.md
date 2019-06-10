# How does it work

## Transform and serialize

During the transformation, the object is copied completely to be modified in order to be serialized.
After transform version workflow model returned who contains serialized object and other information for track.

## With Doctrine

This module use doctrine for `prePersist` for link fake model to doctrine original entity if to be merged,
and `onFlush` event for detach original entity if not to be merged.
