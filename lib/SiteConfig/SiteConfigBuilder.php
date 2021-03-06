<?php
/**
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace BD\GuzzleSiteAuthenticator\SiteConfig;

interface SiteConfigBuilder
{
    /**
     * Builds the SiteConfig for a host.
     *
     * @param string $host The "www." prefix is ignored.
     *
     * @return SiteConfig
     *
     * @throws \OutOfRangeException If there is no config for $host
     */
    public function buildForHost($host);
}
