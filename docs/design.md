# Design

# App
We want to minimize deps, but some deps are unavoidable. We need an environment. In many ways that is what a framework is. `App` is ~~the service locator. It's the place where you find all the shared stuff. Currently this entails:

  * A Request and Response object
  * Global error handling.
  * A Logger.
  * App specific configuration.
  * Environment settings.

## The Hive
F3 has a hive concept on its version of `App`. Basically it's a big central nested array. F3 uses this for everything. Instead of having getters and setters well known keys are used. Views in F3 tend to get coupled to the hive. In fact the inbuilt templating engine context is implicitly coupled to it.

Just using a hive for everything is a tempting pattern to fall into. At least it's consistent. But it increases the real coupling. This could be fixed with interface segregation, and we do that here. `App` implements `ArrayAccess` for the hive, and be treated as an array. Also somewhat tentative about using the hive for everything, and incrementally stuffing stuff into it as dev continues. At current `BASE` is set. Any config passed on App init is pulled in too.
