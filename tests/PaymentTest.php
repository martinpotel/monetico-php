<?php

use Carbon\Carbon;
use DansMaCulotte\Monetico\Exceptions\PaymentException;
use DansMaCulotte\Monetico\Payment\Payment;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    public function testPaymentConstruct()
    {
        $payment = new Payment(array(
            'reference' => 'ABCDEF123',
            'description' => 'PHPUnit',
            'language' => 'FR',
            'email' => 'john@english.fr',
            'amount' => 42.42,
            'currency' => 'EUR',
            'datetime' => Carbon::create(2019, 1, 1),
        ));

        $this->assertTrue($payment instanceof Payment);
    }

    public function testPaymentExceptionReference()
    {
        $this->expectExceptionObject(PaymentException::invalidReference('thisisabigerroryouknow'));

        new Payment(array(
            'reference' => 'thisisabigerroryouknow',
            'description' => 'PHPUnit',
            'language' => 'FR',
            'email' => 'john@english.fr',
            'amount' => 42.42,
            'currency' => 'EUR',
            'datetime' => Carbon::create(2019, 1, 1),
        ));
    }

    public function testPaymentExceptionLanguage()
    {
        $this->expectExceptionObject(PaymentException::invalidLanguage('WTF'));

        new Payment(array(
            'reference' => 'ABCDEF123',
            'description' => 'PHPUnit',
            'language' => 'WTF',
            'email' => 'john@english.fr',
            'amount' => 42.42,
            'currency' => 'EUR',
            'datetime' => Carbon::create(2019, 1, 1),
        ));
    }

    public function testPaymentExceptionDatetime()
    {
        $this->expectExceptionObject(PaymentException::invalidDatetime());

        new Payment(array(
            'reference' => 'ABCDEF123',
            'description' => 'PHPUnit',
            'language' => 'FR',
            'email' => 'john@english.fr',
            'amount' => 42.42,
            'currency' => 'EUR',
            'datetime' => '42',
        ));
    }

    public function testPaymentOptions()
    {
        $payment = new Payment(array(
            'reference' => 'ABCDEF123',
            'description' => 'PHPUnit',
            'language' => 'FR',
            'email' => 'john@english.fr',
            'amount' => 42.42,
            'currency' => 'EUR',
            'datetime' => Carbon::create(2019, 1, 1),
        ));

        $payment->setCardAlias('foobar');
        $this->assertArrayHasKey('aliascb', $payment->options);
        $this->assertTrue($payment->options['aliascb'] === 'foobar');

        $payment->setForceCard();
        $this->assertArrayHasKey('forcesaisiecb', $payment->options);
        $this->assertTrue($payment->options['forcesaisiecb'] === '1');

        $payment->setForceCard(false);
        $this->assertTrue($payment->options['forcesaisiecb'] === '0');

        $payment->setDisable3DS();
        $this->assertArrayHasKey('3dsdebrayable', $payment->options);
        $this->assertTrue($payment->options['3dsdebrayable'] === '1');

        $payment->setDisable3DS(false);
        $this->assertTrue($payment->options['3dsdebrayable'] === '0');

        $payment->setSignLabel('FooBar');
        $this->assertArrayHasKey('libelleMonetique', $payment->options);
        $this->assertTrue($payment->options['libelleMonetique'] === 'FooBar');

        $payment->setDisabledPaymentWays(array(
            '1euro',
            '3xcb',
            '4xcb',
            'fivory',
            'paypal'
        ));
        $this->assertArrayHasKey('desactivemoyenpaiement', $payment->options);
        $this->assertTrue($payment->options['desactivemoyenpaiement'] === '1euro,3xcb,4xcb,fivory,paypal');

        $payment->setDisabledPaymentWays(array(
            '1euro',
            '3xcb',
            '4xcb',
            'fivory',
            'foobar'
        ));
        $this->assertArrayHasKey('desactivemoyenpaiement', $payment->options);
        $this->assertTrue($payment->options['desactivemoyenpaiement'] === '1euro,3xcb,4xcb,fivory');
    }

    public function testPaymentCommitments()
    {
        $payment = new Payment(
            array(
            'reference' => 'ABCDEF123',
            'description' => 'PHPUnit',
            'language' => 'FR',
            'email' => 'john@english.fr',
            'amount' => 200,
            'currency' => 'EUR',
            'datetime' => Carbon::create(2019, 1, 1),
            ),
            array(
                array(
                    'date' => '06/01/2019',
                    'amount' => '50EUR',
                ),
                array(
                    'date' => '12/01/2019',
                    'amount' => '100EUR',
                ),
                array(
                    'date' => '24/01/2019',
                    'amount' => '20EUR',
                ),
                array(
                    'date' => '02/02/2019',
                    'amount' => '30EUR',
                ),
            )
        );

        $seal = $payment->generateSeal(
            'FOO',
            'BAR',
            '3.0',
            'FOOBAR'
        );

        $fields = $payment->generateFields(
            'FOO',
            'BAR',
            '3.0',
            'FOOBAR',
            'https://127.0.0.1',
            'https://127.0.0.1/success',
            'https://127.0.0.1/error'
        );

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('nbrech', $fields);
        $this->assertTrue($fields['nbrech'] === 4);

        $this->assertArrayHasKey('dateech1', $fields);
        $this->assertTrue($fields['dateech1'] === '06/01/2019');

        $this->assertArrayHasKey('montantech1', $fields);
        $this->assertTrue($fields['montantech1'] === '50EUR');

        $this->assertArrayHasKey('dateech2', $fields);
        $this->assertTrue($fields['dateech2'] === '12/01/2019');

        $this->assertArrayHasKey('montantech2', $fields);
        $this->assertTrue($fields['montantech2'] === '100EUR');

        $this->assertArrayHasKey('dateech3', $fields);
        $this->assertTrue($fields['dateech3'] === '24/01/2019');

        $this->assertArrayHasKey('montantech3', $fields);
        $this->assertTrue($fields['montantech3'] === '20EUR');

        $this->assertArrayHasKey('dateech4', $fields);
        $this->assertTrue($fields['dateech4'] === '02/02/2019');

        $this->assertArrayHasKey('montantech4', $fields);
        $this->assertTrue($fields['montantech4'] === '30EUR');
    }
}