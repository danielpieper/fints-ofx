<?php declare(strict_types=1);

namespace FintsOfx;

use danielpieper\FintsOfx\Command\BaseCommand;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

class BaseCommandTest extends TestCase
{
    /** @var Generator */
    private $faker;

    /**
     * {@inheritdoc}
     */
//    protected function setUp(): void
//    {
//        $this->faker = Factory::create('de_DE');
//    }


    /**
     * @param $name
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    protected function getMethod($name)
    {
        $class = new \ReflectionClass('\danielpieper\FintsOfx\Command\BaseCommand');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetUserHomeFolderLinux(): void
    {
        $expected = '/test/path';
        putenv("HOME=$expected");

        $method = $this->getMethod('getUserHomeFolder');
        $command = new BaseCommand('test');
        $actual = $method->invoke($command);

        $this->assertSame($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetUserHomeFolderWindows(): void
    {
        $expectedHomePath = '\test\path';
        $expectedHomeDrive = 'c:';
        $expected = $expectedHomeDrive . $expectedHomePath;

        putenv("HOME=");
        $_SERVER['HOMEDRIVE'] = $expectedHomeDrive;
        $_SERVER['HOMEPATH'] = $expectedHomePath;

        $method = $this->getMethod('getUserHomeFolder');
        $command = new BaseCommand('test');
        $actual = $method->invoke($command);

        $this->assertSame($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetConfigurationFile(): void
    {
        $home = tempnam(sys_get_temp_dir(), 'fints-ofx');
        unlink($home);

        $expectedDir = $home . '/.config/fints-ofx';
        $expected = $expectedDir . '/config.yaml';

        mkdir($expectedDir, 0777, true);
        touch($expected);
        putenv("HOME=$home");

        $method = $this->getMethod('getConfigurationFile');
        $command = new BaseCommand('test');

        $actual = $method->invokeArgs($command, ['/does/not/exist']);
        $this->assertSame($expected, $actual);
        unlink($expected);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetConfigurationFileException(): void
    {
        putenv("HOME=/test/path");

        $method = $this->getMethod('getConfigurationFile');
        $command = new BaseCommand('test');

        $this->expectException(FileLocatorFileNotFoundException::class);
        $method->invokeArgs($command, ['/does/not/exist']);
    }
}
