# Design

# App
We want to minimize deps, but some deps are unavoidable. We an environment. In many wasy that is what a framework is. App is ~~the service locator. Its the place where you find all the shared stuff. Currently this entails:

  * A Request and Response.
  * Global error handling.
  * A Logger.
  * App specific configuration.
  * Environment settings

## The Hive
F3 has a hive concept on its version of App. Basically its a big central nested array. F3 uses this for everything. Instead of having getters and setters well known keys are used. Views tend to get coupled to the hive. In fact the inbuilt templating engine context is implicitly coupled to it.

Juts using a hive for everything is a tempting pattern to fall into. At least its consistent. But it increases the real coupling. Everything gets coupled to it. This could be fixed with ISP We do that here. App implements ArrayAccess for the hive. Using the Hive forr everything also increases potential coupling because everything potentially has access to everything else. Hence we are somewhat tentative  about using the hive for everything and incrementally stuffing stuff into it as dev continues. At current:

  BASE
