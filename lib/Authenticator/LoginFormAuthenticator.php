<?php

namespace BD\GuzzleSiteAuthenticator\Authenticator;

use BD\GuzzleSiteAuthenticator\SiteConfig\SiteConfig;
use BD\GuzzleSiteAuthenticator\ExpressionLanguage\AuthenticatorProvider;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class LoginFormAuthenticator implements Authenticator
{
    /** @var \GuzzleHttp\Client */
    protected $guzzle;

    /** @var \BD\GuzzleSiteAuthenticator\SiteConfig\SiteConfig */
    private $siteConfig;

    public function __construct(SiteConfig $siteConfig)
    {
        // @todo OptionResolver
        $this->siteConfig = $siteConfig;
    }

    public function login(ClientInterface $guzzle)
    {
        $postFields = [
            $this->siteConfig->getUsernameField() => $this->siteConfig->getUsername(),
            $this->siteConfig->getPasswordField() => $this->siteConfig->getPassword(),
        ] + $this->getExtraFields($guzzle);

        $guzzle->post(
            $this->siteConfig->getLoginUri(),
            ['body' => $postFields, 'allow_redirects' => true, 'verify' => false]
        );

        return $this;
    }

    public function isLoggedIn(ClientInterface $guzzle)
    {
        if (($cookieJar = $guzzle->getDefaultOption('cookies')) instanceof CookieJar) {
            /** @var \GuzzleHttp\Cookie\SetCookie $cookie */
            foreach ($cookieJar as $cookie) {
                // check required cookies
                if ($cookie->getDomain() == $this->siteConfig->getHost()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks from the HTML of a page if authentication is requested by a grabbed page.
     *
     * @param string $html
     *
     * @return bool
     */
    public function isLoginRequired($html)
    {
        $useInternalErrors = libxml_use_internal_errors(true);

        // need to check for the login dom element ($options['not_logged_in_xpath']) in the HTML
        $doc = new \DOMDocument();
        $doc->loadHTML($html);

        $xpath = new \DOMXPath($doc);
        $result = ($xpath->evaluate($this->siteConfig->getNotLoggedInXpath())->length > 0);

        libxml_use_internal_errors($useInternalErrors);

        return $result;
    }

    /**
     * Returns extra fields from the configuration.
     * Evaluates any field value that is an expression language string.
     *
     * @param ClientInterface $guzzle
     *
     * @return array
     */
    private function getExtraFields(ClientInterface $guzzle)
    {
        $extraFields = [];

        foreach ($this->siteConfig->getExtraFields() as $extraField) {
            list($fieldName, $fieldValue) = explode('=', $extraField, 2);
            if (substr($fieldValue, 0, 2) === '@=') {
                $expressionLanguage = $this->getExpressionLanguage($guzzle);
                $fieldValue = $expressionLanguage->evaluate(
                    substr($fieldValue, 2),
                    [
                        'config' => $this->siteConfig
                    ]
                );
            }
            $extraFields[$fieldName] = $fieldValue;
        }

        return $extraFields;
    }

    /**
     * @return ExpressionLanguage
     */
    private function getExpressionLanguage(ClientInterface $guzzle)
    {
        return new ExpressionLanguage(
            null,
            [new AuthenticatorProvider($guzzle)]
        );
    }
}
