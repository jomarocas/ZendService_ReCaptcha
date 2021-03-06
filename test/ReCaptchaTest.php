<?php
/**
 * @see       https://github.com/zendframework/ZendService_ReCaptcha for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/ZendService_ReCaptcha/blob/master/LICENSE.md New BSD License
 */

namespace ZendServiceTest\ReCaptcha;

use PHPUnit\Framework\TestCase;
use Zend\Config;
use Zend\Http\Client as HttpClient;
use Zend\Http\Client\Adapter\Test;
use ZendService\ReCaptcha\ReCaptcha;
use ZendService\ReCaptcha\Response as ReCaptchaResponse;
use ZendService\ReCaptcha\Exception;
use Zend\Http\Client\Adapter\Curl;

class ReCaptchaTest extends TestCase
{
    /**
     * @var string
     */
    private $siteKey;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var ReCaptcha
     */
    private $reCaptcha;

    protected function setUp()
    {
        $this->siteKey = getenv('TESTS_ZEND_SERVICE_RECAPTCHA_SITE_KEY');
        $this->secretKey = getenv('TESTS_ZEND_SERVICE_RECAPTCHA_SECRET_KEY');

        if (empty($this->siteKey) || empty($this->siteKey)) {
            $this->markTestSkipped('ZendService\ReCaptcha\ReCaptcha tests skipped due to missing keys');
        }

        $httpClient = new HttpClient(
            null,
            [
                'adapter' => Curl::class,
            ]
        );

        $this->reCaptcha = new ReCaptcha(null, null, null, null, null, $httpClient);
    }

    public function testSetAndGet()
    {
        /* Set and get IP address */
        $ip = '127.0.0.1';
        $this->reCaptcha->setIp($ip);
        $this->assertSame($ip, $this->reCaptcha->getIp());

        /* Set and get site key */
        $this->reCaptcha->setSiteKey($this->siteKey);
        $this->assertSame($this->siteKey, $this->reCaptcha->getSiteKey());

        /* Set and get secret key */
        $this->reCaptcha->setSecretKey($this->secretKey);
        $this->assertSame($this->secretKey, $this->reCaptcha->getSecretKey());
    }

    public function testSingleParam()
    {
        $key = 'ssl';
        $value = true;

        $this->reCaptcha->setParam($key, $value);
        $this->assertSame($value, $this->reCaptcha->getParam($key));
    }

    public function testGetNonExistingParam()
    {
        $this->assertNull($this->reCaptcha->getParam('foobar'));
    }

    public function testMultipleParams()
    {
        $params = [
            'ssl' => true,
        ];

        $this->reCaptcha->setParams($params);
        $_params = $this->reCaptcha->getParams();

        $this->assertSame($params['ssl'], $_params['ssl']);
    }

    public function testSingleOption()
    {
        $key = 'theme';
        $value = 'dark';

        $this->reCaptcha->setOption($key, $value);
        $this->assertSame($value, $this->reCaptcha->getOption($key));
    }

    public function testGetNonExistingOption()
    {
        $this->assertNull($this->reCaptcha->getOption('foobar'));
    }

    public function testMultipleOptions()
    {
        $options = [
            'theme' => 'dark',
            'hl' => 'en',
        ];

        $this->reCaptcha->setOptions($options);
        $_options = $this->reCaptcha->getOptions();

        $this->assertSame($options['theme'], $_options['theme']);
        $this->assertSame($options['hl'], $_options['hl']);
    }

    public function testSetMultipleParamsFromZendConfig()
    {
        $params = [
            'ssl' => true,
        ];

        $config = new Config\Config($params);

        $this->reCaptcha->setParams($config);
        $_params = $this->reCaptcha->getParams();

        $this->assertSame($params['ssl'], $_params['ssl']);
    }

    public function testSetInvalidParams()
    {
        $this->expectException(Exception::class);
        $var = 'string';
        $this->reCaptcha->setParams($var);
    }

    public function testSetMultipleOptionsFromZendConfig()
    {
        $options = [
            'theme' => 'dark',
            'hl' => 'en',
        ];

        $config = new Config\Config($options);

        $this->reCaptcha->setOptions($config);
        $_options = $this->reCaptcha->getOptions();

        $this->assertSame($options['theme'], $_options['theme']);
        $this->assertSame($options['hl'], $_options['hl']);
    }

    public function testSetInvalidOptions()
    {
        $this->expectException(Exception::class);
        $var = 'string';
        $this->reCaptcha->setOptions($var);
    }

    public function testConstructor()
    {
        $params = [
            'noscript' => true,
        ];

        $options = [
            'theme' => 'dark',
            'hl' => 'en',
        ];

        $ip = '127.0.0.1';

        $reCaptcha = new ReCaptcha($this->siteKey, $this->secretKey, $params, $options, $ip);

        $_params = $reCaptcha->getParams();
        $_options = $reCaptcha->getOptions();

        $this->assertSame($this->siteKey, $reCaptcha->getSiteKey());
        $this->assertSame($this->secretKey, $reCaptcha->getSecretKey());
        $this->assertSame($params['noscript'], $_params['noscript']);
        $this->assertSame($options['theme'], $_options['theme']);
        $this->assertSame($options['hl'], $_options['hl']);
        $this->assertSame($ip, $reCaptcha->getIp());
    }

    public function testConstructorWithNoIp()
    {
        // Fake the _SERVER value
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $reCaptcha = new ReCaptcha(null, null, null, null, null);

        $this->assertSame($_SERVER['REMOTE_ADDR'], $reCaptcha->getIp());

        unset($_SERVER['REMOTE_ADDR']);
    }

    public function testGetHtmlWithNoPublicKey()
    {
        $this->expectException(Exception::class);

        $this->reCaptcha->getHtml();
    }

    public function testVerify()
    {
        $this->reCaptcha->setSiteKey($this->siteKey);
        $this->reCaptcha->setSecretKey($this->secretKey);
        $this->reCaptcha->setIp('127.0.0.1');

        $adapter = new Test();
        $client = new HttpClient(null, [
            'adapter' => $adapter
        ]);

        $this->reCaptcha->setHttpClient($client);

        $resp = $this->reCaptcha->verify('responseField');

        // See if we have a valid object and that the status is false
        $this->assertInstanceOf(ReCaptchaResponse::class, $resp);
        $this->assertFalse($resp->getStatus());
    }

    public function testGetHtml()
    {
        $this->reCaptcha->setSiteKey($this->siteKey);

        $html = $this->reCaptcha->getHtml();

        // See if the options for the captcha exist in the string
        $this->assertNotFalse(strstr($html, sprintf('data-sitekey="%s"', $this->siteKey)));

        // See if the js/iframe src is correct
        $this->assertNotTrue(strstr($html, '<iframe'));
    }

    public function testGetHtmlWithLanguage()
    {
        $this->reCaptcha->setSiteKey($this->siteKey);
        $this->reCaptcha->setOption('hl', 'en');

        $html = $this->reCaptcha->getHtml();

        $this->assertContains('?hl=en', $html);
    }

    /** @group ZF-10991 */
    public function testHtmlGenerationWithNoScriptElements()
    {
        $this->reCaptcha->setSiteKey($this->siteKey);
        $this->reCaptcha->setParam('noscript', true);
        $html = $this->reCaptcha->getHtml();
        $this->assertContains('<iframe', $html);
    }

    public function testVerifyWithMissingSecretKey()
    {
        $this->expectException(Exception::class);

        $this->reCaptcha->verify('response');
    }

    public function testVerifyWithMissingIp()
    {
        $this->expectException(Exception::class);

        $this->reCaptcha->setSecretKey($this->secretKey);
        $this->reCaptcha->verify('response');
    }
}
