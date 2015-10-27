<?php

return array(
    'console' => array(
        'router' => array(
            'routes' => array(
                'sphinxindex_set' => array(
                    'type' => 'simple',
                    'options' => array(
                        'route'    => 'index (build|update):command <index>',
                        'defaults' => array(
                            'controller'    => 'SphinxIndex\Index\Index',
                            'action'        => 'index',
                        ),
                    ),
                ),
                'sphinxindex_split' => array(
                    'type' => 'simple',
                    'options' => array(
                        'route'    => 'index split <index> [<target>]',
                        'defaults' => array(
                            'controller'    => 'SphinxIndex\Index\Split',
                            'action'        => 'split',
                        ),
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'sphinx_index' => __DIR__ . '/../view',
        ),
    ),
);
