<?php

use Exception;
use GuzzleHttp\ClientInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class AuthenticatorProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var ClientInterface
     */
    private $guzzle;

    public function __construct(ClientInterface $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    public function getFunctions()
    {
        return [
            $this->getRequestFunction(),
            $this->getXpathFunction(),
        ];
    }

    private function getRequestFunction()
    {
        return new ExpressionFunction(
            'request_html',
            function () {
                throw new Exception("Not supported");
            },
            function (array $arguments, $uri) {
                return $this->guzzle->get($uri)->getBody();
            }
        );
    }

    private function getXpathFunction()
    {
        return new ExpressionFunction(
            'xpath',
            function () {
                throw new Exception("Not supported");
            },
            function (array $arguments, $xpathQuery, $html) {
                $doc = new \DOMDocument;
                @$doc->loadHTML($html, LIBXML_NOCDATA | LIBXML_NOWARNING | LIBXML_NOERROR);

                $xpath = new \DOMXPath($doc);
                $domNodeList = $xpath->query($xpathQuery);
                $domNode = $domNodeList->item(0);

                return $domNode->attributes->getNamedItem('value')->nodeValue;
            }
        );
    }
}
