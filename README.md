# AltPermission Sprinkle for [UserFrosting 4](https://www.userfrosting.com)

Alternate/complementary permission system for [UserFrosting 4](https://www.userfrosting.com)

> This sprinkle is still a work in progress and not ready yet for production use. No official release has been made yet. Fell free to test it and contribute, or use it as a reference.

# Help and Contributing

If you need help using this sprinkle or found any bug, feels free to open an issue or submit a pull request. You can also find me on the [UserFrosting Chat](https://chat.userfrosting.com/) most of the time for direct support.

<a href='https://ko-fi.com/A7052ICP' target='_blank'><img height='36' style='border:0px;height:36px;' src='https://az743702.vo.msecnd.net/cdn/kofi4.png?v=0' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>

# Installation

Edit UserFrosting `app/sprinkles/sprinkles.json` file and add the following to the `require` list :
```
"lcharette/uf_altpermissions": "dev-master"
```

Run `composer update` then `composer run-script bake` to install the sprinkle.

# Licence

By [Louis Charette](https://github.com/lcharette). Copyright (c) 2017, free to use in personal and commercial software as per the MIT license.