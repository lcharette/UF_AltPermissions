<?php

namespace UserFrosting\Tests\Unit;

use UserFrosting\Tests\TestCase;
use UserFrosting\Tests\DatabaseTransactions;
use League\FactoryMuffin\Faker\Facade as Faker;

use UserFrosting\Sprinkle\AltPermissions\Model\Role;
use UserFrosting\Sprinkle\AltPermissions\Model\RoleLocale;

class RoleLocaleTest extends TestCase
{
    use DatabaseTransactions;

    // First, make sure the locale is right
    public function testLocale()
    {
        /** @var UserFrosting\I18n\MessageTranslator $translator */
        $translator = $this->ci->translator;

        /** @var UserFrosting\Config\Config $config */
        $config = $this->ci->config;

        // Force locale to engligh
        $translator->loadLocaleFiles('en_US');

        // If this fails, probably someone messed with the locale and next tests will also fail because of that.
        // (Should probably have my own locale)
        $this->assertEquals("The selected role doesn't exist", $translator->translate('AUTH.NOT_FOUND'));
        $this->assertEquals("The selected role seeker is invalid", $translator->translate('AUTH.BAD_SEEKER'));

        // Set locale as french as run the same tests
        $translator->loadLocaleFiles('fr_FR');
        $this->assertEquals("Le rôle sélectionné n'existe pas", $translator->translate('AUTH.NOT_FOUND'));
        $this->assertEquals("Le demandeur de rôle sélectionné n'est pas valide", $translator->translate('AUTH.BAD_SEEKER'));
    }

    public function testLocaleCache()
    {
        // @var League\FactoryMuffin\FactoryMuffin
        $fm = $this->ci->factory;

        /** @var UserFrosting\Config\Config $config */
        $config = $this->ci->config;

        /** @var UserFrosting\I18n\MessageTranslator $translator */
        $translator = $this->ci->translator;

        // Create 1 role. Use
        $role =  $fm->create('UserFrosting\Sprinkle\AltPermissions\Model\Role', [
            'seeker' => 'foo',
            'name' => "AUTH.NOT_FOUND",
            'description' => "AUTH.BAD_SEEKER"
        ]);

        // Force english language
        $config->set('site.locales.default', 'en_US');
        $translator->loadLocaleFiles('en_US');

        // Try updating it's locale cache
        $role->updateLocaleCache();

        // Now fetch the locale back to make sure
        $newRole = Role::find($role->id);

        // Get the locale, and make sure it return the english one
        $roleLocale = $newRole->locale()->forCurrentLocale()->first();
        $this->assertEquals("The selected role doesn't exist", $roleLocale->name);
        $this->assertEquals("The selected role seeker is invalid", $roleLocale->description);

        // Should also work with the french locale if we force language to FR
        $config->set('site.locales.default', 'fr_FR');
        $translator->loadLocaleFiles('fr_FR');

        // Right now, cache is not built for french, so we except null/empty
        // We need to update the cache so the french one will be loaded
        $newRole->updateLocaleCache();

        // Test the french locale. Make sure to fetch again
        $frenchRole = Role::find($role->id);
        $roleLocale = $frenchRole->locale()->forCurrentLocale()->first();
        $this->assertEquals("Le rôle sélectionné n'existe pas", $roleLocale->name);
        $this->assertEquals("Le demandeur de rôle sélectionné n'est pas valide", $roleLocale->description);
    }
}