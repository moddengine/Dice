<?php

class XmlLoaderTest extends DiceTest
{
    public function testLoadWithoutDice()
    {
        require_once "Loader/Xml.php";

        $rules = (new Dice\Loader\Xml())->load(
            simplexml_load_string("<dice />"),
            null,
            false
        );

        $this->assertSame([], $rules);
    }
}
