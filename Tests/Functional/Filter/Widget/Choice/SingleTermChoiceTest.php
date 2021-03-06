<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\FilterManagerBundle\Tests\Functional\Filter\Widget\Choice;

use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\FilterManagerBundle\Filter\ViewData;
use ONGR\FilterManagerBundle\Filter\ViewData\ChoicesAwareViewData;
use ONGR\FilterManagerBundle\Filter\Widget\Choice\SingleTermChoice;
use ONGR\FilterManagerBundle\Search\FilterContainer;
use ONGR\FilterManagerBundle\Search\FilterManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class SingleTermChoiceTest extends AbstractElasticsearchTestCase
{
    /**
     * @return array
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'product' => [
                    [
                        '_id' => 1,
                        'color' => 'red',
                        'manufacturer' => 'a',
                        'title' => 'm1',
                    ],
                    [
                        '_id' => 2,
                        'color' => 'blue',
                        'manufacturer' => 'a',
                        'title' => 'm2',
                    ],
                    [
                        '_id' => 3,
                        'color' => 'red',
                        'manufacturer' => 'b',
                        'title' => 'm3',
                    ],
                    [
                        '_id' => 4,
                        'color' => 'blue',
                        'manufacturer' => 'b',
                        'title' => 'm4',
                    ],
                    [
                        '_id' => 5,
                        'color' => 'green',
                        'manufacturer' => 'b',
                        'title' => 'm5',
                    ],
                    [
                        '_id' => 6,
                        'color' => 'blue',
                        'manufacturer' => 'a',
                        'title' => 'm6',
                    ],
                    [
                        '_id' => 7,
                        'color' => 'yellow',
                        'manufacturer' => 'a',
                        'title' => 'm7',
                    ],
                    [
                        '_id' => 8,
                        'color' => 'red',
                        'manufacturer' => 'a',
                        'title' => 'm8',
                    ],
                    [
                        '_id' => 9,
                        'color' => 'blue',
                        'manufacturer' => 'a',
                        'title' => 'm9',
                    ],
                    [
                        '_id' => 10,
                        'color' => 'red',
                        'manufacturer' => 'a',
                        'title' => 'm10',
                    ],
                    [
                        '_id' => 11,
                        'color' => 'blue',
                        'manufacturer' => 'a',
                        'title' => 'm11',
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if choices are sorted as expected using configuration settings.
     */
    public function testChoicesConfiguration()
    {
        /** @var ChoicesAwareViewData $result */
        $result = $this->getContainer()->get('ongr_filter_manager.foo_filters')
            ->handleRequest(new Request())->getFilters()['single_choice'];

        $expectedChoices = [
            'red',
            'blue',
            'green',
        ];

        $actualChoices = [];

        foreach ($result->getChoices() as $choice) {
            $actualChoices[] = $choice->getLabel();
        }

        $this->assertEquals($expectedChoices, $actualChoices);
    }

    /**
     * Data provider for testChoicesSort().
     *
     * @return array
     */
    public function getChoicesSortData()
    {
        $out = [];

        // Case #0, sorted in ascending order by term, nothing is prioritized.
        $sortParams = ['type' => '_term', 'order' => 'asc', 'priorities' => []];
        $out[] = ['sortParams' => $sortParams, ['blue', 'green', 'red', 'yellow']];

        // Case #1, sorted in descending order by term, blue is prioritized.
        $sortParams = ['type' => '_term', 'order' => 'desc', 'priorities' => ['blue']];
        $out[] = ['sortParams' => $sortParams, ['blue', 'yellow', 'red', 'green']];

        // Case #2, all items prioritized, so sorting shouldn't matter.
        $sortParams = ['type' => '_term', 'order' => 'desc', 'priorities' => ['blue', 'green', 'red']];
        $out[] = ['sortParams' => $sortParams, ['blue', 'green', 'red', 'yellow']];

        // Case #3, sort items by count, red prioritized.
        $sortParams = ['type' => '_count', 'order' => 'desc', 'priorities' => ['red']];
        $out[] = ['sortParams' => $sortParams, ['red', 'blue', 'green', 'yellow']];

        // Case #3, sort items by count.
        $sortParams = ['type' => '_count', 'order' => 'asc', 'priorities' => []];
        $out[] = ['sortParams' => $sortParams, ['green', 'yellow', 'red', 'blue']];

        return $out;
    }

    /**
     * Check if choices are sorted as expected.
     *
     * @param array $sortParams
     * @param array $expectedChoices
     *
     * @dataProvider getChoicesSortData()
     */
    public function testChoicesSort($sortParams, $expectedChoices)
    {
        $container = new FilterContainer();

        $filter = new SingleTermChoice();
        $filter->setRequestField('choice');
        $filter->setTags(['tagged']);
        $filter->setField('color');
        $filter->setSortType($sortParams);

        $container->set('choice', $filter);

        $manager = new FilterManager(
            $container,
            $this->getManager()->getRepository('TestBundle:Product'),
            new EventDispatcher()
        );

        /** @var ChoicesAwareViewData $result */
        $result = $manager->handleRequest(new Request())->getFilters()['choice'];

        $actualChoices = [];

        foreach ($result->getChoices() as $choice) {
            $actualChoices[] = $choice->getLabel();
        }

        $this->assertFalse($result->hasTag('badged'));
        $this->assertEquals($expectedChoices, $actualChoices);
    }

    /**
     * Check if fetches more choices than ES default.
     */
    public function testChoicesSize()
    {
        $container = new FilterContainer();

        $filter = new SingleTermChoice();
        $filter->setRequestField('choice');
        $filter->setField('title');

        $container->set('choice', $filter);

        $manager = new FilterManager(
            $container,
            $this->getManager()->getRepository('TestBundle:Product'),
            new EventDispatcher()
        );

        /** @var ChoicesAwareViewData $result */
        $result = $manager->handleRequest(new Request())->getFilters()['choice'];

        $this->assertEquals(11, count($result->getChoices()));
    }
}
