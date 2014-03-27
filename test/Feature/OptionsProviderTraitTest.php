<?php

namespace Spiffy\Package;
use Spiffy\Package\TestAsset\Options\Package;

/**
 * @coversDefaultClass \Spiffy\Package\Feature\OptionsProviderTrait
 */
class OptionsProviderTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::setOptions, ::getOptions
     */
    public function testSetGetOptions()
    {
        $options = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'foobarbaz'
        ];

        $trait = new Package();
        $trait->setOptions($options);

        $this->assertSame($options, $trait->getOptions());
        $this->assertSame('bar', $trait->getOption('foo'));
        $this->assertSame('baz', $trait->getOption('bar'));
        $this->assertSame('foobarbaz', $trait->getOption('baz'));
    }

    /**
     * @covers ::getOption, \Spiffy\Package\Feature\Exception\MissingOptionException::__construct
     * @expectedException \Spiffy\Package\Feature\Exception\MissingOptionException
     * @expectedExceptionMessage Option with key "foo" does not exist
     */
    public function testGetOptionThrowsExceptionForUnkonwnValue()
    {
        $trait = new Package();
        $trait->getOption('foo');
    }
}
