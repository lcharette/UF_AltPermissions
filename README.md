# AltPermission Sprinkle for [UserFrosting 4](https://www.userfrosting.com)

[![Build Status](https://github.com/lcharette/UF_AltPermissions/workflows/Build/badge.svg?branch=master)](https://github.com/lcharette/UF_AltPermissions/actions?query=workflow%3ABuild) [![StyleCI](https://github.styleci.io/repos/86100743/shield?branch=master)](https://github.styleci.io/repos/86100743) [![UserFrosting Version](https://img.shields.io/badge/UserFrosting->=%204.2-brightgreen.svg)](https://github.com/userfrosting/UserFrosting) [![Donate](https://img.shields.io/badge/Donate-Buy%20Me%20a%20Coffee-brightgreen.svg)](https://ko-fi.com/A7052ICP)

Alternate/complementary permission system for [UserFrosting 4](https://www.userfrosting.com)

> This sprinkle is still a work in progress and not ready yet for production use. No official release has been made yet. Fell free to test it and contribute, or use it as a reference.

# Help and Contributing

If you need help using this sprinkle or found any bug, feels free to open an issue or submit a pull request. You can also find me on the [UserFrosting Chat](https://chat.userfrosting.com/) most of the time for direct support.

# Installation

Edit UserFrosting `app/sprinkles/sprinkles.json` file and add the following to the `require` list :
```
"lcharette/UF_AltPermissions": "dev-master"
```

Run `composer update` then `composer run-script bake` to install the sprinkle.

# Licence

By [Louis Charette](https://github.com/lcharette). Copyright (c) 2017, free to use in personal and commercial software as per the MIT license.
