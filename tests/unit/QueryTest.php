<?php
namespace JClaveau\ElasticSearch;

use JClaveau\VisibilityViolator\VisibilityViolator;


/**
 */
class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function test_where_equal()
    {

        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query->where('field', '=', 'value');
        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');
        $this->assertEquals('value', $filters[0]['term']['field']);
    }

    /**
     */
    public function test_where_in()
    {
        // simple
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', 'IN', 'value');
        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals(['value'], $filters[0]['terms']['field']);

        // multiple
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', 'IN', ['value1', 'value2']);
        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals(['value1', 'value2'], $filters[0]['terms']['field']);
    }

    /**
     */
    public function test_addFilterLevel()
    {
        $query  = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        VisibilityViolator::callHiddenMethod($query, 'addFilterLevel', ['osef', function($query) {
            //
        }], true);

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([
            ['osef' => []],
        ], $filters);
    }

    /**
     */
    public function test_orWhere()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query->should(function ($query) {
            $query->where('field', '=', 'value');
            $query->where('field2', '=', 'value2');
        });

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([[
            'or' => [
                [
                    'term' => [
                        'field' => 'value',
                    ]
                ],
                [
                    'term' => [
                        'field2' => 'value2',
                    ]
                ]
            ]
        ]], $filters);
    }

    /**
     */
    public function test_where_not_in()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', 'NOT IN', 'value');

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([[
            'bool' => [
                'must_not' => [
                    [
                        'terms' => [
                            'field' => ['value'],
                        ]
                    ],
                ],
            ],
        ]], $filters);
    }

    /**
     */
    public function test_where_not_equal()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', '!=', 'value');

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([[
            'bool' => [
                'must_not' => [
                    [
                        'term' => [
                            'field' => 'value',
                        ]
                    ],
                ],
            ],
        ]], $filters);
    }

    /**
     */
    public function test_where_greater_than()
    {
        // simple
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', '>', [1, 3, 8, 12, 42]);

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([
            [
                'range' => [
                    'field' => [
                        'gt' => 42,
                    ],
                ]
            ]
        ], $filters);
    }

    /**
     */
    public function test_where_greater_or_equal_than()
    {
        // simple
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', '>=', [1, 3, 8, 12, 42]);

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([
            [
                'range' => [
                    'field' => [
                        'gte' => 42,
                    ],
                ]
            ]
        ], $filters);
    }

    /**
     */
    public function test_where_lower_than()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', '<', [1, 3, 8, 12, 42]);
        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([
            [
                'range' => [
                    'field' => [
                        'lt' => 1
                    ],
                ]
            ]
        ], $filters);
    }

    /**
     */
    public function test_where_lower_tor_equal_than()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', '<=', [1, 3, 8, 12, 42]);
        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([
            [
                'range' => [
                    'field' => [
                        'lte' => 1,
                    ],
                ]
            ]
        ], $filters);
    }

    /**
     */
    public function test_getSearchParams()
    {
        // simple
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $filters = VisibilityViolator::setHiddenProperty($query, 'filters', [
            [
                'range' => [
                    'field' => [
                        'lt' => 'value'
                    ],
                ],
            ]
        ]);

        $params = $query->getSearchParams();
        $must   = $params['body']['query']['constant_score']['filter']['bool']['must'];

        // print_r($params);

        $this->assertEquals([
            [
                'range' => [
                    'field' => [
                        'lt' => 'value'
                    ],
                ]
            ]
        ], $must);

        // with a group of filters
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $filters = VisibilityViolator::setHiddenProperty($query, 'filters', [
            'or' => [
                [
                    'term' => [
                        'field' => 'value',
                    ]
                ],
                [
                    'term' => [
                        'field2' => 'value2',
                    ]
                ]
            ]
        ]);

        $params = $query->getSearchParams();
        $must   = $params['body']['query']['constant_score']['filter']['bool']['must'];

        // print_r($must);
        $this->assertEquals([
            'or' => [
                [
                    'term' => [
                        'field' => 'value',
                    ]
                ],
                [
                    'term' => [
                        'field2' => 'value2',
                    ]
                ]
            ]
        ], $must);

        // without aggregations
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $filters = VisibilityViolator::setHiddenProperty($query, 'filters', []);

        $params = $query->getSearchParams();
        $must   = isset($params['body']['aggregations']);

        $this->assertEquals(false, $must, "Aggregation must not be setted");
    }

    /**
     */
    public function test_should_notIn_full()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query->should(function ($query) {
            $query->where('field', '=', 'value');
            $query->where('field2', 'NOT IN', 'value2');
        });

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        // print_r($filters);

        $this->assertEquals([[
            'or' => [
                [
                    'term' => [
                        'field' => 'value',
                    ]
                ],
                [
                    'bool' => [
                        'must_not' => [
                            [
                                'terms' => [
                                    'field2' => ['value2'],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]], $filters);

    }

    /**
     */
    public function test_openOr_closeOr_notIn_full()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query->should(function ($query) {
            $query->where('field', '=', 'value');
            $query->where('field2', 'NOT IN', 'value2');
        });

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        // print_r($filters);

        $this->assertEquals([[
            'or' => [
                [
                    'term' => [
                        'field' => 'value',
                    ]
                ],
                [
                    'bool' => [
                        'must_not' => [
                            [
                                'terms' => [
                                    'field2' => ['value2'],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]], $filters);

    }

    /**
     */
    public function test_and_inside_or()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query->should(function ($query) {
            $query->must(function ($query) {
                $query->where('field', '=', 'value');
                $query->where('field2', 'NOT IN', 'value2');
            });
            $query->must(function ($query) {
                $query->where('field3', '=', 'value3');
                $query->where('field2', 'NOT IN', 'something else');
            });
        });

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        // print_r($filters);

        $this->assertEquals([
            [
                'or' => [
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'field' => 'value',
                                    ]
                                ],
                                [
                                    'bool' => [
                                        'must_not' => [
                                            [
                                                'terms' => [
                                                    'field2' => ['value2'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'field3' => 'value3',
                                    ]
                                ],
                                [
                                    'bool' => [
                                        'must_not' => [
                                            [
                                                'terms' => [
                                                    'field2' => ['something else'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $filters);
    }

    /**
     */
    public function test_addOperationAggregation()
    {
        $query = new ElasticSearchQuery;

        $query->addOperationAggregation( ElasticSearchQuery::SUM, ['field' => 'field_to_sum']);

        $es_query = $query->getSearchParams();

        $this->assertEquals([
            'sum' => ['field' => 'field_to_sum']
        ], $es_query['body']['aggregations']['calculation_sum_field_to_sum']);
    }

    /**
     */
    public function test_addOperationAggregation_after_group_bys()
    {
        $query = new ElasticSearchQuery;

        $query
            ->groupBy('field_1')
            ->groupBy('field_2')
            ->addOperationAggregation( ElasticSearchQuery::SUM,     ['field' => 'field_to_sum'])
            ->addOperationAggregation( ElasticSearchQuery::AVERAGE, ['field' => 'field_to_avg'])
            ;

        $es_query = $query->getSearchParams();
        $operations = $es_query['body']
            ['aggregations']['group_by_field_1']
            ['aggregations']['group_by_field_2']
            ['aggregations']
            ;


        $this->assertEquals([
            'sum' => ['field' => 'field_to_sum']
        ], $operations['calculation_sum_field_to_sum']);

        $this->assertEquals([
            'avg' => ['field' => 'field_to_avg']
        ], $operations['calculation_avg_field_to_avg']);
    }

    /**
     */
    public function test_group_by_nested_fields()
    {
        $query = new ElasticSearchQuery;

        $query
            ->setNestedFields(['nest'])
            ->groupBy('field_1')
            ->groupBy('nest.field_1')
            ->groupBy('nest.field_2')
            ->groupBy('field_2')
            // ->addOperationAggregation( ElasticSearchQuery::SUM,     ['field' => 'field_to_sum'])
            // ->addOperationAggregation( ElasticSearchQuery::AVERAGE, ['field' => 'field_to_avg'])
            ;

        $es_query = $query->getSearchParams();
        // print_r($es_query);
        // exit;

        $this->assertEquals(
            [
                'terms' => [
                    'field'   => 'nest.field_2',
                    'size'    => 0,
                    'missing' => -1,
                ]
            ],
            $es_query['body']
            ['aggregations']['group_by_field_1']
            ['aggregations']['group_by_field_2']
            ['aggregations']['nested_nest']
            ['aggregations']['group_by_nest.field_1']
            ['aggregations']['group_by_nest.field_2']
        );
    }

    /**
     */
    public function test_group_by_nested_fields_filtered() {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query
            ->setNestedFields(['nest'])
            ->where('nest.field', 'exists')
            ->groupBy('nest.field')
            ->groupBy('field_1')
            ;

        $es_query = $query->getSearchParams();

        $this->assertEquals(
            [
                'terms' => [
                    'field'   => 'nest.field',
                    'size'    => 0,
                    'missing' => -1,
                ]
            ],
            $es_query['body']
            ['aggregations']['group_by_field_1']
            ['aggregations']['nested_nest']
            ['aggregations']['group_by_nest.field']
        );
    }

    /**
     */
    public function test_fieldRenamer()
    {
        $query = new ElasticSearchQuery;

        $query
            ->setFieldRenamer(function($field_name) {
                if ($field_name == 'field_to_rename')
                    return 'renamed_field';

                return $field_name;
            })
            ->addOperationAggregation( ElasticSearchQuery::SUM, ['field' => 'field_to_rename'], false)
            ->addOperationAggregation( ElasticSearchQuery::SUM, ['field' => 'field_with_good_name'], false)
            ->groupBy('field_to_groupon')
            ->addOperationAggregation( ElasticSearchQuery::AVERAGE, ['field' => 'field_for_avg'])
            ->addOperationAggregation( ElasticSearchQuery::HISTOGRAM, [
                'field' => 'field-for-histogram',
                'interval' => 2,
            ])
            ;


        $es_query = $query->getSearchParams();
        // print_r($es_query);

        $this->assertEquals([
            'sum' => ['field' => 'renamed_field']
        ], $es_query['body']['aggregations']['calculation_sum_field_to_rename']);

        $this->assertEquals([
            'sum' => ['field' => 'field_with_good_name']
        ], $es_query['body']['aggregations']['calculation_sum_field_with_good_name']);

        $this->assertEquals([
            'avg' => ['field' => 'field_for_avg']
        ], $es_query['body']['aggregations']['group_by_field_to_groupon']['aggregations']['calculation_avg_field_for_avg']);

        $this->assertEquals([
            'histogram' => [
                'field'         => 'field-for-histogram',
                'interval'      => 2,
                // 'min_doc_count' => 1,
            ],
        ], $es_query['body']['aggregations']['group_by_field_to_groupon']['aggregations']['histogram_field-for-histogram_2']);

        $this->assertEquals([
              'renamed_field'        => 'field_to_rename',
              'field_with_good_name' => 'field_with_good_name',
              'field_to_groupon'     => 'field_to_groupon',
              'field_for_avg'        => 'field_for_avg',
              'field-for-histogram'  => 'field-for-histogram',
            ],
            VisibilityViolator::getHiddenProperty($query, 'renamed_fields')
        );
    }

    /**
     */
    public function test_addOperationAggregation_all_types()
    {
        $query = new ElasticSearchQuery;

        $query
            ->addOperationAggregation( ElasticSearchQuery::SUM, ['field' => 'field'], false)
            ->groupBy('field_to_groupon')
            ->addOperationAggregation( ElasticSearchQuery::AVERAGE, ['field' => 'field_for_avg'])
            ->addOperationAggregation( ElasticSearchQuery::HISTOGRAM, [
                'field' => 'field-for-histogram',
                'interval' => 2,
            ])
            ->addOperationAggregation( ElasticSearchQuery::SCRIPT, [
                'field'  => 'field-filled-by-script',
                'script' => 'script to fill the field',
            ])
            ;


        $es_query = $query->getSearchParams();
        // var_export($es_query);

        $this->assertEquals([
            'sum' => ['field' => 'field']
        ], $es_query['body']['aggregations']['calculation_sum_field']);

        $this->assertEquals([
            'avg' => ['field' => 'field_for_avg']
        ], $es_query['body']['aggregations']['group_by_field_to_groupon']['aggregations']['calculation_avg_field_for_avg']);

        $this->assertEquals([
            'histogram' => [
                'field'         => 'field-for-histogram',
                'interval'      => 2,
                // 'min_doc_count' => 1,
            ],
        ], $es_query['body']['aggregations']['group_by_field_to_groupon']['aggregations']['histogram_field-for-histogram_2']);

        $this->assertEquals([
                'terms' => [
                    'field'  => 'field-filled-by-script',
                    'script' => 'script to fill the field',
                    'size'   => 0,
                ],
            ],
            $es_query['body']['aggregations']['group_by_field_to_groupon']['aggregations']['script_field-filled-by-script']
        );
    }

    /**
     */
    public function test_addOperationAggregation_after_grouping_on_nested_field()
    {
        $query = new ElasticSearchQuery;

        $query
            ->setNestedFields(['nested_field'])
            ->groupBy('nested_field')
            ->addOperationAggregation( ElasticSearchQuery::AVERAGE, ['field' => 'field_for_avg'])
            ;


        $es_query = $query->getSearchParams();
        // var_export($es_query);

        // normal leaf average operation
        $this->assertEquals([
                'avg' => ['field' => 'field_for_avg']
            ],
            $es_query['body']
            ['aggregations']['nested_nested_field']
            ['aggregations']['group_by_nested_field']
            ['aggregations']['calculation_avg_field_for_avg']
        );

        // same level as nested average operation in case of empty nested field
        $this->assertEquals([
                'avg' => ['field' => 'field_for_avg']
            ],
            $es_query['body']
            ['aggregations']['calculation_avg_field_for_avg']
        );
    }

    /**
     */
    public function test_addOperationAggregation_filters()
    {
        $query = new ElasticSearchQuery;

        $query->addOperationAggregation(
            ElasticSearchQuery::FILTERS,
            [
                'filters' => [
                    "errors"   => ["match" => ["body" => "error"]],
                    "warnings" => ["match" => ["body" => "warning"]],
                ],
                'other_bucket'     => true,
                'other_bucket_key' => 'lalala',
            ]
        );

        $es_query = $query->getSearchParams();

        // normal leaf average operation
        $this->assertEquals(
            [
                'filters_6d3c4d81321c3c4dc7ba7ac394494c8d' => [
                    'filters' => [
                        'filters' => [
                            'errors' => [
                                'match' => [
                                    'body' => 'error',
                                ],
                            ],
                            'warnings' => [
                                'match' => [
                                    'body' => 'warning',
                                ],
                            ],
                        ],
                        'other_bucket' => true,
                        'other_bucket_key' => 'lalala',
                    ],
                ],
            ],
            $es_query['body']['aggregations']
        );
    }

    /**
     */
    public function test_addOperationAggregation_count()
    {
        /* ALL THGE INDEX */
        $query = new ElasticSearchQuery;
        $query->addOperationAggregation( ElasticSearchQuery::COUNT );
        $es_query = $query->getSearchParams();
        $this->assertEquals(false, isset($es_query['body']['aggregations']));

        /* ElasticSearchResult::COUNT used as an alias of no field specified */
        $query = new ElasticSearchQuery;
        $query->addOperationAggregation( ElasticSearchQuery::COUNT, ['field' => ElasticSearchResult::COUNT]);
        $es_query = $query->getSearchParams();
        $this->assertEquals(false, isset($es_query['body']['aggregations']));

        /* NON-NESTED */
        $query = new ElasticSearchQuery;
        $query->setNestedFields(['my_nested_field']);
        try {
            $query->addOperationAggregation(
                ElasticSearchQuery::COUNT,
                ['field' => 'my_non-nested_field']
            );
            $this->assertTrue(false, "An exception should have been thrown here");
        }
        catch (\InvalidArgumentException $e) {
            $this->assertEquals(
                "COUNT operation is only applicable to nested fields or the whole index instead of 'my_non-nested_field'. You are maybe looking for CARDINALITY aggregation",
                $e->getMessage()
            );
        }

        /* COUNT NESTED ENTRY ITSELF */
        $query = new ElasticSearchQuery;
        $query->setNestedFields(['my_nested_field']);

        $query->addOperationAggregation(
            ElasticSearchQuery::COUNT,
            ['field' => 'my_nested_field']
        );

        $es_query = $query->getSearchParams();

        $this->assertEquals(
            [
                'count_nested_my_nested_field' => [
                    'nested' => [
                        'path' => 'my_nested_field',
                    ],
                ],
            ],
            $es_query['body']['aggregations']
        );

        /* COUNT NESTED SUB-ENTRY */
        $query = new ElasticSearchQuery;
        $query->setNestedFields(['my_nested_field']);
        try {
            $query->addOperationAggregation(
                ElasticSearchQuery::COUNT,
                [ 'field' => 'my_nested_field.sub_entry']
            );
            $this->assertTrue(false, "An exception should have been thrown here");
        }
        catch (\InvalidArgumentException $e) {
            $this->assertEquals(
                "COUNT operation is only applicable to nested fields or the whole index instead of 'my_nested_field.sub_entry'. You are maybe looking for CARDINALITY aggregation",
                $e->getMessage()
            );
        }

        /* COUNT NESTED IN GROUP_BY NON-NESTED FIELD AGGREGATION */
        $query = new ElasticSearchQuery;
        $query
            ->setNestedFields(['my_nested_field'])
            ->groupBy('my_other_nonnested_field')
            ->addOperationAggregation(
                ElasticSearchQuery::COUNT,
                [ 'field' => 'my_nested_field']
            );

        $es_query = $query->getSearchParams();

        $this->assertEquals(
            [
                'group_by_my_other_nonnested_field' => [
                    'terms' => [
                        'field' => 'my_other_nonnested_field',
                        'size' => 0,
                        'missing' => ElasticSearchQuery::MISSING_AGGREGATION_FIELD,
                    ],
                    'aggregations' => [
                        'count_nested_my_nested_field' => [
                            'nested' => [
                                'path' => 'my_nested_field',
                            ],
                        ],
                    ],
                ],
            ],
            $es_query['body']['aggregations']
        );

        /* COUNT NESTED IN GROUP_BY NON-NESTED FIELD AGGREGATION */
        $query = new ElasticSearchQuery;
        $query
            ->setNestedFields(['my_nested_field'])
            ->groupBy('my_nested_field.subentry')
            ->addOperationAggregation(
                ElasticSearchQuery::COUNT,
                [ 'field' => 'my_nested_field']
            )
            ;

        $es_query = $query->getSearchParams();

        $this->assertEquals(
            [
                'nested_my_nested_field' => [
                    'aggregations' => [
                        'group_by_my_nested_field.subentry' => [
                            'terms' => [
                                'field' => 'my_nested_field.subentry',
                                'size' => 0,
                                'missing' => ElasticSearchQuery::MISSING_AGGREGATION_FIELD,
                            ],
                            // no nested aggregation added as it's already done by rthe group by
                        ],
                    ],
                    'nested' => [
                        'path' => 'my_nested_field',
                    ],
                ],
            ],
            $es_query['body']['aggregations']
        );

        /**/
    }

    /**
     */
    public function test_json_encode()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', '<', 'value');

        $json = \json_encode($query);

        $this->assertEquals(
            '{"index":null,"ignore_unavailable":true,"body":{"query":{"constant_score":{"filter":{"bool":{"must":[{"range":{"field":{"lt":"value"}}}]}}}},"size":0}}'
            , $json
        );
    }

    /**
     */
    public function test_setIndex()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->setIndex( 'my_index' );
        $this->assertEquals(
            'my_index'
            , $query->getSearchParams()['index']
        );

        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->setIndex( ['my_index', 'my_index2'] );
        $this->assertEquals(
            'my_index,my_index2'
            , $query->getSearchParams()['index']
        );
    }

    /**
     */
    public function test_wrapFilterIfNested() {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query
            ->setNestedFields(['nest'])
            ->where('nest.field', 'exists')
            ;

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([
            'nested' => [
                'path'  => 'nest',
                'query' => [
                    'filtered'  => [
                        'filter'    => [
                            'bool'  => [
                                'must'  => [
                                    [
                                        'exists'  => [
                                            'field' => 'nest.field'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ], $filters[0]
        );
    }

    /**
     */
    public function test_getTimeout()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->setTimeout( '1h' );
        $this->assertEquals(
            '1h'
            , $query->getSearchParams()['body']['timeout']
        );

        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->setTimeout( '30s' );
        $this->assertEquals(
            '30s'
            , $query->getSearchParams()['body']['timeout']
        );
    }

    /**
     */
    public function test_supportedOperationTypes()
    {
        $this->assertEquals(
            [
                // scalar results
                ElasticSearchQuery::COUNT,
                ElasticSearchQuery::AVERAGE,
                ElasticSearchQuery::MAX,
                ElasticSearchQuery::MIN,
                ElasticSearchQuery::SUM,
                ElasticSearchQuery::HISTOGRAM,
                // ElasticSearchQuery::VALUE_COUNT,

                ElasticSearchQuery::CARDINALITY,
                ElasticSearchQuery::PERCENTILES,
                // ElasticSearchQuery::PERCENTILES_RANKS,
                // ElasticSearchQuery::STATS,
                ElasticSearchQuery::EXTENDED_STATS,
                // ElasticSearchQuery::GEO_BOUNDS,
                // ElasticSearchQuery::GEO_CENTROID,
                ElasticSearchQuery::SCRIPT,
                ElasticSearchQuery::FILTERS,
                ElasticSearchQuery::INLINE,
            ],
            ElasticSearchQuery::supportedOperationTypes()
        );
    }

    /**
     */
    public function test_missing_field_filter()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query
            ->where('field', 'missing')
            ;

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([
            'bool' => [
                'must_not' => [
                    [
                        'exists' => [
                            'field' => 'field'
                        ]
                    ],
                ]
            ]
        ], $filters[0]);
    }

    /**
     */
    public function test_missing_nested_object_filter()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query
            ->setNestedFields(['nest'])
            ->where('nest', 'missing')
            ;

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([
            'bool' => [
                'must_not' => [
                    [
                        'nested' => [
                            'path'  => 'nest',
                            'query' => [
                                'match_all' => [
                                ]
                            ]
                        ]
                    ],
                ],
            ],
        ], $filters[0]);
    }

    /**
     */
    public function test_missing_nested_field_filter()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query
            ->setNestedFields(['nest'])
            ->where('nest.field', 'missing')
            ;

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([
            'bool' => [
                'must_not' => [
                    [
                        'nested' => [
                            'path'  => 'nest',
                            'query' => [
                                'filtered' => [
                                    'filter' => [
                                        'bool' => [
                                            'must' => [
                                                [
                                                    'exists' => [
                                                        'field' => 'nest.field'
                                                    ]
                                                ]
                                            ],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ], $filters[0]);
    }

    /**/
}
