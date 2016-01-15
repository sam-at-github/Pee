# Aims
Overall aim is to create a simple elegant web framework, with generally good software qualities. Specifically small, simple, elegant, intuitive, extensible, integral, modular. Good constraints are the basis for conceptual integrity.

# Objectives

  * Pretty much I want to take F3 and:
    - Pull out the router.
    - Make it have a proper OO structure.
    - Pull out the hive.

# Design

## Router
A Router is a collection of routes. Routes point ot some callable. The responsibility of the router is to direct a raw HTTP reuest to some callable. It may also pass parameters out of the request specified in the route to the route targe. Routes and router only deal with the path component of a URL - not the schem, authority, query or fragment.



Router:
  __construct()
  addRoute(Route)
  deleteRoute(Route|index|name)
  getRoute(Route|index|name)
  getRoutes()
  route(HttpRequest);
  <iterator>

Route
  __construct(method, path, RouteTarget)
  __toString()
  buildPath([params]);


Issues:

*Aliases*
Do we need aliases? Why not just add two router with the same target?

*Redirects*
Do we need to do redirects in the router? Whys just App::getBaseURI() "/" . App::Router->getRoute(name)->buildPath().


## Hive
As used in F3, its a global singleton namespace for access to ~everything. In generall though a hive is a nested hash data structure, and we need not have just one.

  * Convenience accessors `[]`, `.`, and combonations of.
  * Should support.
    - get,set,unset,isset,delete,push,pop,shift,unshift,[],.
    - ref,copy,sync
    - toArray, fromArray

### Hive References
Considering the F3 hive. The primary interface to the Hive does not use references. However you can get a reference, set a hive variable to reference another, and make one hive variable reference another hive variable with:

    - ref,copy,sync

References are somewhat useful for aliasing stuff. For example certain thing from the PHP env are aliased into F3's hive for convenience.

### Review of F3 Hive
The hive is a useful idea. It's especially useful for rendering - provides a simepl consistent data container for view to get at stuff. I never liked the way the hive is *used* in F3, as a singleton containing essentially references to evrything. And as the primary mechanism for communication amongst everything. For one the parge potential for dependencies on this singleton makes, well potentiall lots of dependencies, makgin F3 hard to test and pull apart. But in a web frameworks somekind of global state like this is so useful. Ive almost come around to it.

Pros:

  * Simple
  * Flexible
  * Can we avoid a singleton data structure like this. And should we.

Cons:

  * Increases potential for dependencies
  * Increases potential for scope creep - passing a hive around everywhere weakens the responsiblity boundaries.

Conclusion

  * Have a hive type.
  * Allow new hive to be created, even cloned.
  * Make module depend on a hive not implicitly *the* hive (use them differently).
