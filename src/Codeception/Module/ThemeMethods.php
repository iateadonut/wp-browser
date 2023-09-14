<?php

namespace Codeception\Module;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

trait ThemeMethods
{
    /**
     * Moves to the themes administration page.
     *
     * @return void The client moves to the themes administration page.
     */
    public function amOnThemesPage()
    {
        $this->amOnAdminPage('themes.php');
    }

    /**
     * Returns the list of available themes.
     *
     * The method will **not** handle authentication and navigation to the themes administration page.
     *
     * @param string $classes A list of classes to filter the themes by; these will be appended to the `.theme` one.
     *
     * @return string[] A list of available theme slugs.
     *
     * @example
     * ```php
     * $I->loginAsAdmin();
     * $I->amOnThemesPage();
     * $available = $I->grabAvailableThemes();
     * $active = $I->grabAvailableThemes('.active');
     * ```
     */
    public function grabAvailableThemes($classes = '.theme')
    {
        if (method_exists($this, 'waitForElement')) {
            $this->waitForElement("//div[@class='theme']");
        }

        $found = $this->_findElements(".theme$classes .theme-id-container .theme-name[id]");

        if (is_array($found)) {
            /** @var Facebook\WebDriver\Remote\RemoteWebElement[] $found */
            $slugs = array_map(
                function ($el) {
                    return preg_replace('/-name$/', '', $el->getAttribute('id'));
                },
                $found
            );

            return array_values(array_filter($slugs));
        }

        /** @var Crawler $found */
        $slugs = $found->each(
            function (Crawler $el) {
                $node = $el->getNode(0);

                if (!$node instanceof DOMElement) {
                    return false;
                }

                $rawSlug = $node->getAttribute('id');

                if (!$rawSlug) {
                    return false;
                }

                return preg_replace('/-name$/', '', $rawSlug);
            }
        );

        return array_values(array_filter($slugs));
    }

    /**
     * Returns the slug of the currently active themes.
     *
     * The method will **not** handle authentication and navigation to the themes administration page.
     *
     * @return string|null The slug of the currently active theme or `null` if no theme is active.
     *
     * @example
     * ```php
     * $I->loginAsAdmin();
     * $I->amOnThemesPage();
     * $active = $I->grabActiveTheme();
     * ```
     */
    public function grabActiveTheme()
    {
        $active = $this->grabAvailableThemes('.active');
        return count($active) ? $active[0] : null;
    }

    /**
     * Activates a theme.
     *
     * The method will **not** handle authentication and navigation to the themes administration page.
     *
     * @param string $slug The theme slug.
     *
     * @return void The theme is activated.
     *
     * @example
     * ```php
     * $I->loginAsAdmin();
     * $I->amOnThemesPage();
     * $I->activateTheme('storefront');
     * ```
     */
    public function activateTheme($slug)
    {
        if (method_exists($this, 'waitForElement')) {
            $this->waitForElement("//div[@class='theme']");
        }

        // Exclude active themes.
        $xpath = "//div[@class='theme'][@class!='active']" .
            // Pick theme-container with a child matching the slug.
            "//div[@class='theme-id-container'][.//*[@class='theme-name' and @id='$slug-name']]";

        $this->click('.button.activate', $xpath);
    }

    /**
     * Verifies that a theme is active.
     *
     * The method will **not** handle authentication and navigation to the themes administration page.
     *
     * @param string $slug The theme slug.
     *
     * @return void Verifies that the theme is active.
     *
     * @example
     * ```php
     * $I->loginAsAdmin();
     * $I->amOnThemesPage();
     * $I->activateTheme('storefront');
     * $I->seeThemeActivated('storefront');
     * ```
     */
    public function seeThemeActivated($slug)
    {
        if (method_exists($this, 'waitForElement')) {
            $this->waitForElement("//div[contains(@class, 'theme')]");
            $this->seeElementInDOM("//div[@class='theme active'][.//*[@class='theme-name' and @id='$slug-name']]");

            return;
        }

        $this->seeElement("//div[@class='theme active'][.//*[@class='theme-name' and @id='$slug-name']]");
    }
}
