<?php


namespace Hydra\Tests\Decoder;

use Hydra\Decoder\JsonDecoder;

/**
 */
class JsonDecoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * data provider for jsondecode test
     */
    public function provider()   {
        return array(
            array('{"J":5,"0":"N","W":{"T":1,"F":5}}', array("J" => 5, "0" => "N", "W" => array("T" => 1, "F" => 5))),
        );
    }

    /**
     * @dataProvider provider
     */
    public function testDecode($data, $result) {
        $jsonDecoder = new JsonDecoder();

        $this->assertInstanceOf('\\Hydra\\Interfaces\\DecoderInterface', $jsonDecoder);

        $this->assertSame($jsonDecoder->decode($data), $result);
    }


}
