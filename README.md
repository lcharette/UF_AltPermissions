# UF-AltPermission
Alternate/complementary permission system for UserFrosting

## Install
Edit UserFrosting `app/sprinkles/sprinkles.json` file and add the following to the `require` list :
```
"lcharette/uf_altpermissions": "dev-master"
```

Run `composer update` then `composer run-script bake` to install the sprinkle.