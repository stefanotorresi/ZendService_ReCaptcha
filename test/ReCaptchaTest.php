<?php
/**
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendServiceTest\ReCaptcha;

use PHPUnit_Framework_TestCase as TestCase;
use ZendService\ReCaptcha\ReCaptcha;
use ZendService\ReCaptcha\Response as ReCaptchaResponse;
use Zend\Config;

class ReCaptchaTest extends TestCase
{
    /**
     * @var ReCaptcha
     */
    protected $reCaptcha = null;

    public function setUp()
    {
        $this->publicKey = getenv('TESTS_ZEND_SERVICE_RECAPTCHA_PUBLIC_KEY');
        $this->privateKey = getenv('TESTS_ZEND_SERVICE_RECAPTCHA_PRIVATE_KEY');

        if (empty($this->publicKey)
            || $this->publicKey == 'public key'
            || empty($this->privateKey)
            || $this->privateKey == 'private key'
        ) {
            $this->markTestSkipped('ZendService\ReCaptcha\ReCaptcha tests skipped due to missing keys');
        }

        $this->reCaptcha = new ReCaptcha();
    }

    public function testSetAndGet()
    {
        /* Set and get IP address */
        $ip = '127.0.0.1';
        $this->reCaptcha->setIp($ip);
        $this->assertSame($ip, $this->reCaptcha->getIp());

        /* Set and get public key */
        $this->reCaptcha->setPublicKey($this->publicKey);
        $this->assertSame($this->publicKey, $this->reCaptcha->getPublicKey());

        /* Set and get private key */
        $this->reCaptcha->setPrivateKey($this->privateKey);
        $this->assertSame($this->privateKey, $this->reCaptcha->getPrivateKey());
    }

    public function testSingleParam()
    {
        $key = 'ssl';
        $value = true;

        $this->reCaptcha->setParam($key, $value);
        $this->assertSame($value, $this->reCaptcha->getParam($key));
    }

    public function tetsGetNonExistingParam()
    {
        $this->assertNull($this->reCaptcha->getParam('foobar'));
    }

    public function testMultipleParams()
    {
        $params = [
            'ssl' => true,
            'error' => 'errorMsg',
            'xhtml' => true,
        ];

        $this->reCaptcha->setParams($params);
        $_params = $this->reCaptcha->getParams();

        $this->assertSame($params['ssl'], $_params['ssl']);
        $this->assertSame($params['error'], $_params['error']);
        $this->assertSame($params['xhtml'], $_params['xhtml']);
    }

    public function testSingleOption()
    {
        $key = 'theme';
        $value = 'black';

        $this->reCaptcha->setOption($key, $value);
        $this->assertSame($value, $this->reCaptcha->getOption($key));
    }

    public function tetsGetNonExistingOption()
    {
        $this->assertNull($this->reCaptcha->getOption('foobar'));
    }

    public function testMultipleOptions()
    {
        $options = [
            'theme' => 'black',
            'lang' => 'no',
        ];

        $this->reCaptcha->setOptions($options);
        $_options = $this->reCaptcha->getOptions();

        $this->assertSame($options['theme'], $_options['theme']);
        $this->assertSame($options['lang'], $_options['lang']);
    }

    public function testSetMultipleParamsFromZendConfig()
    {
        $params = [
            'ssl' => true,
            'error' => 'errorMsg',
            'xhtml' => true,
        ];

        $config = new Config\Config($params);

        $this->reCaptcha->setParams($config);
        $_params = $this->reCaptcha->getParams();

        $this->assertSame($params['ssl'], $_params['ssl']);
        $this->assertSame($params['error'], $_params['error']);
        $this->assertSame($params['xhtml'], $_params['xhtml']);
    }

    public function testSetInvalidParams()
    {
        $this->setExpectedException('ZendService\\ReCaptcha\\Exception');
        $var = 'string';
        $this->reCaptcha->setParams($var);
    }

    public function testSetMultipleOptionsFromZendConfig()
    {
        $options = [
            'theme' => 'black',
            'lang' => 'no',
        ];

        $config = new Config\Config($options);

        $this->reCaptcha->setOptions($config);
        $_options = $this->reCaptcha->getOptions();

        $this->assertSame($options['theme'], $_options['theme']);
        $this->assertSame($options['lang'], $_options['lang']);
    }

    public function testSetInvalidOptions()
    {
        $this->setExpectedException('ZendService\\ReCaptcha\\Exception');
        $var = 'string';
        $this->reCaptcha->setOptions($var);
    }

    public function testConstructor()
    {
        $params = [
            'ssl' => true,
            'error' => 'errorMsg',
            'xhtml' => true,
        ];

        $options = [
            'theme' => 'black',
            'lang' => 'no',
        ];

        $ip = '127.0.0.1';

        $reCaptcha = new ReCaptcha($this->publicKey, $this->privateKey, $params, $options, $ip);

        $_params = $reCaptcha->getParams();
        $_options = $reCaptcha->getOptions();

        $this->assertSame($this->publicKey, $reCaptcha->getPublicKey());
        $this->assertSame($this->privateKey, $reCaptcha->getPrivateKey());
        $this->assertSame($params['ssl'], $_params['ssl']);
        $this->assertSame($params['error'], $_params['error']);
        $this->assertSame($params['xhtml'], $_params['xhtml']);
        $this->assertSame($options['theme'], $_options['theme']);
        $this->assertSame($options['lang'], $_options['lang']);
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
        $this->setExpectedException('ZendService\\ReCaptcha\\Exception');

        $html = $this->reCaptcha->getHtml();
    }

    public function testVerify()
    {
        $this->reCaptcha->setPublicKey($this->publicKey);
        $this->reCaptcha->setPrivateKey($this->privateKey);
        $this->reCaptcha->setIp('127.0.0.1');

        $adapter = new \Zend\Http\Client\Adapter\Test();
        $client = new \Zend\Http\Client(null, [
            'adapter' => $adapter
        ]);

        $this->reCaptcha->setHttpClient($client);

        $resp = $this->reCaptcha->verify('challengeField', 'responseField');

        // See if we have a valid object and that the status is false
        $this->assertTrue($resp instanceof ReCaptchaResponse);
        $this->assertFalse($resp->getStatus());
    }

    public function testGetHtml()
    {
        $this->reCaptcha->setPublicKey($this->publicKey);
        $errorMsg = 'errorMsg';
        $this->reCaptcha->setParam('ssl', true);
        $this->reCaptcha->setParam('xhtml', true);
        $this->reCaptcha->setParam('error', $errorMsg);

        $html = $this->reCaptcha->getHtml();

        // See if the options for the captcha exist in the string
        $this->assertNotSame(false, strstr($html, 'var RecaptchaOptions = {"theme":"red","lang":"en"};'));

        // See if the js/iframe src is correct
        $this->assertNotSame(
            false,
            strstr(
                $html,
                sprintf(
                    'src="%s/challenge?k=%s&error=%s"',
                    ReCaptcha::API_SECURE_SERVER,
                    $this->publicKey,
                    $errorMsg
                )
            )
        );
    }

    /** @group ZF-10991 */
    public function testHtmlGenerationWillUseSuppliedNameForNoScriptElements()
    {
        $this->reCaptcha->setPublicKey($this->publicKey);
        $html = $this->reCaptcha->getHtml('contact');
        $this->assertContains('contact[recaptcha_challenge_field]', $html);
        $this->assertContains('contact[recaptcha_response_field]', $html);
    }

    public function testVerifyWithMissingPrivateKey()
    {
        $this->setExpectedException('ZendService\\ReCaptcha\\Exception');

        $this->reCaptcha->verify('challenge', 'response');
    }

    public function testVerifyWithMissingIp()
    {
        $this->setExpectedException('ZendService\\ReCaptcha\\Exception');

        $this->reCaptcha->setPrivateKey($this->privateKey);
        $this->reCaptcha->verify('challenge', 'response');
    }

    public function testVerifyWithMissingChallengeField()
    {
        $this->reCaptcha->setPrivateKey($this->privateKey);
        $this->reCaptcha->setIp('127.0.0.1');
        $response = $this->reCaptcha->verify('', 'response');
        $this->assertFalse($response->getStatus());
    }

    public function testVerifyWithMissingResponseField()
    {
        $this->reCaptcha->setPrivateKey($this->privateKey);
        $this->reCaptcha->setIp('127.0.0.1');
        $response = $this->reCaptcha->verify('challenge', '');
        $this->assertFalse($response->getStatus());
    }
}