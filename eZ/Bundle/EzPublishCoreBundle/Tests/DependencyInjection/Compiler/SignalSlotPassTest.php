<?php
/**
 * File containing the SignalSlotPassTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Bundle\EzPublishCoreBundle\Tests\DependencyInjection\Compiler;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\SignalSlotPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use PHPUnit_Framework_TestCase;
use Symfony\Component\DependencyInjection\Reference;

class SignalSlotPassTest extends PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $dispatcherDef = new Definition();
        $slotDef = new Definition();
        $signalIdentifier = 'FooSignal';
        $slotDef->addTag( 'ezpublish.api.slot', array( 'signal' => $signalIdentifier ) );

        $containerBuilder = new ContainerBuilder();
        $slotId = 'acme.foo_slot';
        $containerBuilder->addDefinitions(
            array(
                $slotId => $slotDef,
                'ezpublish.signalslot.signal_dispatcher' => $dispatcherDef
            )
        );

        $pass = new SignalSlotPass();
        $pass->process( $containerBuilder );
        $this->assertTrue( $dispatcherDef->hasMethodCall( 'attach' ) );
        $calls = $dispatcherDef->getMethodCalls();
        list( $method, $arguments ) = $calls[0];
        $this->assertSame( 'attach', $method );
        list( $signal, $serviceId ) = $arguments;
        $this->assertSame( $signalIdentifier, $signal );
        $this->assertEquals( $slotId, new Reference( $serviceId ) );
    }

    /**
     * @expectedException \LogicException
     */
    public function testProcessNoSignal()
    {
        $slotDef = new Definition();
        $slotDef->addTag( 'ezpublish.api.slot', array() );

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(
            array(
                'acme.foo_slot' => $slotDef,
                'ezpublish.signalslot.signal_dispatcher' => new Definition()
            )
        );

        $pass = new SignalSlotPass();
        $pass->process( $containerBuilder );
    }
}
