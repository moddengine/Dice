<?php
/*@description        Dice - A minimal Dependency Injection Container for PHP
* @author             Tom Butler tom@r.je
* @copyright          2012-2015 Tom Butler <tom@r.je>
* @link               http://r.je/dice.html
* @license            http://www.opensource.org/licenses/bsd-license.php  BSD License
* @version            2.0
*/
class DiceTest extends PHPUnit_Framework_TestCase {
	protected $dice;

	public function __construct() {
		parent::__construct();
		spl_autoload_register(array($this, 'autoload'));
	}
	
	public function autoload($class) {
		//If Dice Triggers the autoloader the test fails
		//This generally means something invalid has been passed to 
		//a method such as is_subclass_of or dice is trying to construct
		//an object from something it shouldn't.
		$this->fail('Autoload triggered: ' . $class);
	}
	
	protected function setUp() {
		parent::setUp ();
		$this->dice = new \Dice\Dice();	
	}

	protected function tearDown() {
		$this->dice = null;		
		parent::tearDown ();
	}
		
	public function testNoConstructor() {
		$a = $this->dice->create('NoConstructor');		
		$this->assertInstanceOf('NoConstructor', $a);
	}

		
	public function testInternalClass() {
		$rule = [];
		$rule['constructParams'][] = '.';
		
		$this->dice->addRule('DirectoryIterator', $rule);
		
		$dir = $this->dice->create('DirectoryIterator');
		
		$this->assertInstanceOf('DirectoryIterator', $dir);
	}
	
	public function testInternalClassExtended() {
		$rule = [];
		$rule['constructParams'][] = '.';
	
		$this->dice->addRule('MyDirectoryIterator', $rule);
	
		$dir = $this->dice->create('MyDirectoryIterator');
	
		$this->assertInstanceOf('MyDirectoryIterator', $dir);
	}
	
	
	public function testInternalClassExtendedConstructor() {
		$rule = [];
		$rule['constructParams'][] = '.';
	
		$this->dice->addRule('MyDirectoryIterator2', $rule);
	
		$dir = $this->dice->create('MyDirectoryIterator2');
	
		$this->assertInstanceOf('MyDirectoryIterator2', $dir);
	}
	
	public function testNoMoreAssign() {
		$rule = [];
		$rule['substitutions']['Bar77'] = ['instance' => function() {
			return Baz77::create();
		}];
		
		$this->dice->addRule('Foo77', $rule);
		
		$foo = $this->dice->create('Foo77');
		
		$this->assertInstanceOf('Bar77', $foo->bar);
		$this->assertEquals('Z', $foo->bar->a);
	}

	public function testConsumeArgs() {
		$rule = [];
		$rule['constructParams'] = ['A'];		
		$this->dice->addRule('ConsumeArgsSub', $rule);
		$foo = $this->dice->create('ConsumeArgsTop',['B']);
		
		$this->assertEquals('A', $foo->a->s);
	}
	
	
	public function testAssignSharedNamed() {
		$rule = [];
		$rule['shared'] = true;
		$rule['instanceOf'] = function() {
			return Baz77::create();
		};
		$this->dice->addRule('$SharedBaz', $rule);
		
		//$rule2 
		
	}
	
	public function testPdo() {
		$pdo = $this->dice->create('mysqli');
	}
	
	public function testSetDefaultRule() {
		$defaultBehaviour = [];
		$defaultBehaviour['shared'] = true;
		$defaultBehaviour['newInstances'] = array('Foo', 'Bar');
		$this->dice->addRule('*', $defaultBehaviour);		

		$rule = $this->dice->getRule('*');
		foreach ($defaultBehaviour as $name => $value) {
			$this->assertEquals($rule[$name], $defaultBehaviour[$name]);
		}
	}

	public function testDefaultRuleWorks() {
		$defaultBehaviour = [];
		$defaultBehaviour['shared'] = true;
		
		$this->dice->addRule('*', $defaultBehaviour);
		
		$rule = $this->dice->getRule('A');
		
		$this->assertTrue($rule['shared']);
		
		$a1 = $this->dice->create('A');
		$a2 = $this->dice->create('A');
		
		$this->assertSame($a1, $a2);
	}
	
	
	public function testCreate() {
		$this->getMock('stdClass', array(), array(), 'TestCreate');
		$myobj = $this->dice->create('TestCreate');
		$this->assertInstanceOf('TestCreate', $myobj);
	}
	
	public function testCreateInvalid() {
		//"can't expect default exception". Not sure why.
		$this->setExpectedException('ErrorException');
		try {
			$this->dice->create('SomeClassThatDoesNotExist');
		}
		catch (Exception $e) {
			throw new ErrorException('Error occurred');
		}
	}
	

	/*
	 * Object graph creation cannot be tested with mocks because the constructor need to be tested.
	 * You can't set 'expects' on the objects which are created making them redundant for that as well
	 * Need real classes to test with unfortunately. 
	 */
	public function testObjectGraphCreation() {
		$a = $this->dice->create('A');
		$this->assertInstanceOf('B', $a->b);
		$this->assertInstanceOf('c', $a->b->c);
		$this->assertInstanceOf('D', $a->b->c->d);
		$this->assertInstanceOf('E', $a->b->c->e);
		$this->assertInstanceOf('F', $a->b->c->e->f);
	} 

	public function testNewInstances() {
		$rule = [];
		$rule['shared'] = true;
		$this->dice->addRule('B', $rule);
		
		$rule = [];
		$rule['newInstances'][] = 'B';
		$this->dice->addRule('A', $rule);
		
		$a1 = $this->dice->create('A');
		$a2 = $this->dice->create('A');
		
		$this->assertNotSame($a1->b, $a2->b);
	}
	
	public function testDefaultValueAssigned() {
		$obj = $this->dice->create('MethodWithDefaultValue');
		$this->assertEquals($obj->foo, 'bar');
	}
	
	public function testDefaultNullAssigned() {
		$rule = [];
		$rule['constructParams'] = [ ['instance' => 'A'], null];
		$this->dice->addRule('MethodWithDefaultNull', $rule);
		$obj = $this->dice->create('MethodWithDefaultNull');
		$this->assertNull($obj->b);
	}
	
	public function testNullSubstitution() {
		$rule = [];
		$rule['substitutions']['B'] = null;
		$this->dice->addRule('MethodWithDefaultNull', $rule);
		$obj = $this->dice->create('MethodWithDefaultNull');
		$this->assertNull($obj->b);
	}
	
	
	public function testSharedNamed() {
		$rule = [];
		$rule['shared'] = true;
		$rule['instanceOf'] = 'A';
		
		$this->dice->addRule('[A]', $rule);
		
		$a1 = $this->dice->create('[A]');
		$a2 = $this->dice->create('[A]');
		$this->assertSame($a1, $a2);
	}
	
	public function testForceNewInstance() {
		$rule = [];
		$rule['shared'] = true;
		$this->dice->addRule('A', $rule);
		
		$a1 = $this->dice->create('A');
		$a2 = $this->dice->create('A');
		
		$a3 = $this->dice->create('A', array(), true);
		
		$this->assertSame($a1, $a2);
		$this->assertNotSame($a1, $a3);
		$this->assertNotSame($a2, $a3);
	
	}
	
	
	public function testSharedRule() {
		$shared = [];
		$shared['shared'] = true;
	
		$this->dice->addRule('MyObj', $shared);
	
		$obj = $this->dice->create('MyObj');
		$this->assertInstanceOf('MyObj', $obj);
	
		$obj2 = $this->dice->create('MyObj');
		$this->assertInstanceOf('MyObj', $obj2);
	
		$this->assertSame($obj, $obj2);
	
	
		//This check isn't strictly needed but it's nice to have that safety measure!
		$obj->setFoo('bar');
		$this->assertEquals($obj->getFoo(), $obj2->getFoo());
		$this->assertEquals($obj->getFoo(), 'bar');
		$this->assertEquals($obj2->getFoo(), 'bar');
	}
	
	
	public function testSubstitutionText() {
		$rule = [];
		$rule['substitutions']['B'] = ['instance' => 'ExtendedB'];
		$this->dice->addRule('A', $rule);
		
		$a = $this->dice->create('A');
		
		$this->assertInstanceOf('ExtendedB', $a->b);
	}
	
	public function testSubstitutionTextMixedCase() {
		$rule = [];
		$rule['substitutions']['B'] = ['instance' => 'exTenDedb'];
		$this->dice->addRule('A', $rule);
	
		$a = $this->dice->create('A');
	
		$this->assertInstanceOf('ExtendedB', $a->b);
	}
	
	public function testSubstitutionCallback() {
		$rule = [];
		$injection = $this->dice;
		$rule['substitutions']['B'] = ['instance' => function() use ($injection) {
			return $injection->create('ExtendedB');
		}];
		
		$this->dice->addRule('A', $rule);
		
		$a = $this->dice->create('A');
		
		$this->assertInstanceOf('ExtendedB', $a->b);
	}
	
	public function testSubstitutionObject() {
		$rule = [];

		$rule['substitutions']['B'] = $this->dice->create('ExtendedB');
				
		$this->dice->addRule('A', $rule);
		
		$a = $this->dice->create('A');
		$this->assertInstanceOf('ExtendedB', $a->b);
	}
	
	public function testSubstitutionString() {
		$rule = [];
	
		$rule['substitutions']['B'] = ['instance' => 'ExtendedB'];
	
		$this->dice->addRule('A', $rule);
	
		$a = $this->dice->create('A');
		$this->assertInstanceOf('ExtendedB', $a->b);
	}
	
	
	public function testConstructParams() {
		$rule = [];
		$rule['constructParams'] = array('foo', 'bar');
		$this->dice->addRule('RequiresConstructorArgsA', $rule);
		
		$obj = $this->dice->create('RequiresConstructorArgsA');
		
		$this->assertEquals($obj->foo, 'foo');
		$this->assertEquals($obj->bar, 'bar');
	}

	public function testConstructParamsNested() {
		$rule = [];
		$rule['constructParams'] = array('foo', 'bar');
		$this->dice->addRule('RequiresConstructorArgsA', $rule);

		$rule = [];
		$rule['shareInstances'] = array('D');
		$this->dice->addRule('ParamRequiresArgs', $rule);
		
		$obj = $this->dice->create('ParamRequiresArgs');
		
		$this->assertEquals($obj->a->foo, 'foo');
		$this->assertEquals($obj->a->bar, 'bar');
	}

	
	public function testConstructParamsMixed() {
		$rule = [];
		$rule['constructParams'] = array('foo', 'bar');
		$this->dice->addRule('RequiresConstructorArgsB', $rule);
		
		$obj = $this->dice->create('RequiresConstructorArgsB');
		
		$this->assertEquals($obj->foo, 'foo');
		$this->assertEquals($obj->bar, 'bar');
		$this->assertInstanceOf('A', $obj->a);
	}
	
	public function testConstructArgs() {
		$obj = $this->dice->create('RequiresConstructorArgsA', array('foo', 'bar'));		
		$this->assertEquals($obj->foo, 'foo');
		$this->assertEquals($obj->bar, 'bar');
	}
		
	public function testConstructArgsMixed() {
		$obj = $this->dice->create('RequiresConstructorArgsB', array('foo', 'bar'));
		$this->assertEquals($obj->foo, 'foo');
		$this->assertEquals($obj->bar, 'bar');
		$this->assertInstanceOf('A', $obj->a);
	}
	
	public function testCreateArgs1() {
		$a = $this->dice->create('A', array($this->dice->create('ExtendedB')));
		$this->assertInstanceOf('ExtendedB', $a->b);
	}
	
	
	public function testCreateArgs2() {
		$a2 = $this->dice->create('A2', array($this->dice->create('ExtendedB'), 'Foo'));
		$this->assertInstanceOf('B', $a2->b);
		$this->assertInstanceOf('C', $a2->c);
		$this->assertEquals($a2->foo, 'Foo');
	}

	
	public function testCreateArgs3() {
		//reverse order args. It should be smart enough to handle this.
		$a2 = $this->dice->create('A2', array('Foo', $this->dice->create('ExtendedB')));
		$this->assertInstanceOf('B', $a2->b);
		$this->assertInstanceOf('C', $a2->c);
		$this->assertEquals($a2->foo, 'Foo');
	}
	
	public function testCreateArgs4() {
		$a2 = $this->dice->create('A3', array('Foo', $this->dice->create('ExtendedB')));
		$this->assertInstanceOf('B', $a2->b);
		$this->assertInstanceOf('C', $a2->c);
		$this->assertEquals($a2->foo, 'Foo');
	}
	
	public function testMultipleSharedInstancesByNameMixed() {
		$rule = [];
		$rule['shared'] = true;
		$rule['constructParams'][] = 'FirstY';
		
		$this->dice->addRule('Y', $rule);
		
		$rule = [];
		$rule['instanceOf'] = 'Y';
		$rule['shared'] = true;
		$rule['constructParams'][] = 'SecondY';
		
		$this->dice->addRule('[Y2]', $rule);
		
		$rule = [];
		$rule['constructParams'] = [ ['instance' => 'Y'], ['instance' => '[Y2]']];
		
		$this->dice->addRule('Z', $rule);
		
		$z = $this->dice->create('Z');
		$this->assertEquals($z->y1->name, 'FirstY');
		$this->assertEquals($z->y2->name, 'SecondY');		
	}
	
	public function testNonSharedComponentByNameA() {
		$rule = [];
		$rule['instanceOf'] = 'ExtendedB';
		$this->dice->addRule('$B', $rule);
		
		$rule = [];
		$rule['constructParams'][] = ['instance' => '$B'];
		$this->dice->addRule('A', $rule);
		
		$a = $this->dice->create('A');
		$this->assertInstanceOf('ExtendedB', $a->b);
	}
	
	public function testNonSharedComponentByName() {
		
		$rule = [];
		$rule['instanceOf'] = 'Y3';
		$rule['constructParams'][] = 'test';
		
		
		$this->dice->addRule('$Y2', $rule);
		
		
		$y2 = $this->dice->create('$Y2');
		//echo $y2->name;
		$this->assertInstanceOf('Y3', $y2);
		
		$rule = [];

		$rule['constructParams'][] = ['instance' => '$Y2'];
		$this->dice->addRule('Y1', $rule);
		
		$y1 = $this->dice->create('Y1');
		$this->assertInstanceOf('Y3', $y1->y2);
	}
	
	public function testSubstitutionByName() {
		$rule = [];
		$rule['instanceOf'] = 'ExtendedB';
		$this->dice->addRule('$B', $rule);
		
		$rule = [];
		$rule['substitutions']['B'] = ['instance' => '$B'];
				
		$this->dice->addRule('A', $rule);		
		$a = $this->dice->create('A');
		
		$this->assertInstanceOf('ExtendedB', $a->b);
	}
	
	public function testMultipleSubstitutions() {
		$rule = [];
		$rule['instanceOf'] = 'Y2';
		$rule['constructParams'][] = 'first';
		$this->dice->addRule('$Y2A', $rule);
		
		$rule = [];
		$rule['instanceOf'] = 'Y2';
		$rule['constructParams'][] = 'second';
		$this->dice->addRule('$Y2B', $rule);
		
		$rule = [];
		$rule['constructParams'] = array(['instance' => '$Y2A'], ['instance' => '$Y2B']);
		$this->dice->addRule('HasTwoSameDependencies', $rule);
		
		$twodep = $this->dice->create('HasTwoSameDependencies');
		
		$this->assertEquals('first', $twodep->y2a->name);
		$this->assertEquals('second', $twodep->y2b->name);
		
	}
	
	
	public function testCall() {
		$rule = [];
		$rule['call'][] = array('callMe', array());
		$this->dice->addRule('TestCall', $rule);
		$object = $this->dice->create('TestCall');
		$this->assertTrue($object->isCalled);
	}
	
	public function testCallWithParameters() {
		$rule = [];
		$rule['call'][] = array('callMe', array('one', 'two'));
		$this->dice->addRule('TestCall2', $rule);
		$object = $this->dice->create('TestCall2');
		$this->assertEquals('one', $object->foo);
		$this->assertEquals('two', $object->bar);
	}
	
	public function testCallWithInstance() {
		$rule = [];
		$rule['call'][] = array('callMe', array(['instance' => 'A']));
		$this->dice->addRule('TestCall3', $rule);
		$object = $this->dice->create('TestCall3');
		
		$this->assertInstanceOf('a', $object->a);
	
	}
	
	
	//
	public function testInterfaceRule() {
		$rule = [];

		$rule['shared'] = true;
		$this->dice->addRule('interfaceTest', $rule);
		
		$one = $this->dice->create('InterfaceTestClass');
		$two = $this->dice->create('InterfaceTestClass');
		
		
		$this->assertSame($one, $two);
		
		
		
	}
	
	
	public function testBestMatch() {
		$bestMatch = $this->dice->create('BestMatch', array('foo', $this->dice->create('A')));
		$this->assertEquals('foo', $bestMatch->string);
		$this->assertInstanceOf('A', $bestMatch->a);
	}
	

    
	
	public function testShareInstances() {
		$rule = [];
		$rule['shareInstances'] = ['Shared'];
		$this->dice->addRule('TestSharedInstancesTop', $rule);
		
		
		$shareTest = $this->dice->create('TestSharedInstancesTop');
		
		$this->assertinstanceOf('TestSharedInstancesTop', $shareTest);
		
		$this->assertInstanceOf('SharedInstanceTest1', $shareTest->share1);
		$this->assertInstanceOf('SharedInstanceTest2', $shareTest->share2);
		
		$this->assertSame($shareTest->share1->shared, $shareTest->share2->shared);
		$this->assertEquals($shareTest->share1->shared->uniq, $shareTest->share2->shared->uniq);
		
	}
	
	public function testNamedShareInstances() {

		$rule = [];
		$rule['instanceOf'] = 'Shared';
		$this->dice->addRule('$Shared', $rule);

		$rule = [];
		$rule['shareInstances'] = ['$Shared'];
		$this->dice->addRule('TestSharedInstancesTop', $rule);
		
		
		$shareTest = $this->dice->create('TestSharedInstancesTop');
		
		$this->assertinstanceOf('TestSharedInstancesTop', $shareTest);
		
		$this->assertInstanceOf('SharedInstanceTest1', $shareTest->share1);
		$this->assertInstanceOf('SharedInstanceTest2', $shareTest->share2);
		
		$this->assertSame($shareTest->share1->shared, $shareTest->share2->shared);
		$this->assertEquals($shareTest->share1->shared->uniq, $shareTest->share2->shared->uniq);


		$shareTest2 = $this->dice->create('TestSharedInstancesTop');
		$this->assertNotSame($shareTest2->share1->shared, $shareTest->share2->shared);
	}


	public function testShareInstancesNested() {
		$rule = [];
		$rule['shareInstances'] = ['F'];
		$this->dice->addRule('A4',$rule);
		$a = $this->dice->create('A4');
		$this->assertTrue($a->m1->f === $a->m2->e->f);
	}
	
	
	public function testShareInstancesMultiple() {
		$rule = [];
		$rule['shareInstances'] = ['Shared'];
		$this->dice->addRule('TestSharedInstancesTop', $rule);
	
	
		$shareTest = $this->dice->create('TestSharedInstancesTop');
	
		$this->assertinstanceOf('TestSharedInstancesTop', $shareTest);
	
		$this->assertInstanceOf('SharedInstanceTest1', $shareTest->share1);
		$this->assertInstanceOf('SharedInstanceTest2', $shareTest->share2);
	
		$this->assertSame($shareTest->share1->shared, $shareTest->share2->shared);
		$this->assertEquals($shareTest->share1->shared->uniq, $shareTest->share2->shared->uniq);
		
		
		$shareTest2 = $this->dice->create('TestSharedInstancesTop');
		$this->assertSame($shareTest2->share1->shared, $shareTest2->share2->shared);
		$this->assertEquals($shareTest2->share1->shared->uniq, $shareTest2->share2->shared->uniq);
		
		$this->assertNotSame($shareTest->share1->shared, $shareTest2->share2->shared);
		$this->assertNotEquals($shareTest->share1->shared->uniq, $shareTest2->share2->shared->uniq);
	
	}	
	
	public function testNamespaceBasic() {
		$a = $this->dice->create('Foo\\A');
		$this->assertInstanceOf('Foo\\A', $a);
	}
	
	
	public function testNamespaceWithSlash() {
		$a = $this->dice->create('\\Foo\\A');
		$this->assertInstanceOf('\\Foo\\A', $a);
	}
	
	public function testNamespaceWithSlashrule() {
		$rule = [];
		$rule['substitutions']['Foo\\A'] = ['instance' => 'Foo\\ExtendedA'];
		$this->dice->addRule('\\Foo\\B', $rule);
		
		$b = $this->dice->create('\\Foo\\B');
		$this->assertInstanceOf('Foo\\ExtendedA', $b->a);
	}
	
	public function testNamespaceWithSlashruleInstance() {
		$rule = [];
		$rule['substitutions']['Foo\\A'] = ['instance' => 'Foo\\ExtendedA'];
		$this->dice->addRule('\\Foo\\B', $rule);
	
		$b = $this->dice->create('\\Foo\\B');
		$this->assertInstanceOf('Foo\\ExtendedA', $b->a);
	}
	
	public function testNamespaceTypeHint() {
		$rule = [];
		$rule['shared'] = true;
		$this->dice->addRule('Bar\\A', $rule);
		
		$c = $this->dice->create('Foo\\C');
		$this->assertInstanceOf('Bar\\A', $c->a);
		
		$c2 = $this->dice->create('Foo\\C');
		$this->assertNotSame($c, $c2);
		
		//Check the rule has been correctly recognised for type hinted classes in a different namespace
		$this->assertSame($c2->a, $c->a);
	}
	
	public function testNamespaceInjection() {
		$b = $this->dice->create('Foo\\B');
		$this->assertInstanceOf('Foo\\B', $b);
		$this->assertInstanceOf('Foo\\A', $b->a);		
	}
		
	
	public function testNamespaceRuleSubstitution() {
		$rule = [];
		$rule['substitutions']['Foo\\A'] = ['instance' => 'Foo\\ExtendedA'];
		$this->dice->addRule('Foo\\B', $rule);
		
		$b = $this->dice->create('Foo\\B');
		$this->assertInstanceOf('Foo\\ExtendedA', $b->a);
	}
	
	public function testCyclicReferences() {
		$rule = [];
		$rule['shared'] = true;
		
		$this->dice->addRule('CyclicB', $rule);
		
		$a = $this->dice->create('CyclicA');
		
		$this->assertInstanceOf('CyclicB', $a->b);
		$this->assertInstanceOf('CyclicA', $a->b->a);
		
		$this->assertSame($a->b, $a->b->a->b);
	}

	public function testSharedClassWithTraitExtendsInternalClass()
	{
		$rule = [];
		$rule['shared'] = true;
		$rule['constructParams'] = ['.'];

		$this->dice->addRule('MyDirectoryIteratorWithTrait', $rule);

		$dir = $this->dice->create('MyDirectoryIteratorWithTrait');

		$this->assertInstanceOf('MyDirectoryIteratorWithTrait', $dir);
	}

	public function testConstructParamsPrecedence() {
		$rule = [];
		$rule['constructParams'] = ['A', 'B'];
		$this->dice->addRule('RequiresConstructorArgsA', $rule);

		$a1 = $this->dice->create('RequiresConstructorArgsA');
		$this->assertEquals('A', $a1->foo);
		$this->assertEquals('B', $a1->bar);

		$a2 = $this->dice->create('RequiresConstructorArgsA', ['C', 'D']);
		$this->assertEquals('C', $a2->foo);
		$this->assertEquals('D', $a2->bar);
	}


	public function testNullScalar() {
		$rule = [];
		$rule['constructParams'] = [null];
		$this->dice->addRule('NullScalar', $rule);

		$obj = $this->dice->create('NullScalar');
		$this->assertEquals(null, $obj->string);
	}

	public function testNullScalarNested() {
		$rule = [];
		$rule['constructParams'] = [null];
		$this->dice->addRule('NullScalar', $rule);

		$obj = $this->dice->create('NullScalarNested');
		$this->assertEquals(null, $obj->nullScalar->string);
	}


	public function testTwoDefaultNullClass() {
		$obj = $this->dice->create('MethodWithTwoDefaultNullC');
        $this->assertNull($obj->a);
		$this->assertInstanceOf('NB',$obj->b);
    }

    public function testTwoDefaultNullClassClass() {
		$obj = $this->dice->create('MethodWithTwoDefaultNullCC');
        $this->assertNull($obj->a);
		$this->assertInstanceOf('NB',$obj->b);
		$this->assertInstanceOf('NC',$obj->c);
    }

    public function testScalarConstructorArgs() {
    	$obj = $this->dice->create('ScalarConstructors', ['string', null]);
    	$this->assertEquals('string', $obj->string);
    	$this->assertEquals(null, $obj->null);
    }
}