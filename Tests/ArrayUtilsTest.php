<?php

namespace ArturDoruch\ArrayUtil\Tests;

use ArturDoruch\ArrayUtil\ArrayUtils;
use PHPUnit\Framework\TestCase;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class ArrayUtilsTest extends TestCase
{
    private $array = [
        'request' => [
            'path' => '/products',
            'methods' => ['GET', 'HEAD'],
        ],
        'false' => false,
        'null' => null,
        '' => 'empty',
        'text' => 'Żyrafa',
        'products' => [
            [
                'name' => 'Product name',
                'total' => [
                    'items' => 20
                ],
                'available' => false,
            ],
        ],
    ];


    public function getIsIndexedTestData()
    {
        return [
            [[1, 4, 6], true],
            [['a', ['b', 'c' => ['d']]], true],
            [[], true],
            [[null], true],
            [[0, 'foo' => ['bar']], false],
            [[0, 2 => ['bar']], false],
        ];
    }

    /**
     * @dataProvider getIsIndexedTestData
     */
    public function testIsIndexed(array $array, bool $isIndexed)
    {
        self::assertSame($isIndexed, ArrayUtils::isIndexed($array));
    }


    public function testFind()
    {
        self::assertNull(ArrayUtils::find($this->array, ['null']));
        self::assertFalse(ArrayUtils::find($this->array, ['false']));
        self::assertEquals('empty', ArrayUtils::find($this->array, ['']));
        self::assertEquals('/products', ArrayUtils::find($this->array, ['request', 'path']));
        self::assertSame(false, ArrayUtils::find($this->array, ['products', 0, 'available']));

        self::assertCount(6, ArrayUtils::find($this->array, []));
        // Test with not exist key.
        self::assertNull(ArrayUtils::find($this->array, [0]));
        self::assertNull(ArrayUtils::find($this->array, ['request', 'foo']));
        // Test default.
        self::assertEquals('bar', ArrayUtils::find($this->array, ['request', 'foo'], 'bar'));

        self::assertNull(ArrayUtils::find([true => null], [1], 'abc'));
        self::assertNull(ArrayUtils::find([false => null], [0], 'abc'));
    }


    public function testKeyExists()
    {
        self::assertTrue(ArrayUtils::keyExists($this->array, ['null']));
        self::assertTrue(ArrayUtils::keyExists($this->array, ['false']));
        self::assertTrue(ArrayUtils::keyExists($this->array, ['']));
        self::assertTrue(ArrayUtils::keyExists($this->array, ['request', 'path']));
        self::assertTrue(ArrayUtils::keyExists($this->array, ['products', 0, 'available']));

        self::assertFalse(ArrayUtils::keyExists($this->array, [0]));
        self::assertFalse(ArrayUtils::keyExists($this->array, ['products', 'notExist']));

        $array = [
            null => 'null',
            false => 'false',
        ];

        self::assertTrue(ArrayUtils::keyExists($array, ['']));
        self::assertTrue(ArrayUtils::keyExists($array, [0]));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testKeyExistsWithEmptyArray()
    {
        ArrayUtils::keyExists($this->array, []);
    }


    public function testContains()
    {
        self::assertTrue(ArrayUtils::contains($this->array, null));
        self::assertTrue(ArrayUtils::contains($this->array, false));
        self::assertTrue(ArrayUtils::contains($this->array, 'empty'));
        self::assertTrue(ArrayUtils::contains($this->array, 'żyrafa'));
        self::assertFalse(ArrayUtils::contains($this->array, 'żyrafa', true));

        self::assertTrue(ArrayUtils::contains([
            'array' => $array = [0, 1]
        ], $array));

        $object = new \stdClass();
        $object->name = 'foo';
        self::assertTrue(ArrayUtils::contains([
            'object' => $object
        ], $object));

        self::assertTrue(ArrayUtils::contains(['0'], 0));
        self::assertTrue(ArrayUtils::contains([0], '0'));
        self::assertTrue(ArrayUtils::contains(['0.0'], 0.0));
        self::assertTrue(ArrayUtils::contains([0.0], '0.0'));

        self::assertFalse(ArrayUtils::contains(['0'], 0, true));
        self::assertFalse(ArrayUtils::contains([0], '0', true));
        self::assertFalse(ArrayUtils::contains(['0.0'], 0.0, true));
        self::assertFalse(ArrayUtils::contains([0.0], '0.0', true));

        self::assertFalse(ArrayUtils::contains([1, 2], null));
        self::assertFalse(ArrayUtils::contains([null], false));
        self::assertFalse(ArrayUtils::contains([0], false));
        self::assertFalse(ArrayUtils::contains([''], false));
        self::assertFalse(ArrayUtils::contains([], null));
        self::assertFalse(ArrayUtils::contains([false], null));
    }


    public function testToObject()
    {
        $array = $this->array;
        $array['Foo_bar'] = 'baz';
        $object = ArrayUtils::toObject($array);

        self::assertObjectHasAttribute('products', $object);
        self::assertEquals($this->array['products'], $object->products);
        self::assertObjectHasAttribute('fooBar', $object);
        self::assertEquals('baz', $object->fooBar);
        self::assertIsObject($object->request);

        $object = ArrayUtils::toObject($array, false);
        self::assertIsArray($object->request);

        $object = ArrayUtils::toObject($array, false, 'snake');
        self::assertObjectHasAttribute('foo_bar', $object);
        $object = ArrayUtils::toObject($array, false, null);
        self::assertObjectHasAttribute('Foo_bar', $object);
    }


    public function testFlatten()
    {
        $array = [
            'zero', 'one', 'two',
            'request' => [
                'path' => '/products',
                'methods' => ['GET', 'HEAD'],
            ],
            'null' => null,
            'products' => [
                [
                    'total' => [
                        'items' => 20
                    ],
                ],
            ],
        ];

        $flatArray = ArrayUtils::flatten($array);

        self::assertCount(8, $flatArray);
        self::assertArrayHasKey(7, $flatArray);
        self::assertEquals('zero', $flatArray[0]);
        self::assertEquals(20, $flatArray[7]);

        $flatArray = ArrayUtils::flatten($array, true);

        self::assertCount(6, $flatArray);
        self::assertEquals('GET', $flatArray[0]);
        self::assertEquals('two', $flatArray[2]);
        self::assertArrayHasKey('path', $flatArray);
        self::assertEquals(20, $flatArray['items']);
    }


    public function testConcatStrings()
    {
        $strings = ArrayUtils::concatStrings('-', [0, 1, 2], [0, 'a', 'b']);
        self::assertEquals(['0-0', '1-a', '2-b'], $strings);

        $strings = ArrayUtils::concatStrings(['-', '/', 'separator not used'], [1, 2], ['A', new Stringable('B')], ['11', null]);
        self::assertEquals(['1-A/11', '2-B/'], $strings);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /Missing \$arrays with values to concatenate/
     */
    public function testConcatStringsMissingArraysArguments()
    {
        ArrayUtils::concatStrings('-', [1, 2, 3, 4]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Missing 2 value separators of 3 expected.
     */
    public function testConcatStringsMissingSeparatorValues()
    {
        ArrayUtils::concatStrings([''], [1, 2], ['a', 'b'], ['x', 'y'], [3, 4]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Missing index #1 in array #2.
     */
    public function testConcatStringsMissingArrayIndex()
    {
        ArrayUtils::concatStrings('', [1, 2], ['a']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /^Invalid type "boolean" of value of index #1 in array #2/
     */
    public function testConcatStringsInvalidArrayValueType()
    {
        ArrayUtils::concatStrings('', [1, 2], ['a', false]);
    }


    public function testMergeDistinct()
    {
        $array1 = [
            'path' => '/prods',
            'methods' => ['GET', 'HEAD'],
            'headers' => [
                'x-1' => 'a',
                'x-2' => 'b'
            ],
        ];
        $array2 = [
            'path' => '/products',
            'methods' => ['POST'],
            'headers' => [
                'x-2' => null,
                'x-3' => 'c',
            ],
            'fragment' => '#name'
        ];

        $merged = ArrayUtils::mergeDistinct($array1, $array2);

        self::assertCount(4, $merged);
        self::assertEquals('/products', $merged['path']);
        self::assertEquals(['POST', 'HEAD'], $merged['methods']);
        self::assertCount(3, $merged['headers']);
        self::assertArrayHasKey('x-1', $merged['headers']);
        self::assertArrayHasKey('x-3', $merged['headers']);
        self::assertNull($merged['headers']['x-2']);

        $mergedIndexedArrays = ArrayUtils::mergeDistinct($array1, $array2, true);
        self::assertEquals(['GET', 'HEAD', 'POST'], $mergedIndexedArrays['methods']);
        self::assertCount(3, $mergedIndexedArrays['headers']);
    }


    public function testEquals()
    {
        self::assertFalse(ArrayUtils::equals($array1 = ['text' => 'abc'], $array2 = ['text' => 'cdf']));

        // Test strings with case sensitivity.
        self::assertFalse(ArrayUtils::equals($array1 = ['text' => 'Żyrafa'], $array2 = ['text' => 'żyrafa']));
        self::assertTrue(ArrayUtils::equals($array1, $array2, false));

        $array1 = [
            'methods' => ['GET', 'HEAD'],
        ];
        $array2 = [
            'methods' => ['HEAD', 'GET'],
        ];

        // Test indexed array order.
        self::assertTrue(ArrayUtils::equals($array1, $array2));

        // Test indexed array order and string case sensitivity.
        $array2['methods'] = ['head', 'get'];
        self::assertFalse(ArrayUtils::equals($array1, $array2));
        self::assertTrue(ArrayUtils::equals($array1, $array2, false));

        // Test numeric comparison
        $array1 = [
            'float' => 0.345,
            'int' => 23,
            'zero' => 0,
            'stringInt' => '123',
        ];
        $array2 = [
            'float' => '0.345',
            'int' => '23',
            'zero' => '0',
            'stringInt' => 123,
        ];
        self::assertFalse(ArrayUtils::equals($array1, $array2));
        self::assertTrue(ArrayUtils::equals($array1, $array2, false));

        // Test comparing false and 0
        self::assertFalse(ArrayUtils::equals($array1 = [false], $array2 = [0]));
        self::assertTrue(ArrayUtils::equals($array1, $array2, false));
        // Test comparing true and 1
        self::assertFalse(ArrayUtils::equals($array1 = [true], $array2 = [1]));
        self::assertTrue(ArrayUtils::equals($array1, $array2, false));
        // Test comparing null and empty value.
        self::assertFalse(ArrayUtils::equals($array1 = [null], $array2 = ['']));
        self::assertTrue(ArrayUtils::equals($array1, $array2, false));
        // Test comparing null and false.
        self::assertFalse(ArrayUtils::equals($array1 = [null], $array2 = [false]));
        self::assertTrue(ArrayUtils::equals($array1, $array2, false));

        // Test comparing objects
        $object = new \stdClass();
        $object2 = new \stdClass();

        self::assertFalse(ArrayUtils::equals([$object], [$object2]));
        self::assertTrue(ArrayUtils::equals([$object], [$object2], false));
        $object2->property = 'value';
        self::assertFalse(ArrayUtils::equals([$object], [$object2], false));
    }


    public function testKeySort()
    {
        $array = [
            'x' => 1,
            'a' => [
                'c' => true,
                'b' => true,
                'B' => true,
                1 => true,
            ]
        ];

        ArrayUtils::keySort($array);

        self::assertEquals(['a', 'x'], array_keys($array));
        self::assertEquals([1, 'B', 'b', 'c'], array_keys($array['a']));
    }


    public function testInsert()
    {
        $array = [1, 2, 3, 5];
        ArrayUtils::insert($array, [4], 3);
        self::assertEquals([1, 2, 3, 4, 5], $array);

        $array = [1, 2, 3, 5];
        ArrayUtils::insert($array, [4], 6);
        self::assertEquals([1, 2, 3, 5, 4], $array);

        $array = [1, 2, 3];
        ArrayUtils::insert($array, [4], -2);
        self::assertSame([1, 4, 2, 3], $array);

        $array = [1, 2];
        ArrayUtils::insert($array, [], 1);
        self::assertSame([1, 2], $array);

        $array = [];
        ArrayUtils::insert($array, $insert = [null, false, new \stdClass(), []], 0);
        self::assertSame($insert, $array);
    }
}
