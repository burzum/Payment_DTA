<?php
require_once '../DTA.php';
require_once 'PHPUnit/Framework.php';

class DTATest extends PHPUnit_Framework_TestCase
{
    protected $fixture;

    protected function setUp()
    {
        // Create the Array fixture.
        $this->fixture = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
            'name' => "Senders Name",
            'bank_code' => "16050000",
            'account_number' => "3503007767",
        );
        $this->fixture->setAccountFileSender($DTA_test_account);
    }

    public function testInstantiate()
    {
        $this->assertEquals("DTA", get_class($this->fixture));
    }

    public function testInstantiateShortBankCode()
    {
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "1605000",
             'account_number' => "3503007767",
         );

        $this->assertTrue($dtaus->setAccountFileSender($DTA_test_account));
    }

    public function testInstantiateLongBankCode()
    {
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "160500000",
             'account_number' => "3503007767",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTA_test_account));
    }

    public function testInstantiateLongAccountNumber()
    {
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050000",
             'account_number' => "35030077671",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTA_test_account));
    }

    public function testInstantiateLetterInAccountNumber()
    {
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050000",
             'account_number' => "3503007A67",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTA_test_account));
    }

    public function testCountEmpty()
    {
        if (!method_exists($this->fixture, 'count')) {
            $this->markTestSkipped('no count() in v1.2.0');
        } else {
            $this->assertEquals(0, $this->fixture->count());
        }
    }

    public function testCountNonEmpty()
    {
        if (!method_exists($this->fixture, 'count')) {
            $this->markTestSkipped('no count() in v1.2.0');
        } else {
            $this->fixture->addExchange(array(
                    'name' => "A Receivers Name",
                    'bank_code' => "16050000",
                    'account_number' => "3503007767"
                ),
                (float) 1234.56,
                "Test-Verwendungszweck"
            );
            $this->fixture->addExchange(array(
                    'name' => "A Receivers Name",
                    'bank_code' => "16050000",
                    'account_number' => "3503007767"),
                (float) 321.9,
                "Test-Verwendungszweck"
            );

            $this->assertEquals(2, $this->fixture->count());
        }
    }

    public function testAmountZero()
    {
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 0.0,
            "Ein Test-Verwendungszweck"
        );
        $this->assertEquals(0, $this->fixture->count());
    }

    public function testMaxAmount()
    {
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) PHP_INT_MAX/100,
            "Ein Test-Verwendungszweck"
        );
        $this->assertEquals(1, $this->fixture->count());
    }

    public function testAmountTooBig()
    {
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) (PHP_INT_MAX/100+1),
            "Ein Test-Verwendungszweck"
        );
        $this->assertEquals(0, $this->fixture->count());
    }

    public function testAmountSumNoOverflow()
    {
        if (PHP_INT_MAX != 2147483647) {
            $this->markTestSkipped('unexpected PHP_INT_MAX -- maybe a 64bit system?');
        } else {
            for($i = 0; $i < 10; $i++) {
                $this->fixture->addExchange(array(
                        'name' => "A Receivers Name",
                        'bank_code' => "16050000",
                        'account_number' => "3503007767"
                    ),
                    2147483.64,
                    "Ein Test-Verwendungszweck"
                );
            }
            $this->assertEquals(10, $this->fixture->count());
        }
    }

    public function testAmountSumOverflow()
    {
        if (PHP_INT_MAX != 2147483647) {
            $this->markTestSkipped('unexpected PHP_INT_MAX -- maybe a 64bit system?');
        } else {
            /* add enough transfers so that the sum of
             * amounts will cause an integer overflow */
            for($i = 0; $i < 10; $i++) {
                $this->fixture->addExchange(array(
                        'name' => "A Receivers Name",
                        'bank_code' => "16050000",
                        'account_number' => "3503007767"
                    ),
                    2147484, // = ceil(PHP_INT_MAX/100/10) and > PHP_INT_MAX/100/10
                    "Ein Test-Verwendungszweck"
                );
            }
            $this->assertEquals(9, $this->fixture->count());
        }
    }

    public function testValidStringTrue()
    {
        $result = $this->fixture->validString(" \$%&*+,-./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ����");
        $this->assertTrue($result);
    }

    public function testValidStringFalse()
    {
        $result = $this->fixture->validString("�");
        $this->assertFalse($result);
    }

    public function testMakeValidString()
    {
        $result = $this->fixture->makeValidString("� �~����");
        $this->assertEquals("AE AE OEUE SS", $result);
    }

    public function testMakeValidStringExtended()
    {
        if (!method_exists($this->fixture, 'count')) {
            $this->markTestSkipped('v1.2.0 had fewer char replacements');
        } else {
            $result = $this->fixture->makeValidString("� ������");
            $this->assertEquals("AE AEAOEOUESS", $result);
        }
    }

    public function testRejectLetterInAccountNumber()
    {
        $result = $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3A5030076B"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        );
        $this->assertFalse($result);
    }

    public function testUmlautInRecvName()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "� Receivers N�me",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(512, strlen($this->fixture->getFileContent()));
    }

    public function testAdditionalSenderName()
    {
        $DTA_test_account = array(
            'name' => "Senders Name",
            'additional_name' => "some very long additional sender name",
            'bank_code' => "16050000",
            'account_number' => "3503007767",
        );
        $this->assertTrue($this->fixture->setAccountFileSender($DTA_test_account));

        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767",
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(512, strlen($this->fixture->getFileContent()));
    }

    public function testAdditionalRecvName()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767",
                'additional_name' => "some very long additional receiver name"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(512, strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthRejectLongAccountNumber()
    {
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "35030077671"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        );
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "35030077671"),
            (float) 321.9,
            "Kurzer Test-Verwendungszweck"
        );

        $this->assertEquals(256, strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthLeadingZerosAccountNumber()
    {
    	// this covers Bug #14736
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "00000000003503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        );
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "000000000003503007767"),
            (float) 321.9,
            "Kurzer Test-Verwendungszweck"
        );

        $this->assertEquals(256, strlen($this->fixture->getFileContent()));
	}

    public function testCountLeadingZerosAccountNumber()
    {
    	// this covers Bug #14736
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "00000000003503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
		);
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "000000000003503007767"),
            (float) 321.9,
            "Kurzer Test-Verwendungszweck"
        );
        $this->assertEquals(0, $this->fixture->count());
    }

    /* following tests should check for correct file size, i.e. correct
     * number of C record parts, for different numbers of extensions */
    public function testFileLengthOneTransferNoExt()
    {
        // shortest format with one C record, no extensions
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(128*(1+2+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthTwoTransfersNoExt()
    {
        // two C records of two parts each
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"),
            (float) 321.9,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(128*(1+(2*2)+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferOneExt()
    {
        // shortest format with one C record, one extension
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(128*(1+2+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferTwoExt()
    {
        // shortest format with one C record, two extensions
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2")
        ));
        $this->assertEquals(128*(1+2+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferTwoExt2a()
    {
        // check if still valid without purpose extension
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => "Additional Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->assertTrue($this->fixture->setAccountFileSender($DTA_test_account));

        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1")
        ));
        $this->assertEquals(128*(1+2+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferTwoExt2b()
    {
        // check if still valid when giving purpose as string
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => "Additional Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->assertTrue($this->fixture->setAccountFileSender($DTA_test_account));

        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Verwendungszweck Zeile 1"
        ));
        $this->assertEquals(128*(1+2+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferThreeExt()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3")
        ));
        // C record needs three parts now
        $this->assertEquals(128*(1+3+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferSixExt()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6")
        ));
        $this->assertEquals(128*(1+3+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferSevenExt()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6",
                  "Verwendungszweck Zeile 7")
        ));
        // C record needs four parts now
        $this->assertEquals(128*(1+4+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferTenExt()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6",
                  "Verwendungszweck Zeile 7",
                  "Verwendungszweck Zeile 8",
                  "Verwendungszweck Zeile 9",
                  "Verwendungszweck Zeile 10")
        ));
        $this->assertEquals(128*(1+4+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferElevenExt()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6",
                  "Verwendungszweck Zeile 7",
                  "Verwendungszweck Zeile 8",
                  "Verwendungszweck Zeile 9",
                  "Verwendungszweck Zeile 10",
                  "Verwendungszweck Zeile 11")
        ));
        // C record needs five parts now
        $this->assertEquals(128*(1+5+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferFourteenExt()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6",
                  "Verwendungszweck Zeile 7",
                  "Verwendungszweck Zeile 8",
                  "Verwendungszweck Zeile 9",
                  "Verwendungszweck Zeile 10",
                  "Verwendungszweck Zeile 11",
                  "Verwendungszweck Zeile 12",
                  "Verwendungszweck Zeile 13",
                  "Verwendungszweck Zeile 14")
        ));
        $this->assertEquals(128*(1+5+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferFifteenExt()
    {
        // add all 15 possible extensions
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => "Additional Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->assertTrue($this->fixture->setAccountFileSender($DTA_test_account));

        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6",
                  "Verwendungszweck Zeile 7",
                  "Verwendungszweck Zeile 8",
                  "Verwendungszweck Zeile 9",
                  "Verwendungszweck Zeile 10",
                  "Verwendungszweck Zeile 11",
                  "Verwendungszweck Zeile 12",
                  "Verwendungszweck Zeile 13",
                  "Verwendungszweck Zeile 14")
        ));
        $this->assertEquals(128*(1+6+1), strlen($this->fixture->getFileContent()));
    }

    public function testPurposeLineLimit()
    {
        // too many purpose lines
        $this->assertFalse($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6",
                  "Verwendungszweck Zeile 7",
                  "Verwendungszweck Zeile 8",
                  "Verwendungszweck Zeile 9",
                  "Verwendungszweck Zeile 10",
                  "Verwendungszweck Zeile 11",
                  "Verwendungszweck Zeile 12",
                  "Verwendungszweck Zeile 13",
                  "Verwendungszweck Zeile 14",
                  "Verwendungszweck Zeile 15")
        ));
        $this->assertEquals(0, $this->fixture->count());
    }

    public function testSaveFileTrue()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"),
            (float) 321.9,
            "Kurzer Test-Verwendungszweck"
        ));

        $tmpfname = tempnam(sys_get_temp_dir(), "dtatest");
        if ($this->fixture->saveFile($tmpfname)) {
            $file_content = file_get_contents($tmpfname);
            unlink($tmpfname);
            $this->assertEquals(768, strlen($file_content));
        } else {
            $this->assertTrue(false);
        }
    }

    public function testSaveFileFalse()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));

        $tmpfname = "/root/nonexistantdirectory/dtatestfile";
        $this->assertFalse($this->fixture->saveFile($tmpfname));
    }

}