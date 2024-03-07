<?php

namespace WillPower232\CloverParser\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use WillPower232\CloverParser\CloverParser;

class CloverParserTest extends TestCase
{
    private function makeTempFileSafely(): string
    {
        $file = tmpfile();

        if ($file === false) {
            throw new \Exception('Unable to create temporary file');
        }

        $path = stream_get_meta_data($file)['uri'];

        return $path;
    }

    public function testNoFile(): void
    {
        $parser = new CloverParser();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Clover file is not present');

        $parser->addFile('/this/file/does/not/exist');
    }

    public function testNotXmlFile(): void
    {
        $parser = new CloverParser();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to process Clover file');
        $path = $this->makeTempFileSafely();

        file_put_contents($path, 'not xml');

        $parser->addFile($path);
    }

    public function testTotalsHappenFromPercentage(): void
    {
        $parser = new CloverParser();

        $this->assertFalse($parser->totalsCalculated());

        $parser->getPercentage();

        $this->assertTrue($parser->totalsCalculated());
    }

    public function testCalculationFromProject(): void
    {
        $parser = new CloverParser();

        $clover = <<<ENDCLOVER
        <?xml version="1.0" encoding="UTF-8"?>
        <coverage generated="1618905787">
            <project timestamp="1618905787">
                <metrics files="29" loc="1846" ncloc="1233" classes="20" methods="58"
                coveredmethods="50" conditionals="0" coveredconditionals="0" statements="414"
                coveredstatements="316" elements="472" coveredelements="366"/>
            </project>
        </coverage>
        ENDCLOVER;

        $path = $this->makeTempFileSafely();

        file_put_contents($path, $clover);

        $parser->addFile($path);

        $this->assertSame(77.54237288135593, $parser->getPercentage());
    }

    public function testCalculationFromFiles(): void
    {
        $parser = new CloverParser();

        $clover = <<<ENDCLOVER
        <?xml version="1.0" encoding="UTF-8"?>
        <coverage generated="1618905787">
            <project timestamp="1618905787">
                <package name="App\Providers">
                    <file name="/var/www/app/Providers/AuthServiceProvider.php">
                        <metrics loc="30" ncloc="18" classes="1" methods="1" coveredmethods="1"
                        conditionals="1" coveredconditionals="0" statements="2" coveredstatements="1"
                        elements="3" coveredelements="1"/>
                    </file>
                    <file name="/var/www/app/Providers/RouteServiceProvider.php">
                        <metrics loc="56" ncloc="28" classes="1" methods="3" coveredmethods="1"
                        conditionals="1" coveredconditionals="0" statements="8" coveredstatements="1"
                        elements="11" coveredelements="1"/>
                    </file>
                </package>
                <file name="/var/www/app/helpers.php">
                    <metrics loc="54" ncloc="23" classes="0" methods="0" coveredmethods="0"
                    conditionals="1" coveredconditionals="0" statements="4" coveredstatements="1"
                    elements="4" coveredelements="1"/>
                </file>
            </project>
        </coverage>
        ENDCLOVER;

        $path = $this->makeTempFileSafely();

        file_put_contents($path, $clover);

        $parser->addFile($path);

        $this->assertSame(23.809523809523807, $parser->getPercentage());
    }

    public function testCalculationAvoidsDivisionByZero(): void
    {
        $parser = new CloverParser();

        $clover = <<<ENDCLOVER
        <?xml version="1.0" encoding="UTF-8"?>
        <coverage generated="1618905787">
            <project timestamp="1618905787">
                <metrics files="29" loc="1846" ncloc="1233" classes="20" methods="0"
                coveredmethods="5" conditionals="0" coveredconditionals="0" statements="0"
                coveredstatements="3" elements="0" coveredelements="6"/>
            </project>
        </coverage>
        ENDCLOVER;

        $path = $this->makeTempFileSafely();

        file_put_contents($path, $clover);

        $parser->addFile($path);

        $this->assertSame(0.0, $parser->getPercentage());
    }
}
