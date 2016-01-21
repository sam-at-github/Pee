{
  "name": "sam-at-github/pee",
  "minimum-stability": "dev",
  "prefer-source": true,
  "require":
  {
    "sam-at-github/phpacl": "*",
    "sam-at-github/models": "*",
    "jquery/jquery": "*",
    "twig/twig": "1.15.*",
    "psr/log": "1.0.*"
  },
  "repositories": [
    {
      "type": "package",
      "package": {
        "name": "jquery/jquery",
        "version": "1.11.1",
        "dist": {
          "url": "https://code.jquery.com/jquery-1.11.1.min.js",
          "type": "file"
        }
      }
    },
  ],
  "scripts": {
    "post-install-cmd": "[ -d webroot/js/ ]] || mkdir -p webroot/js/; cp vendor/jquery/jquery/jquery-1.11.1.min.js webroot/js/; cd webroot/js/ && ln -f -s jquery-1.11.1.min.js jquery.js",
    "post-update-cmd":  "[ -d webroot/js/ ] || mkdir -p webroot/js/; cp vendor/jquery/jquery/jquery-1.11.1.min.js webroot/js/; cd webroot/js/ && ln -f -s jquery-1.11.1.min.js jquery.js"
  }
}
